<?php

namespace App\Filament\Resources\PublicPages;

use App\Filament\Resources\PublicPages\Pages\CreatePublicPage;
use App\Filament\Resources\PublicPages\Pages\EditPublicPage;
use App\Filament\Resources\PublicPages\Pages\ListPublicPages;
use App\Filament\Resources\PublicPages\Pages\ViewPublicPage;
use App\Filament\Traits\ResolvesRecordUrl;
use App\Model\PublicPage;
use App\Providers\LocalesServiceProvider;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\BooleanConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\NumberConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Facades\File;
use UnitEnum;

class PublicPageResource extends Resource
{
    use ResolvesRecordUrl;

    protected static ?string $model = PublicPage::class;

    protected static UnitEnum|string|null $navigationGroup = 'PublicPages';

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'content/site-pages';

    public static function getModelLabel(): string
    {
        return __('admin.resources.public_page.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.public_page.plural');
    }

    public static function form(Schema $schema): Schema
    {
        $locales = LocalesServiceProvider::getAvailableLanguagesFallbackFirst(); // or getAvailableLocales()
        $fallback = config('app.fallback_locale', $locales[0] ?? 'en');

        return $schema
            ->columns(3)
            ->components([
                Section::make(__('admin.resources.public_page.sections.page_details'))
                    ->description(__('admin.resources.public_page.sections.page_details_descr'))
                    ->schema([
                        TextInput::make('slug')
                            ->label(__('admin.resources.public_page.fields.slug'))
                            ->required()
                            ->maxLength(191)
                            ->helperText(__('admin.resources.public_page.fields.slug_helper')),

                        Tabs::make('Translations')
                            ->columnSpanFull()
                            ->tabs(
                                collect($locales)->map(function (string $locale) use ($fallback) {
                                    return Tab::make(strtoupper($locale))
                                        ->schema([
                                            TextInput::make("title.$locale")
                                                ->label(__('admin.resources.public_page.fields.title'))
                                                ->required($locale === $fallback)
                                                ->maxLength(191)
                                                ->helperText(__('admin.resources.public_page.fields.title_helper'))
                                                // ensure nested key is always a string:
                                                ->formatStateUsing(fn ($state) => is_string($state) ? $state : '')
                                                ->dehydrateStateUsing(fn ($state) => is_string($state) ? $state : ''),

                                            TextInput::make("short_title.$locale")
                                                ->label(__('admin.resources.public_page.fields.short_title'))
                                                ->maxLength(191)
                                                ->default('')
                                                ->hintIcon('heroicon-o-question-mark-circle', tooltip: __('admin.resources.public_page.fields.short_title_helper'))
                                                ->formatStateUsing(fn ($state) => is_string($state) ? $state : '')
                                                ->dehydrateStateUsing(fn ($state) => is_string($state) ? $state : ''),

                                            RichEditor::make("content.$locale")
                                                ->label(__('admin.resources.public_page.fields.content'))
                                                ->columnSpanFull()
                                                ->toolbarButtons([
                                                    'h3', 'bold', 'italic', 'underline', 'strike', 'link',
                                                    'bulletList', 'orderedList', 'blockquote', 'codeBlock',
                                                ])
                                                ->formatStateUsing(fn ($state) => is_string($state) ? $state : '')
                                                ->dehydrateStateUsing(fn ($state) => is_string($state) ? $state : ''),
                                        ]);
                                })->all()
                            ),
                    ])
                    ->columnSpan(2),

                Section::make(__('admin.resources.public_page.sections.display_settings'))
                    ->description(__('admin.resources.public_page.sections.display_settings_descr'))
                    ->schema([
                        Toggle::make('shown_in_footer')
                            ->label(__('admin.resources.public_page.fields.shown_in_footer'))
                            ->helperText(__('admin.resources.public_page.fields.shown_in_footer_helper')),

                        Toggle::make('is_tos')
                            ->label(__('admin.resources.public_page.fields.is_tos'))
                            ->helperText(__('admin.resources.public_page.fields.is_tos_helper')),

                        Toggle::make('is_privacy')
                            ->label(__('admin.resources.public_page.fields.is_privacy'))
                            ->helperText(__('admin.resources.public_page.fields.is_privacy_helper')),

                        Toggle::make('show_last_update_date')
                            ->label(__('admin.resources.public_page.fields.show_last_update_date'))
                            ->helperText(__('admin.resources.public_page.fields.show_last_update_date_helper')),

                        TextInput::make('page_order')
                            ->label(__('admin.resources.public_page.fields.page_order'))
                            ->required()
                            ->numeric()
                            ->default(0)
                            ->helperText(__('admin.resources.public_page.fields.page_order_helper')),
                    ])
                    ->columnSpan(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin.resources.public_page.fields.title'))
                    ->state(fn ($record) => $record->translated('title'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('admin.resources.public_page.fields.slug'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('page_order')
                    ->label(__('admin.resources.public_page.fields.page_order'))
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('shown_in_footer')
                    ->label(__('admin.resources.public_page.fields.shown_in_footer'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_tos')
                    ->label(__('admin.resources.public_page.fields.is_tos'))
                    ->boolean(),
                Tables\Columns\IconColumn::make('is_privacy')
                    ->label(__('admin.resources.public_page.fields.is_privacy'))
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('admin.common.updated_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('slug')->label(__('admin.resources.public_page.fields.slug')),
                        NumberConstraint::make('page_order')->label(__('admin.resources.public_page.fields.page_order')),
                        BooleanConstraint::make('shown_in_footer')->label(__('admin.resources.public_page.fields.shown_in_footer')),
                        BooleanConstraint::make('is_tos')->label(__('admin.resources.public_page.fields.is_tos')),
                        BooleanConstraint::make('is_privacy')->label(__('admin.resources.public_page.fields.is_privacy')),
                        BooleanConstraint::make('show_last_update_date')->label(__('admin.resources.public_page.fields.show_last_update_date')),
                        DateConstraint::make('created_at')->label(__('admin.common.created_at')),
                    ])
                    ->constraintPickerColumns(2),
            ], layout: Tables\Enums\FiltersLayout::Dropdown)
            ->deferFilters()
            ->actions([
                ActionGroup::make([
                    Action::make('page_url')
                        ->label(__('admin.resources.public_page.fields.page_url'))
                        ->icon('heroicon-o-globe-alt')
                        ->url(fn ($record) => route('pages.get', ['slug' => $record->slug]))
                        ->openUrlInNewTab()
                        ->color('info'),
                    DeleteAction::make()
                        ->hidden(fn ($record) => (bool) ($record->is_tos || $record->is_privacy)),
                ]),
            ])
            ->toolbarActions([
//                DeleteBulkAction::make(),
            ])
            ->recordUrl(fn ($record) => static::resolveRecordUrl($record))
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublicPages::route('/'),
            'create' => CreatePublicPage::route('/create'),
            'edit' => EditPublicPage::route('/{record}/edit'),
            'view' => ViewPublicPage::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
//            Pages\ViewPublicPage::class,
//            Pages\EditPublicPage::class,
        ]);
    }
}
