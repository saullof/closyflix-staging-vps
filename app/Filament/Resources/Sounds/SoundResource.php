<?php

namespace App\Filament\Resources\Sounds;

use App\Filament\Resources\Sounds\Pages\CreateSound;
use App\Filament\Resources\Sounds\Pages\EditSound;
use App\Filament\Resources\Sounds\Pages\ListSounds;
use App\Filament\Resources\Sounds\Pages\ViewSound;
use App\Filament\Resources\Sounds\Pages\ViewSoundAttachments;
use App\Filament\Traits\ResolvesRecordUrl;
use App\Model\Sound;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use UnitEnum;

class SoundResource extends Resource
{
    use ResolvesRecordUrl;

    protected static ?string $model = Sound::class;

    protected static UnitEnum|string|null $navigationGroup = 'Stories';

    protected static ?int $navigationSort = 1;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getModelLabel(): string
    {
        return __('admin.resources.sound.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.sound.plural');
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.resources.sound.sections.details'))
                ->columnSpanFull()
                ->description(__('admin.resources.sound.sections.details_descr'))
                ->schema([
                    Forms\Components\TextInput::make('title')
                        ->label(__('admin.resources.sound.fields.title'))
                        ->required()
                        ->maxLength(120)
                        ->columnSpanFull(),

                    Forms\Components\TextInput::make('artist')
                        ->label(__('admin.resources.sound.fields.artist'))
                        ->required()
                        ->maxLength(120)
                        ->columnSpanFull(),

                    Forms\Components\Textarea::make('description')
                        ->label(__('admin.resources.sound.fields.description'))
                        ->maxLength(255)
                        ->nullable()
                        ->columnSpanFull(),
                ])
                ->columns(2),

            Section::make(__('admin.resources.sound.sections.settings'))
                ->columnSpanFull()
                ->description(__('admin.resources.sound.sections.settings_descr'))
                ->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label(__('admin.resources.sound.fields.is_active'))
                        ->required()
                        ->default(true),
                ])
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->numeric()
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title')
                    ->label(__('admin.resources.sound.fields.title'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\TextColumn::make('artist')
                    ->label(__('admin.resources.sound.fields.artist'))
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('admin.resources.sound.fields.is_active'))
                    ->boolean()
                    ->sortable(),

                // If you have a virtual attribute `cover` (URL) you can show it as image
                Tables\Columns\ImageColumn::make('cover')
                    ->label(__('admin.resources.sound.fields.cover'))
                    ->circular()
                    ->size(36)
                    ->toggleable(isToggledHiddenByDefault: true),

                // Attachment counts (audio/cover/etc)
                Tables\Columns\TextColumn::make('attachments_count')
                    ->label(__('admin.resources.sound.fields.attachments'))
                    ->counts('attachments')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),

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
                        TextConstraint::make('title')->label(__('admin.resources.sound.fields.title')),
                        TextConstraint::make('artist')->label(__('admin.resources.sound.fields.artist')),
                        TextConstraint::make('description')->label(__('admin.resources.sound.fields.description')),
                        DateConstraint::make('created_at')->label(__('admin.common.created_at')),
                    ])
                    ->constraintPickerColumns(2),
            ], layout: Tables\Enums\FiltersLayout::Dropdown)
            ->deferFilters()
            ->actions([
                ActionGroup::make([
                    DeleteAction::make(),
                ])->icon('heroicon-o-ellipsis-horizontal'),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ])
            ->recordUrl(fn ($record) => static::resolveRecordUrl($record))
            ->defaultSort('created_at', 'desc')
            ->paginated([10, 25, 50]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            ViewSoundAttachments::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListSounds::route('/'),
            'create' => CreateSound::route('/create'),
            'edit'   => EditSound::route('/{record}/edit'),
            'view'   => ViewSound::route('/{record}'),
            'attachments' => ViewSoundAttachments::route('/{record}/attachments'),
        ];
    }
}
