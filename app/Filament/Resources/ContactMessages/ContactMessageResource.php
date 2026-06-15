<?php

namespace App\Filament\Resources\ContactMessages;

use App\Filament\Resources\ContactMessages\Pages\CreateContactMessage;
use App\Filament\Resources\ContactMessages\Pages\EditContactMessage;
use App\Filament\Resources\ContactMessages\Pages\ListContactMessages;
use App\Filament\Resources\ContactMessages\Pages\ViewContactMessage;
use App\Filament\Traits\ResolvesRecordUrl;
use App\Model\ContactMessage;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Forms\Components as Forms;

class ContactMessageResource extends Resource
{
    use ResolvesRecordUrl;

    protected static ?string $model = ContactMessage::class;

    protected static ?int $navigationSort = 22;

    protected static string|UnitEnum|null $navigationGroup = 'ContactMessages';

    public static function getModelLabel(): string
    {
        return __('admin.resources.contact_message.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.contact_message.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    Forms\TextInput::make('email')
                        ->label(__('admin.resources.contact_message.fields.email'))
                        ->email()
                        ->required()
                        ->maxLength(191),

                    Forms\TextInput::make('subject')
                        ->label(__('admin.resources.contact_message.fields.subject'))
                        ->required()
                        ->maxLength(191),

                    Forms\Textarea::make('message')
                        ->label(__('admin.resources.contact_message.fields.message'))
                        ->required()
                        ->rows(4)
                        ->columnSpanFull(),

                    Forms\Toggle::make('is_replied')
                        ->label(__('admin.resources.contact_message.fields.is_replied'))
                        ->helperText(__('admin.resources.contact_message.helpers.is_replied'))
                        ->live()
                        ->dehydrated(false)
                        ->afterStateHydrated(fn (Forms\Toggle $component, ?ContactMessage $record) => $component->state((bool) $record?->replied_at))
                        ->afterStateUpdated(function (Set $set, bool $state): void {
                            $set('replied_at', $state ? now()->toDateTimeString() : null);
                            $set('replied_by', $state ? Auth::id() : null);
                        }),

                    Forms\Hidden::make('replied_at'),

                    Forms\Hidden::make('replied_by'),

                    Forms\Placeholder::make('reply_details')
                        ->label(__('admin.resources.contact_message.fields.reply_details'))
                        ->content(fn (?ContactMessage $record): string => $record?->replied_at
                            ? __('admin.resources.contact_message.reply_details', [
                                'date' => $record->replied_at->format('Y-m-d H:i'),
                                'user' => $record->replier->username ?? __('admin.resources.contact_message.status.unknown_replier'),
                            ])
                            : __('admin.resources.contact_message.status.pending')),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label(__('admin.resources.contact_message.fields.email'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('subject')
                    ->label(__('admin.resources.contact_message.fields.subject'))
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('reply_status')
                    ->label(__('admin.resources.contact_message.fields.status'))
                    ->state(fn (ContactMessage $record) => $record->replied_at
                        ? __('admin.resources.contact_message.status.replied')
                        : __('admin.resources.contact_message.status.pending'))
                    ->badge()
                    ->color(fn (ContactMessage $record) => $record->replied_at ? 'success' : 'warning'),
                Tables\Columns\TextColumn::make('replier.username')
                    ->label(__('admin.resources.contact_message.fields.replied_by'))
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        TextConstraint::make('email')->label(__('admin.resources.contact_message.fields.email')),
                        TextConstraint::make('subject')->label(__('admin.resources.contact_message.fields.subject')),
                        TextConstraint::make('message')->label(__('admin.resources.contact_message.fields.message')),
                        DateConstraint::make('replied_at')->label(__('admin.resources.contact_message.fields.replied_at')),
                        DateConstraint::make('created_at')->label(__('admin.common.created_at')),
                    ])
                    ->constraintPickerColumns(2),
                TernaryFilter::make('replied_at')
                    ->label(__('admin.resources.contact_message.filters.reply_status'))
                    ->trueLabel(__('admin.resources.contact_message.status.replied'))
                    ->falseLabel(__('admin.resources.contact_message.status.pending'))
                    ->nullable(),
            ], layout: Tables\Enums\FiltersLayout::Dropdown)
            ->deferFilters()
            ->actions([
                ActionGroup::make([
                    Action::make('mark_replied')
                        ->label(__('admin.resources.contact_message.actions.mark_replied'))
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn (ContactMessage $record) => !$record->replied_at)
                        ->requiresConfirmation()
                        ->action(fn (ContactMessage $record) => $record->update([
                            'replied_at' => now(),
                            'replied_by' => Auth::id(),
                        ])),
                    Action::make('mark_unreplied')
                        ->label(__('admin.resources.contact_message.actions.mark_unreplied'))
                        ->icon('heroicon-o-arrow-path')
                        ->color('warning')
                        ->visible(fn (ContactMessage $record) => (bool) $record->replied_at)
                        ->requiresConfirmation()
                        ->action(fn (ContactMessage $record) => $record->update([
                            'replied_at' => null,
                            'replied_by' => null,
                        ])),
                    DeleteAction::make(),
                ])
                    ->icon('heroicon-o-ellipsis-horizontal')
                    ->iconSize('lg'),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContactMessages::route('/'),
            'create' => CreateContactMessage::route('/create'),
            'edit' => EditContactMessage::route('/{record}/edit'),
            'view' => ViewContactMessage::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
//            Pages\ViewContactMessage::class,
//            Pages\EditContactMessage::class,
        ]);
    }
}
