<?php

namespace App\Filament\Resources\Reviews;

use App\Filament\Resources\Reviews\Pages\CreateReview;
use App\Filament\Resources\Reviews\Pages\EditReview;
use App\Filament\Resources\Reviews\Pages\ListReviews;
use App\Filament\Resources\Reviews\Pages\ViewReview;
use App\Filament\Resources\Reviews\Schemas\ReviewForm;
use App\Filament\Resources\Reviews\Schemas\ReviewInfolist;
use App\Filament\Resources\Reviews\Tables\ReviewsTable;
use App\Models\Review;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static string|BackedEnum|null $navigationIcon =
        'heroicon-o-star';

    protected static ?string $recordTitleAttribute = 'title';

    protected static bool $hasTitleCaseModelLabel = false;

    public static function getNavigationLabel(): string
    {
        return 'تقييمات العملاء';
    }

    public static function getModelLabel(): string
    {
        return 'تقييم';
    }

    public static function getPluralModelLabel(): string
    {
        return 'تقييمات العملاء';
    }

    public static function getNavigationGroup(): ?string
    {
        return 'إدارة المتجر';
    }

    public static function getNavigationSort(): ?int
    {
        return 6;
    }

    /**
     * عدد التقييمات التي تنتظر موافقة الإدارة.
     */
    public static function getNavigationBadge(): ?string
    {
        return (string) Review::query()
            ->where('is_approved', false)
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        $pendingReviews = Review::query()
            ->where('is_approved', false)
            ->count();

        return $pendingReviews > 0
            ? 'warning'
            : 'success';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'عدد التقييمات التي تنتظر الموافقة';
    }

    /**
     * التقييمات تُرسل من العملاء، لذلك لا ننشئها يدويًا من الإدارة.
     */
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ReviewForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ReviewInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ReviewsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListReviews::route('/'),
            'create' => CreateReview::route('/create'),
            'view' => ViewReview::route('/{record}'),
            'edit' => EditReview::route('/{record}/edit'),
        ];
    }
}