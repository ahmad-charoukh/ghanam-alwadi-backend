<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    /**
     * تحديث الاسم والبريد الإلكتروني.
     */
    public function update(
        Request $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $request->merge([
            'email' => Str::lower(
                trim(
                    (string) $request->input(
                        'email'
                    )
                )
            ),
        ]);

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

                Rule::unique(
                    'users',
                    'email'
                )->ignore($user->id),
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
                'البريد الإلكتروني مستخدم في حساب آخر.',
        ]);

        $newEmail = $validated['email'];

        $emailChanged =
            $newEmail !== Str::lower(
                $user->email
            );

        /*
         * نستخدم forceFill لأن email_verified_at
         * غير موجود داخل fillable لأسباب أمنية.
         */
        $user->forceFill([
            'name' =>
                trim($validated['name']),

            'email' =>
                $newEmail,

            'email_verified_at' =>
                $emailChanged
                    ? null
                    : $user->email_verified_at,
        ])->save();

        return response()->json([
            'success' => true,

            'message' =>
                'تم تحديث الملف الشخصي بنجاح.',

            'data' => [
                'user' =>
                    $this->userData(
                        $user->fresh()
                    ),
            ],
        ]);
    }

    /**
     * تغيير كلمة المرور.
     */
    public function updatePassword(
        Request $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'current_password' => [
                'required',
                'string',
            ],

            'password' => [
                'required',
                'confirmed',

                Password::min(8)
                    ->letters()
                    ->numbers(),
            ],
        ], [
            'current_password.required' =>
                'كلمة المرور الحالية مطلوبة.',

            'password.required' =>
                'كلمة المرور الجديدة مطلوبة.',

            'password.confirmed' =>
                'تأكيد كلمة المرور الجديدة غير مطابق.',

            'password.min' =>
                'يجب ألا تقل كلمة المرور عن 8 أحرف.',
        ]);

        if (
            ! Hash::check(
                $validated['current_password'],
                $user->password
            )
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'كلمة المرور الحالية غير صحيحة.',

                'errors' => [
                    'current_password' => [
                        'كلمة المرور الحالية غير صحيحة.',
                    ],
                ],
            ], 422);
        }

        if (
            Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'كلمة المرور الجديدة يجب أن تختلف عن الحالية.',

                'errors' => [
                    'password' => [
                        'اختر كلمة مرور جديدة مختلفة.',
                    ],
                ],
            ], 422);
        }

        $user->update([
            'password' =>
                Hash::make(
                    $validated['password']
                ),
        ]);

        /*
         * إلغاء رموز الأجهزة الأخرى بعد تغيير كلمة
         * المرور، مع الإبقاء على رمز الجهاز الحالي.
         */
        $currentToken =
            $user->currentAccessToken();

        if ($currentToken) {
            $user->tokens()
                ->where(
                    'id',
                    '!=',
                    $currentToken->id
                )
                ->delete();
        }

        return response()->json([
            'success' => true,

            'message' =>
                'تم تغيير كلمة المرور بنجاح.',
        ]);
    }

    /**
     * حذف حساب المستخدم نهائيًا.
     */
    public function deleteAccount(
        Request $request
    ): JsonResponse {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'password' => [
                'required',
                'string',
            ],

            'confirm_deletion' => [
                'required',
                'accepted',
            ],
        ], [
            'password.required' =>
                'كلمة المرور مطلوبة لتأكيد حذف الحساب.',

            'confirm_deletion.required' =>
                'يجب تأكيد حذف الحساب.',

            'confirm_deletion.accepted' =>
                'يجب الموافقة على حذف الحساب نهائيًا.',
        ]);

        if (
            ! Hash::check(
                $validated['password'],
                $user->password
            )
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'كلمة المرور غير صحيحة.',

                'errors' => [
                    'password' => [
                        'كلمة المرور غير صحيحة.',
                    ],
                ],
            ], 422);
        }

        /*
         * منع حذف آخر حساب إدارة حتى لا يتم
         * فقدان إمكانية الوصول إلى لوحة التحكم.
         */
        if (
            $user->is_admin
            && User::query()
                ->where('is_admin', true)
                ->count() <= 1
        ) {
            return response()->json([
                'success' => false,

                'message' =>
                    'لا يمكن حذف آخر حساب إدارة في النظام.',

                'errors' => [
                    'account' => [
                        'أنشئ حساب إدارة آخر قبل حذف هذا الحساب.',
                    ],
                ],
            ], 422);
        }

        DB::transaction(
            function () use ($user): void {
                /*
                 * حذف البيانات الشخصية المرتبطة
                 * بحساب المستخدم.
                 */
                $user->notifications()
                    ->delete();

                $user->tokens()
                    ->delete();

                $user->cartItems()
                    ->delete();

                $user->favorites()
                    ->delete();

                $user->addresses()
                    ->delete();

                DB::table(
                    'password_reset_tokens'
                )
                    ->where(
                        'email',
                        $user->email
                    )
                    ->delete();

                /*
                 * الطلبات والتذاكر والتقييمات التاريخية
                 * تبقى محفوظة ويصبح user_id فيها فارغًا
                 * حسب علاقات nullOnDelete.
                 */
                $user->delete();
            }
        );

        return response()->json([
            'success' => true,

            'message' =>
                'تم حذف حسابك وتسجيل الخروج من جميع الأجهزة.',
        ]);
    }

    /**
     * تجهيز بيانات المستخدم.
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

            'updated_at' =>
                $user->updated_at
                    ?->toIso8601String(),
        ];
    }
}