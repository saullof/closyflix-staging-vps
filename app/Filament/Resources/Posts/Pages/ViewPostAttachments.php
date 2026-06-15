<?php

namespace App\Filament\Resources\Posts\Pages;

use App\Filament\Resources\Attachments\Forms\CreateAttachmentForm;
use App\Filament\Resources\Posts\PostResource;
use App\Model\Attachment;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Contracts\Support\Htmlable;
use BackedEnum;
use Illuminate\Support\Facades\Gate;

class ViewPostAttachments extends ManageRelatedRecords
{
    public static function canAccess(array $parameters = []): bool
    {
        $parent = $parameters['record'] ?? null;

        return $parent
            && (Gate::allows('view', $parent) || Gate::allows('update', $parent));
    }

    protected static string $resource = PostResource::class;

    protected static string $relationship = 'attachments';

    protected static BackedEnum|string|null $navigationIcon = 'heroicon-o-paper-clip';

    public function getTitle(): string | Htmlable
    {
        return __('admin.resources.attachment.plural');
    }

    public function getBreadcrumb(): string
    {
        return __('admin.resources.attachment.plural');
    }

    public static function getNavigationLabel(): string
    {
        return __('admin.resources.attachment.plural');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components(
                CreateAttachmentForm::schema((int) $this->getRecord()->getKey())
            );
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make()
                ->columnSpanFull()
                ->schema([
                    TextEntry::make('path')
                        ->label(__('admin.resources.attachment.fields.open'))
                        ->url(fn ($record) => $record->path)
                        ->openUrlInNewTab()
                        ->icon('heroicon-m-arrow-top-right-on-square')
                        ->formatStateUsing(fn () => __('admin.resources.attachment.fields.open')),

                    TextEntry::make('user.email')
                        ->label(__('admin.resources.attachment.fields.user_id')),

                    TextEntry::make('driver')
                        ->label(__('admin.resources.attachment.fields.driver'))
                        ->formatStateUsing(fn ($state) => Attachment::getDriverName($state))
                        ->badge()
                        ->color('success'),

                    TextEntry::make('type')
                        ->label(__('admin.resources.attachment.fields.type')),

                    TextEntry::make('created_at')
                        ->dateTime()
                        ->label(__('admin.common.created_at')),
                ])
                ->columns(1),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('message')
            ->columns([
                Tables\Columns\TextColumn::make('path')
                    ->label(__('admin.resources.attachment.fields.open'))
                    ->url(fn ($record) => $record->path)
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn ($state) => __('admin.resources.attachment.fields.open')),
                Tables\Columns\TextColumn::make('user.username')
                    ->label(__('admin.resources.attachment.fields.user_id'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('driver')
                    ->label(__('admin.resources.attachment.fields.driver'))
                    ->formatStateUsing(fn ($state) => Attachment::getDriverName($state))
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('admin.resources.attachment.fields.type'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->label(__('admin.common.created_at'))
                    ->searchable()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                CreateAction::make()
                    ->label(__('admin.common.create'))
                    ->modalHeading(__('admin.common.create')),
            ])
            ->actions([
                ViewAction::make()
                    ->label(__('admin.common.view'))
                    ->modalHeading(__('admin.common.view')),
                DeleteAction::make()
                    ->label(__('admin.common.delete'))
                    ->modalHeading(__('admin.common.delete')),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->paginated([10, 25, 50]);
    }
}
