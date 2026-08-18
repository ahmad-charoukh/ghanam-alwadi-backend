<?php

namespace App\Filament\Delivery\Resources\Orders;

use App\Filament\Delivery\Resources\Orders\Pages\ListOrders;
use App\Filament\Delivery\Resources\Orders\Pages\ViewOrder;
use App\Filament\Delivery\Resources\Orders\Schemas\OrderForm;
use App\Filament\Delivery\Resources\Orders\Schemas\OrderInfolist;
use App\Filament\Delivery\Resources\Orders\Tables\OrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-truck';

    protected static ?string $recordTitleAttribute =
        'order_number';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationLabel(): string
    {
        return 'طلباتي';
    }

    public static function getModelLabel(): string
    {
        return 'طلب توصيل';
    }

    public static function getPluralModelLabel(): string
    {
        return 'طلبات التوصيل';
    }

    public static function getNavigationBadge(): ?string
    {
        $count = static::getEloquentQuery()
            ->whereNotIn('status', [
                Order::STATUS_DELIVERED,
                Order::STATUS_CANCELLED,
            ])
            ->count();

        return $count > 0
            ? (string) $count
            : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }

    /**
     * المندوب يشاهد فقط الطلبات المعيّنة له.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where(
                'delivery_driver_id',
                auth()->id()
            );
    }

    public static function canView(
        Model $record
    ): bool {
        return (int) $record->getAttribute(
            'delivery_driver_id'
        ) === (int) auth()->id();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(
        Model $record
    ): bool {
        return false;
    }

    public static function canDelete(
        Model $record
    ): bool {
        return false;
    }

    public static function form(
        Schema $schema
    ): Schema {
        return OrderForm::configure($schema);
    }

    public static function infolist(
        Schema $schema
    ): Schema {
        return OrderInfolist::configure($schema);
    }

    public static function table(
        Table $table
    ): Table {
        return OrdersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOrders::route('/'),
            'view' => ViewOrder::route('/{record}'),
        ];
    }
}