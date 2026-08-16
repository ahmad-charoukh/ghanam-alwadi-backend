<?php

namespace App\Filament\Resources\Settings;

use App\Filament\Resources\Settings\Pages\CreateSetting;
use App\Filament\Resources\Settings\Pages\EditSetting;
use App\Filament\Resources\Settings\Pages\ListSettings;
use App\Filament\Resources\Settings\Pages\ViewSetting;
use App\Filament\Resources\Settings\Schemas\SettingForm;
use App\Filament\Resources\Settings\Schemas\SettingInfolist;
use App\Filament\Resources\Settings\Tables\SettingsTable;
use App\Models\Setting;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class SettingResource extends Resource
{
    protected static ?string $model = Setting::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-cog-6-tooth';

    protected static ?string $recordTitleAttribute = 'app_name';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationLabel(): string
    {
        return 'إعدادات المتجر';
    }

    public static function getModelLabel(): string
    {
        return 'إعدادات المتجر';
    }

    public static function getPluralModelLabel(): string
    {
        return 'إعدادات المتجر';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'إدارة النظام';
    }

    public static function getNavigationSort(): ?int
    {
        return 1;
    }

    public static function getNavigationBadge(): ?string
    {
        return Setting::query()->exists()
            ? 'مضبوطة'
            : 'غير مضبوطة';
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return Setting::query()->exists()
            ? 'success'
            : 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return Setting::query()->exists()
            ? 'تم إنشاء إعدادات المتجر'
            : 'يجب إنشاء إعدادات المتجر';
    }

    /**
     * يسمح بإنشاء سجل واحد فقط.
     */
    public static function canCreate(): bool
    {
        return Setting::query()->doesntExist();
    }

    /**
     * عند الضغط على إعدادات المتجر يفتح سجل الإعدادات مباشرة.
     */
    public static function getNavigationUrl(): string
    {
        $setting = Setting::query()->first();

        if ($setting !== null) {
            return static::getUrl('edit', [
                'record' => $setting,
            ]);
        }

        return static::getUrl('index');
    }

    public static function form(Schema $schema): Schema
    {
        return SettingForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return SettingInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SettingsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSettings::route('/'),
            'create' => CreateSetting::route('/create'),
            'view' => ViewSetting::route('/{record}'),
            'edit' => EditSetting::route('/{record}/edit'),
        ];
    }
}