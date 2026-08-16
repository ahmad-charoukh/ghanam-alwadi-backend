<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUser extends ViewRecord
{
    protected static string $resource =
        UserResource::class;

    public function getTitle(): string
    {
        return 'بيانات المستخدم: '
            . $this->record->name;
    }

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('تعديل المستخدم')
                ->icon(
                    'heroicon-m-pencil-square'
                )
                ->color('primary'),
        ];
    }
}