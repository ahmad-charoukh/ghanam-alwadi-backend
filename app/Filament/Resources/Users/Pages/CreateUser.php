<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource =
        UserResource::class;

    /**
     * الصلاحية التي تم اختيارها في النموذج.
     */
    private bool $shouldBeAdmin = false;

    /**
     * إزالة is_admin من الإنشاء العادي
     * لأنه غير موجود داخل fillable.
     */
    protected function mutateFormDataBeforeCreate(
        array $data
    ): array {
        $this->shouldBeAdmin = (bool) (
            $data['is_admin'] ?? false
        );

        unset($data['is_admin']);

        return $data;
    }

    /**
     * حفظ صلاحية الإدارة بشكل آمن بعد إنشاء المستخدم.
     */
    protected function afterCreate(): void
    {
        $this->record
            ->forceFill([
                'is_admin' =>
                    $this->shouldBeAdmin,
            ])
            ->save();
    }

    protected function getCreatedNotificationTitle(): ?string
    {
        return 'تم إنشاء حساب المستخدم بنجاح';
    }
}