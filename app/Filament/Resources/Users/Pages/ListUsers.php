<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords
{
    protected static string $resource =
        UserResource::class;

    public function getTitle(): string
    {
        return 'إدارة المستخدمين';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('إضافة مستخدم جديد')
                ->icon('heroicon-m-user-plus')
                ->color('success'),
        ];
    }
}