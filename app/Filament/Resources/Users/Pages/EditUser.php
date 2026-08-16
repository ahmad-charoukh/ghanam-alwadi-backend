<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource =
        UserResource::class;

    /**
     * صلاحية الإدارة المختارة في النموذج.
     */
    private ?bool $shouldBeAdmin = null;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make()
                ->label('عرض')
                ->icon('heroicon-m-eye'),

            DeleteAction::make()
                ->label('حذف المستخدم')
                ->icon('heroicon-m-trash')
                ->requiresConfirmation()
                ->modalHeading('حذف حساب المستخدم')
                ->modalDescription(
                    'هل أنت متأكد من حذف هذا الحساب؟ لا يمكن التراجع عن هذه العملية.'
                )
                ->modalSubmitActionLabel(
                    'نعم، حذف الحساب'
                )
                ->visible(
                    function (): bool {
                        /*
                         * منع المستخدم من حذف حسابه الحالي
                         * من لوحة الإدارة.
                         */
                        if (
                            auth()->id()
                            === $this->record->id
                        ) {
                            return false;
                        }

                        /*
                         * منع حذف آخر مدير في النظام.
                         */
                        if (
                            $this->record->is_admin
                            && User::query()
                                ->where(
                                    'is_admin',
                                    true
                                )
                                ->count() <= 1
                        ) {
                            return false;
                        }

                        return true;
                    }
                ),
        ];
    }

    /**
     * معالجة البيانات قبل حفظ التعديل.
     */
    protected function mutateFormDataBeforeSave(
        array $data
    ): array {
        $requestedAdminStatus = (bool) (
            $data['is_admin']
            ?? $this->record->is_admin
        );

        /*
         * منع المدير الحالي من إزالة
         * صلاحية الإدارة عن حسابه بنفسه.
         */
        if (
            auth()->id()
            === $this->record->id
        ) {
            $requestedAdminStatus = true;
        }

        /*
         * منع إزالة صلاحية آخر مدير
         * حتى لو تم تعديل الطلب يدويًا.
         */
        if (
            $this->record->is_admin
            && ! $requestedAdminStatus
            && User::query()
                ->where('is_admin', true)
                ->count() <= 1
        ) {
            $requestedAdminStatus = true;
        }

        $this->shouldBeAdmin =
            $requestedAdminStatus;

        /*
         * is_admin غير موجود داخل fillable،
         * لذلك يتم حفظه بشكل منفصل وآمن.
         */
        unset($data['is_admin']);

        return $data;
    }

    /**
     * حفظ صلاحية الإدارة بعد حفظ بيانات المستخدم.
     */
    protected function afterSave(): void
    {
        if ($this->shouldBeAdmin === null) {
            return;
        }

        $this->record
            ->forceFill([
                'is_admin' =>
                    $this->shouldBeAdmin,
            ])
            ->save();
    }

    protected function getSavedNotificationTitle(): ?string
    {
        return 'تم تحديث بيانات المستخدم بنجاح';
    }
}