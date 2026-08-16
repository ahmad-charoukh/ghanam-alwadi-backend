<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailVerificationCodeNotification;
use App\Notifications\PasswordResetCodeNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class AuthController extends Controller
{
    private const RESET_CODE_EXPIRY_MINUTES = 10;

    private const EMAIL_VERIFICATION_CODE_EXPIRY_MINUTES = 10;

    /**
     * إنشاء حساب جديد.
     */
    public function register(Request $request): JsonResponse
    {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'min:2',
                'max:150',
            ],

            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ], [
            'name.required' =>
                'اسم المستخدم مطلوب.',

            'name.min' =>
                'يجب ألا يقل الاسم عن حرفين.',

            'email.required' =>
                'البريد الإلكتروني مطلوب.',

            'email.email' =>
                'البريد الإلكتروني غير صحيح.',

            'email.unique' =>
                'البريد الإلكتروني مستخدم مسبقاً.',

            'password.required' =>
                'كلمة المرور مطلوبة.',

            'password.min' =>
                'يجب ألا تقل كلمة المرور عن 8 أحرف.',

            'password.confirmed' =>
                'تأكيد كلمة المرور غير مطابق.',
        ]);

        $user = User::query()->create([
            'name' =>
                trim($validated['name']),

            'email' =>
                $validated['email'],

            'password' =>
                Hash::make(
                    $validated['password']
                ),
        ]);

        $deviceName =
            $validated['device_name']
            ?? 'ghanam-alwadi-mobile';

        $token = $user
            ->createToken(
                $deviceName,
                ['mobile']
            )
            ->plainTextToken;

        return response()->json([
            'success' => true,

            'message' =>
                'تم إنشاء الحساب بنجاح.',

            'data' => [
                'user' =>
                    $this->userData($user),

                'token' =>
                    $token,

                'token_type' =>
                    'Bearer',
            ],
        ], 201);
    }

    /**
     * تسجيل الدخول.
     */
    public function login(Request $request): JsonResponse
    {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
            ],

            'password' => [
                'required',
                'string',
            ],

            'device_name' => [
                'nullable',
                'string',
                'max:100',
            ],
        ], [
            'email.required' =>
                'البريد الإلكتروني مطلوب.',

            'email.email' =>
                'البريد الإلكتروني غير صحيح.',

            'password.required' =>
                'كلمة المرور مطلوبة.',
        ]);

        $user = User::query()
            ->where(
                'email',
                $validated['email']
            )
            ->first();

        if (
            ! $user
            || ! Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'البريد الإلكتروني أو كلمة المرور غير صحيحة.',
            ], 401);
        }

        $deviceName =
            $validated['device_name']
            ?? 'ghanam-alwadi-mobile';

        /*
         * حذف التوكن القديم للجهاز نفسه.
         */
        $user->tokens()
            ->where(
                'name',
                $deviceName
            )
            ->delete();

        $token = $user
            ->createToken(
                $deviceName,
                ['mobile']
            )
            ->plainTextToken;

        return response()->json([
            'success' => true,

            'message' =>
                'تم تسجيل الدخول بنجاح.',

            'data' => [
                'user' =>
                    $this->userData($user),

                'token' =>
                    $token,

                'token_type' =>
                    'Bearer',
            ],
        ]);
    }

    /**
     * إرسال رمز استرجاع كلمة المرور.
     */
    public function forgotPassword(
        Request $request
    ): JsonResponse {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],
        ], [
            'email.required' =>
                'البريد الإلكتروني مطلوب.',

            'email.email' =>
                'البريد الإلكتروني غير صحيح.',
        ]);

        $user = User::query()
            ->where(
                'email',
                $validated['email']
            )
            ->first();

        /*
         * نعيد نفس الرسالة عند عدم وجود البريد
         * لحماية بيانات المستخدمين.
         */
        if (! $user) {
            return $this->resetCodeSentResponse();
        }

        $code = $this->generateCode();

        DB::table('password_reset_tokens')
            ->updateOrInsert(
                [
                    'email' =>
                        $user->email,
                ],
                [
                    'token' =>
                        Hash::make($code),

                    'created_at' =>
                        now(),
                ]
            );

        try {
            $user->notify(
                new PasswordResetCodeNotification(
                    $code,
                    self::RESET_CODE_EXPIRY_MINUTES
                )
            );
        } catch (Throwable $exception) {
            DB::table('password_reset_tokens')
                ->where(
                    'email',
                    $user->email
                )
                ->delete();

            report($exception);

            return response()->json([
                'success' => false,

                'message' =>
                    'تعذر إرسال رمز الاسترجاع حاليًا. حاول مرة أخرى لاحقًا.',
            ], 503);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'إذا كان البريد مسجلاً، فسيصلك رمز استرجاع كلمة المرور.',

            'data' => [
                'expires_in_minutes' =>
                    self::RESET_CODE_EXPIRY_MINUTES,

                /*
                 * يظهر فقط في بيئة التطوير المحلية.
                 */
                'debug_code' =>
                    app()->environment('local')
                        ? $code
                        : null,
            ],
        ]);
    }

    /**
     * إعادة تعيين كلمة المرور.
     */
    public function resetPassword(
        Request $request
    ): JsonResponse {
        $this->normalizeEmail($request);

        $validated = $request->validate([
            'email' => [
                'required',
                'email',
                'max:255',
            ],

            'code' => [
                'required',
                'digits:6',
            ],

            'password' => [
                'required',
                'string',
                'min:8',
                'max:255',
                'confirmed',
            ],
        ], [
            'email.required' =>
                'البريد الإلكتروني مطلوب.',

            'email.email' =>
                'البريد الإلكتروني غير صحيح.',

            'code.required' =>
                'رمز التحقق مطلوب.',

            'code.digits' =>
                'رمز التحقق يجب أن يتكون من 6 أرقام.',

            'password.required' =>
                'كلمة المرور الجديدة مطلوبة.',

            'password.min' =>
                'يجب ألا تقل كلمة المرور عن 8 أحرف.',

            'password.confirmed' =>
                'تأكيد كلمة المرور غير مطابق.',
        ]);

        $result = DB::transaction(
            function () use ($validated): array {
                $resetRecord = DB::table(
                    'password_reset_tokens'
                )
                    ->where(
                        'email',
                        $validated['email']
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $resetRecord) {
                    return [
                        'status' =>
                            'invalid',
                    ];
                }

                $createdAt = Carbon::parse(
                    $resetRecord->created_at
                );

                if (
                    $createdAt->lt(
                        now()->subMinutes(
                            self::RESET_CODE_EXPIRY_MINUTES
                        )
                    )
                ) {
                    DB::table(
                        'password_reset_tokens'
                    )
                        ->where(
                            'email',
                            $validated['email']
                        )
                        ->delete();

                    return [
                        'status' =>
                            'expired',
                    ];
                }

                if (
                    ! Hash::check(
                        $validated['code'],
                        $resetRecord->token
                    )
                ) {
                    return [
                        'status' =>
                            'invalid',
                    ];
                }

                $user = User::query()
                    ->where(
                        'email',
                        $validated['email']
                    )
                    ->lockForUpdate()
                    ->first();

                if (! $user) {
                    DB::table(
                        'password_reset_tokens'
                    )
                        ->where(
                            'email',
                            $validated['email']
                        )
                        ->delete();

                    return [
                        'status' =>
                            'invalid',
                    ];
                }

                $user->forceFill([
                    'password' =>
                        Hash::make(
                            $validated['password']
                        ),

                    'remember_token' =>
                        Str::random(60),
                ])->save();

                /*
                 * تسجيل الخروج من جميع الأجهزة.
                 */
                $user->tokens()->delete();

                DB::table(
                    'password_reset_tokens'
                )
                    ->where(
                        'email',
                        $validated['email']
                    )
                    ->delete();

                return [
                    'status' =>
                        'success',
                ];
            }
        );

        if ($result['status'] === 'expired') {
            throw ValidationException::withMessages([
                'code' => [
                    'انتهت صلاحية رمز التحقق. اطلب رمزًا جديدًا.',
                ],
            ]);
        }

        if ($result['status'] !== 'success') {
            throw ValidationException::withMessages([
                'code' => [
                    'رمز التحقق غير صحيح.',
                ],
            ]);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'تم تغيير كلمة المرور بنجاح. يمكنك تسجيل الدخول الآن.',
        ]);
    }

    /**
     * إرسال رمز تأكيد البريد.
     */
    public function sendVerificationCode(
        Request $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'success' => true,

                'message' =>
                    'البريد الإلكتروني مؤكد مسبقًا.',

                'data' => [
                    'email_verified' =>
                        true,
                ],
            ]);
        }

        $code = $this->generateCode();

        $cacheKey =
            $this->emailVerificationCacheKey(
                $user
            );

        Cache::put(
            $cacheKey,
            Hash::make($code),
            now()->addMinutes(
                self::EMAIL_VERIFICATION_CODE_EXPIRY_MINUTES
            )
        );

        try {
            $user->notify(
                new EmailVerificationCodeNotification(
                    $code,
                    self::EMAIL_VERIFICATION_CODE_EXPIRY_MINUTES
                )
            );
        } catch (Throwable $exception) {
            Cache::forget($cacheKey);

            report($exception);

            return response()->json([
                'success' => false,

                'message' =>
                    'تعذر إرسال رمز التحقق حاليًا. حاول مرة أخرى لاحقًا.',
            ], 503);
        }

        return response()->json([
            'success' => true,

            'message' =>
                'تم إرسال رمز التحقق إلى بريدك الإلكتروني.',

            'data' => [
                'email' =>
                    $user->email,

                'expires_in_minutes' =>
                    self::EMAIL_VERIFICATION_CODE_EXPIRY_MINUTES,

                /*
                 * يظهر رمز التحقق في البيئة المحلية فقط.
                 * عند رفع المشروع وتغيير APP_ENV إلى production
                 * ستكون القيمة null.
                 */
                'debug_code' =>
                    app()->environment('local')
                        ? $code
                        : null,
            ],
        ]);
    }

    /**
     * تأكيد البريد الإلكتروني.
     */
    public function verifyEmail(
        Request $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        if ($user->email_verified_at) {
            return response()->json([
                'success' => true,

                'message' =>
                    'البريد الإلكتروني مؤكد مسبقًا.',

                'data' => [
                    'user' =>
                        $this->userData($user),
                ],
            ]);
        }

        $validated = $request->validate([
            'code' => [
                'required',
                'digits:6',
            ],
        ], [
            'code.required' =>
                'رمز التحقق مطلوب.',

            'code.digits' =>
                'رمز التحقق يجب أن يتكون من 6 أرقام.',
        ]);

        $cacheKey =
            $this->emailVerificationCacheKey(
                $user
            );

        $hashedCode = Cache::get($cacheKey);

        if (! is_string($hashedCode)) {
            throw ValidationException::withMessages([
                'code' => [
                    'انتهت صلاحية رمز التحقق. اطلب رمزًا جديدًا.',
                ],
            ]);
        }

        if (
            ! Hash::check(
                $validated['code'],
                $hashedCode
            )
        ) {
            throw ValidationException::withMessages([
                'code' => [
                    'رمز التحقق غير صحيح.',
                ],
            ]);
        }

        $user->forceFill([
            'email_verified_at' =>
                now(),
        ])->save();

        Cache::forget($cacheKey);

        return response()->json([
            'success' => true,

            'message' =>
                'تم تأكيد بريدك الإلكتروني بنجاح.',

            'data' => [
                'user' =>
                    $this->userData(
                        $user->fresh()
                    ),
            ],
        ]);
    }

    /**
     * بيانات المستخدم الحالي.
     */
    public function me(
        Request $request
    ): JsonResponse {
        return response()->json([
            'success' => true,

            'message' =>
                'تم جلب بيانات المستخدم.',

            'data' => [
                'user' =>
                    $this->userData(
                        $request->user()
                    ),
            ],
        ]);
    }

    /**
     * تسجيل الخروج.
     */
    public function logout(
        Request $request
    ): JsonResponse {
        $request->user()
            ->currentAccessToken()
            ?->delete();

        return response()->json([
            'success' => true,

            'message' =>
                'تم تسجيل الخروج بنجاح.',
        ]);
    }

    /**
     * إنشاء رمز تحقق من 6 أرقام.
     */
    private function generateCode(): string
    {
        return str_pad(
            (string) random_int(0, 999999),
            6,
            '0',
            STR_PAD_LEFT
        );
    }

    /**
     * توحيد البريد الإلكتروني.
     */
    private function normalizeEmail(
        Request $request
    ): void {
        if (! $request->has('email')) {
            return;
        }

        $request->merge([
            'email' =>
                Str::lower(
                    trim(
                        (string) $request->input(
                            'email'
                        )
                    )
                ),
        ]);
    }

    /**
     * استجابة إرسال رمز الاسترجاع.
     */
    private function resetCodeSentResponse(): JsonResponse
    {
        return response()->json([
            'success' => true,

            'message' =>
                'إذا كان البريد مسجلاً، فسيصلك رمز استرجاع كلمة المرور.',
        ]);
    }

    /**
     * إنشاء مفتاح تخزين رمز تأكيد البريد.
     */
    private function emailVerificationCacheKey(
        User $user
    ): string {
        return 'email-verification:'
            . $user->id
            . ':'
            . hash(
                'sha256',
                Str::lower($user->email)
            );
    }

    /**
     * تجهيز بيانات المستخدم للـAPI.
     */
    private function userData(
        User $user
    ): array {
        return [
            'id' =>
                $user->id,

            'name' =>
                $user->name,

            'email' =>
                $user->email,

            'email_verified_at' =>
                $user->email_verified_at
                    ?->toIso8601String(),

            'created_at' =>
                $user->created_at
                    ?->toIso8601String(),
        ];
    }
}