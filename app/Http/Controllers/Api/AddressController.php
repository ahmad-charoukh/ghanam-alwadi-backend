<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    /**
     * عرض جميع عناوين العميل المسجل.
     */
    public function index(Request $request): JsonResponse
    {
        $addresses = $request->user()
            ->addresses()
            ->orderByDesc('is_default')
            ->latest('id')
            ->get()
            ->map(
                fn (Address $address): array =>
                    $this->addressData($address)
            )
            ->values();

        return response()->json([
            'success' => true,
            'message' => 'تم تحميل العناوين بنجاح.',
            'data' => $addresses,
        ]);
    }

    /**
     * إضافة عنوان جديد للعميل.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateAddress($request);

        $address = DB::transaction(
            function () use ($request, $validated): Address {
                $user = $request->user();

                $hasAddresses = $user
                    ->addresses()
                    ->exists();

                $makeDefault = ! $hasAddresses
                    || (bool) ($validated['is_default'] ?? false);

                if ($makeDefault) {
                    $user->addresses()->update([
                        'is_default' => false,
                    ]);
                }

                $validated['label'] =
                    $validated['label'] ?? 'المنزل';

                $validated['country'] =
                    $validated['country'] ?? 'السعودية';

                $validated['is_default'] = $makeDefault;

                return $user
                    ->addresses()
                    ->create($validated);
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة العنوان بنجاح.',
            'data' => $this->addressData($address),
        ], 201);
    }

    /**
     * عرض عنوان واحد يخص العميل المسجل.
     */
    public function show(
        Request $request,
        Address $address,
    ): JsonResponse {
        if (! $this->belongsToUser($request, $address)) {
            return $this->addressNotFound();
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تحميل العنوان بنجاح.',
            'data' => $this->addressData($address),
        ]);
    }

    /**
     * تعديل عنوان يخص العميل المسجل.
     */
    public function update(
        Request $request,
        Address $address,
    ): JsonResponse {
        if (! $this->belongsToUser($request, $address)) {
            return $this->addressNotFound();
        }

        $validated = $this->validateAddress(
            $request,
            true
        );

        $address = DB::transaction(
            function () use (
                $request,
                $address,
                $validated
            ): Address {
                $user = $request->user();

                if (
                    array_key_exists('is_default', $validated)
                    && (bool) $validated['is_default']
                ) {
                    $user->addresses()
                        ->whereKeyNot($address->id)
                        ->update([
                            'is_default' => false,
                        ]);
                }

                $address->update($validated);

                /*
                 * يجب أن يبقى هناك عنوان افتراضي واحد
                 * على الأقل ما دام لدى العميل عناوين.
                 */
                $hasDefaultAddress = $user
                    ->addresses()
                    ->where('is_default', true)
                    ->exists();

                if (! $hasDefaultAddress) {
                    $address->update([
                        'is_default' => true,
                    ]);
                }

                return $address->fresh();
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تعديل العنوان بنجاح.',
            'data' => $this->addressData($address),
        ]);
    }

    /**
     * حذف عنوان يخص العميل المسجل.
     */
    public function destroy(
        Request $request,
        Address $address,
    ): JsonResponse {
        if (! $this->belongsToUser($request, $address)) {
            return $this->addressNotFound();
        }

        DB::transaction(
            function () use ($request, $address): void {
                $user = $request->user();
                $wasDefault = $address->is_default;

                $address->delete();

                if (! $wasDefault) {
                    return;
                }

                $newDefaultAddress = $user
                    ->addresses()
                    ->oldest('id')
                    ->first();

                $newDefaultAddress?->update([
                    'is_default' => true,
                ]);
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'تم حذف العنوان بنجاح.',
        ]);
    }

    /**
     * تعيين عنوان كعنوان افتراضي للعميل.
     */
    public function setDefault(
        Request $request,
        Address $address,
    ): JsonResponse {
        if (! $this->belongsToUser($request, $address)) {
            return $this->addressNotFound();
        }

        DB::transaction(
            function () use ($request, $address): void {
                $request->user()
                    ->addresses()
                    ->whereKeyNot($address->id)
                    ->update([
                        'is_default' => false,
                    ]);

                $address->update([
                    'is_default' => true,
                ]);
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'تم تعيين العنوان كعنوان افتراضي.',
            'data' => $this->addressData(
                $address->fresh()
            ),
        ]);
    }

    /**
     * التحقق من بيانات العنوان.
     */
    private function validateAddress(
        Request $request,
        bool $updating = false,
    ): array {
        $required = $updating
            ? ['sometimes', 'required']
            : ['required'];

        return $request->validate([
            'label' => [
                'sometimes',
                'required',
                'string',
                'max:50',
            ],
            'recipient_name' => [
                ...$required,
                'string',
                'max:150',
            ],
            'phone' => [
                ...$required,
                'string',
                'max:30',
            ],
            'country' => [
                'sometimes',
                'required',
                'string',
                'max:100',
            ],
            'city' => [
                ...$required,
                'string',
                'max:100',
            ],
            'district' => [
                'nullable',
                'string',
                'max:150',
            ],
            'street' => [
                'nullable',
                'string',
                'max:200',
            ],
            'building_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'apartment_number' => [
                'nullable',
                'string',
                'max:50',
            ],
            'postal_code' => [
                'nullable',
                'string',
                'max:30',
            ],
            'additional_details' => [
                'nullable',
                'string',
                'max:1000',
            ],
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            'is_default' => [
                'sometimes',
                'boolean',
            ],
        ], [
            'recipient_name.required' =>
                'اسم مستلم الطلب مطلوب.',
            'recipient_name.max' =>
                'اسم المستلم طويل جدًا.',
            'phone.required' =>
                'رقم هاتف المستلم مطلوب.',
            'phone.max' =>
                'رقم الهاتف طويل جدًا.',
            'city.required' =>
                'المدينة مطلوبة.',
            'latitude.between' =>
                'خط العرض غير صحيح.',
            'longitude.between' =>
                'خط الطول غير صحيح.',
            'is_default.boolean' =>
                'قيمة العنوان الافتراضي غير صحيحة.',
        ]);
    }

    /**
     * التأكد من أن العنوان يخص المستخدم المسجل.
     */
    private function belongsToUser(
        Request $request,
        Address $address,
    ): bool {
        return (int) $address->user_id
            === (int) $request->user()->id;
    }

    /**
     * استجابة العنوان غير الموجود أو غير المملوك للمستخدم.
     */
    private function addressNotFound(): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => 'العنوان غير موجود.',
        ], 404);
    }

    /**
     * تنسيق بيانات العنوان المرسلة إلى التطبيق.
     */
    private function addressData(Address $address): array
    {
        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone' => $address->phone,
            'country' => $address->country,
            'city' => $address->city,
            'district' => $address->district,
            'street' => $address->street,
            'building_number' => $address->building_number,
            'apartment_number' => $address->apartment_number,
            'postal_code' => $address->postal_code,
            'additional_details' =>
                $address->additional_details,
            'latitude' => $address->latitude !== null
                ? (float) $address->latitude
                : null,
            'longitude' => $address->longitude !== null
                ? (float) $address->longitude
                : null,
            'is_default' => (bool) $address->is_default,
            'full_address' => $address->full_address,
            'created_at' => $address->created_at
                ?->toIso8601String(),
            'updated_at' => $address->updated_at
                ?->toIso8601String(),
        ];
    }
}