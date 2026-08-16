<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $setting = Setting::current();

        return response()->json([
            'success' => true,
            'message' => 'تم تحميل إعدادات المتجر بنجاح.',
            'data' => [
                'app_name' => $setting->app_name,

                'logo' => $setting->logo,
                'logo_url' => $this->fileUrl(
                    $setting->logo,
                    $request
                ),

                'phone' => $setting->phone,
                'whatsapp' => $setting->whatsapp,
                'email' => $setting->email,
                'address' => $setting->address,
                'about' => $setting->about,

                'social_links' => [
                    'facebook' => $setting->facebook_url,
                    'instagram' => $setting->instagram_url,
                    'tiktok' => $setting->tiktok_url,
                    'telegram' => $setting->telegram_url,
                    'x' => $setting->x_url,
                ],

                'tax_percentage' =>
                    (float) $setting->tax_percentage,

                'shipping_cost' =>
                    (float) $setting->shipping_cost,

                'free_shipping_amount' =>
                    $setting->free_shipping_amount !== null
                        ? (float) $setting->free_shipping_amount
                        : null,

                'currency' => $setting->currency,

                'maintenance_mode' =>
                    (bool) $setting->maintenance_mode,

                'updated_at' =>
                    $setting->updated_at?->toIso8601String(),
            ],
        ]);
    }

    private function fileUrl(
        ?string $path,
        Request $request
    ): ?string {
        if (blank($path)) {
            return null;
        }

        if (filter_var($path, FILTER_VALIDATE_URL)) {
            return $path;
        }

        $storageUrl = Storage::url($path);

        if (filter_var($storageUrl, FILTER_VALIDATE_URL)) {
            return $storageUrl;
        }

        return rtrim($request->getSchemeAndHttpHost(), '/')
            . '/'
            . ltrim($storageUrl, '/');
    }
}