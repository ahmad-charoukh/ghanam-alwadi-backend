<?php

namespace App\Filament\Resources\Banners;

use App\Filament\Resources\Banners\Pages\CreateBanner;
use App\Filament\Resources\Banners\Pages\EditBanner;
use App\Filament\Resources\Banners\Pages\ListBanners;
use App\Filament\Resources\Banners\Pages\ViewBanner;
use App\Filament\Resources\Banners\Schemas\BannerForm;
use App\Filament\Resources\Banners\Schemas\BannerInfolist;
use App\Filament\Resources\Banners\Tables\BannersTable;
use App\Models\Banner;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-photo';

    protected static ?string $recordTitleAttribute = 'title';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationLabel(): string
    {
        return 'البنرات الإعلانية';
    }

    public static function getModelLabel(): string
    {
        return 'بنر إعلاني';
    }

    public static function getPluralModelLabel(): string
    {
        return 'البنرات الإعلانية';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'إدارة المتجر';
    }

    public static function getNavigationSort(): ?int
    {
        return 5;
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::currentlyVisibleQuery()->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $visibleBannersCount = static::currentlyVisibleQuery()->count();

        if ($visibleBannersCount === 0) {
            return 'gray';
        }

        $hasBannerExpiringSoon = static::currentlyVisibleQuery()
            ->whereNotNull('expires_at')
            ->whereBetween('expires_at', [
                now(),
                now()->addDays(3),
            ])
            ->exists();

        return $hasBannerExpiringSoon
            ? 'warning'
            : 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'عدد البنرات الظاهرة حاليًا داخل التطبيق';
    }

    private static function currentlyVisibleQuery(): Builder
    {
        return Banner::query()
            ->where('is_active', true)
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function (Builder $query): void {
                $query
                    ->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            });
    }

    public static function form(Schema $schema): Schema
    {
        return BannerForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return BannerInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return BannersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListBanners::route('/'),
            'create' => CreateBanner::route('/create'),
            'view' => ViewBanner::route('/{record}'),
            'edit' => EditBanner::route('/{record}/edit'),
        ];
    }
}