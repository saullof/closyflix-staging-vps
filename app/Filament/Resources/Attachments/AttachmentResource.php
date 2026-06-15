<?php

namespace App\Filament\Resources\Attachments;

use App\Filament\Resources\Attachments\Forms\CreateAttachmentForm;
use App\Filament\Resources\Attachments\Pages\CreateAttachment;
use App\Filament\Resources\Attachments\Pages\EditAttachment;
use App\Filament\Resources\Attachments\Pages\ListAttachments;
use App\Filament\Resources\Attachments\Pages\ViewAttachment;
use App\Filament\Traits\ResolvesRecordUrl;
use App\Model\Attachment;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use Filament\Pages\Enums\SubNavigationPosition;
use Illuminate\Support\Facades\Gate;
use UnitEnum;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;

class AttachmentResource extends Resource
{
    use ResolvesRecordUrl;

    protected static ?string $model = Attachment::class;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Start;

    protected static string|UnitEnum|null $navigationGroup = 'Attachments';

    protected static ?int $navigationSort = 0;

    public static function getModelLabel(): string
    {
        return __('admin.resources.attachment.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.attachment.plural');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make(__('admin.resources.attachment.sections.attachment_details'))
                ->columnSpanFull()
                ->description(__('admin.resources.attachment.sections.attachment_details_descr'))
                ->schema(CreateAttachmentForm::schema())
                ->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label(__('admin.resources.attachment.fields.id'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.username')
                    ->label(__('admin.resources.attachment.fields.user_id'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('path')
                    ->label(__('admin.resources.attachment.fields.file'))
                    ->url(fn ($record) => $record->path)
                    ->icon('heroicon-m-arrow-top-right-on-square')
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn ($state) => __('admin.resources.attachment.fields.open')),
                Tables\Columns\TextColumn::make('driver')
                    ->label(__('admin.resources.attachment.fields.driver'))
                    ->formatStateUsing(fn ($state) => Attachment::getDriverName($state))
                    ->badge()
                    ->color('success')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('admin.resources.attachment.fields.type'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('admin.common.created_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                QueryBuilder::make()
                    ->constraints([
                        TextConstraint::make('id')->label(__('admin.resources.attachment.fields.id')),
                        TextConstraint::make('type')->label(__('admin.resources.attachment.fields.type')),
                        TextConstraint::make('user.username')->label(__('admin.resources.attachment.fields.user')),
                        TextConstraint::make('message.id')->label(__('admin.resources.attachment.fields.message')),
                        TextConstraint::make('post.id')->label(__('admin.resources.attachment.fields.post')),
                        DateConstraint::make('created_at')->label(__('admin.common.created_at')),
                    ])
                    ->constraintPickerColumns(2),
            ], layout: Tables\Enums\FiltersLayout::Dropdown)
            ->deferFilters()
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
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
            'index' => ListAttachments::route('/'),
            'create' => CreateAttachment::route('/create'),
            'edit' => EditAttachment::route('/{record}/edit'),
            'view' => ViewAttachment::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
//            Pages\ViewAttachment::class,
//            Pages\EditAttachment::class,
        ]);
    }
}
