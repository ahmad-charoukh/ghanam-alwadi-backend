<?php

namespace App\Filament\Resources\Coupons;

use App\Filament\Resources\Coupons\Pages\CreateCoupon;
use App\Filament\Resources\Coupons\Pages\EditCoupon;
use App\Filament\Resources\Coupons\Pages\ListCoupons;
use App\Filament\Resources\Coupons\Schemas\CouponForm;
use App\Filament\Resources\Coupons\Tables\CouponsTable;
use App\Models\Coupon;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class CouponResource extends Resource
{
    protected static ?string $model = Coupon::class;

    protected static string|BackedEnum|null $navigationIcon =
        Heroicon::OutlinedTicket;

    protected static ?string $recordTitleAttribute = 'code';

    protected static ?string $navigationLabel =
        'كوبونات الخصم';

    protected static ?string $modelLabel =
        'كوبون خصم';

    protected static ?string $pluralModelLabel =
        'كوبونات الخصم';

    protected static string|UnitEnum|null $navigationGroup =
        'إدارة المبيعات';

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return CouponForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return CouponsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                ListCoupons::route('/'),

            'create' =>
                CreateCoupon::route('/create'),

            'edit' =>
                EditCoupon::route('/{record}/edit'),
        ];
    }

    /**
     * عدد الكوبونات النشطة.
     */
    public static function getNavigationBadge(): ?string
    {
        return (string) Coupon::query()
            ->where('is_active', true)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'success';
    }
}