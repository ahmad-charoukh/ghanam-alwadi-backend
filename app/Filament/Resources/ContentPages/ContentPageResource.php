<?php

namespace App\Filament\Resources\ContentPages;

use App\Filament\Resources\ContentPages\Pages\CreateContentPage;
use App\Filament\Resources\ContentPages\Pages\EditContentPage;
use App\Filament\Resources\ContentPages\Pages\ListContentPages;
use App\Filament\Resources\ContentPages\Schemas\ContentPageForm;
use App\Filament\Resources\ContentPages\Tables\ContentPagesTable;
use App\Models\ContentPage;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class ContentPageResource extends Resource
{
    protected static ?string $model =
        ContentPage::class;

    protected static string|BackedEnum|null
        $navigationIcon =
            Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel =
        'الصفحات التعريفية';

    protected static ?string $modelLabel =
        'صفحة تعريفية';

    protected static ?string $pluralModelLabel =
        'الصفحات التعريفية';

    protected static ?string $recordTitleAttribute =
        'title';

    protected static ?int $navigationSort = 9;

    protected static bool $hasTitleCaseModelLabel =
        false;

    public static function form(
        Schema $schema
    ): Schema {
        return ContentPageForm::configure(
            $schema
        );
    }

    public static function table(
        Table $table
    ): Table {
        return ContentPagesTable::configure(
            $table
        );
    }

    /**
     * عرض عدد الصفحات بجانب القائمة.
     */
    public static function getNavigationBadge(): ?string
    {
        return (string) ContentPage::query()
            ->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'info';
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' =>
                ListContentPages::route('/'),

            'create' =>
                CreateContentPage::route('/create'),

            'edit' =>
                EditContentPage::route(
                    '/{record}/edit'
                ),
        ];
    }
}