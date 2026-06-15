<?php

namespace App\Filament\Resources\UserVerifies;

use App\Filament\Resources\UserVerifies\Pages\CreateUserVerify;
use App\Filament\Resources\UserVerifies\Pages\EditUserVerify;
use App\Filament\Resources\UserVerifies\Pages\ListUserVerifies;
use App\Filament\Resources\UserVerifies\Pages\ViewUserVerify;
use App\Filament\Traits\ResolvesRecordUrl;
use App\Model\UserVerify;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Filters\QueryBuilder;
use Filament\Tables\Filters\QueryBuilder\Constraints\DateConstraint;
use Filament\Tables\Filters\QueryBuilder\Constraints\TextConstraint;
use Filament\Tables\Table;
use UnitEnum;
use Filament\Schemas\Components\View as ViewComponent;

class UserVerifyResource extends Resource
{
    use ResolvesRecordUrl;

    protected static ?string $model = UserVerify::class;

    protected static ?int $navigationSort = 9;

    protected static UnitEnum|string|null $navigationGroup = 'UserVerifies';

    public static function getModelLabel(): string
    {
        return __('admin.resources.user_verify.label');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.resources.user_verify.plural');
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('admin.resources.user_verify.navigation_badge_tooltip');
    }

    public static function getNavigationBadge(): ?string
    {
        $count = UserVerify::where('status', UserVerify::REQUESTED_STATUS)->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
                Section::make(__('admin.resources.user_verify.sections.verification_details'))
                    ->columnSpanFull()
                    ->description(__('admin.resources.user_verify.sections.verification_details_descr'))
                    ->schema([

                        ViewComponent::make('filament.partials.file-preview-wrapper')
                            ->columnSpanFull()
                            ->viewData([
                                'record' => UserVerify::find(
                                    request()->route('record')
                                ),
                            ]),

                        Select::make('user_id')
                            ->label(__('admin.resources.user_verify.fields.user_id'))
                            ->relationship('user', 'username')
                            ->searchable()
                            ->required()
                            ->preload(true),
                        Select::make('status')
                            ->label(__('admin.resources.user_verify.fields.status'))
                            ->required()
                            ->options([
                                UserVerify::REQUESTED_STATUS => ucfirst(UserVerify::REQUESTED_STATUS),
                                UserVerify::REJECTED_STATUS => ucfirst(UserVerify::REJECTED_STATUS),
                                UserVerify::APPROVED_STATUS => ucfirst(UserVerify::APPROVED_STATUS),
                            ])
                            ->default(UserVerify::REQUESTED_STATUS),
                        TextInput::make('rejectionReason')
                            ->label(__('admin.resources.user_verify.fields.rejectionReason'))
                            ->maxLength(191)
                            ->columnSpanFull()
                            ->default(null),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.username')
                    ->label(__('admin.resources.user_verify.fields.user_id'))
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('admin.resources.user_verify.fields.status'))
                    ->badge()
                    ->color('success')
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->color(fn ($state) => match ($state) {
                        UserVerify::APPROVED_STATUS => 'success',
                        UserVerify::REQUESTED_STATUS => 'warning',
                        UserVerify::REJECTED_STATUS => 'danger',
                        default => 'gray',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('rejectionReason')
                    ->label(__('admin.resources.user_verify.fields.rejectionReason'))
                    ->searchable()
                    ->limit(50),
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
                        TextConstraint::make('user.username')->label(__('admin.resources.user_verify.fields.user_id')),
                        TextConstraint::make('status')->label(__('admin.resources.user_verify.fields.status')),
                        TextConstraint::make('rejectionReason')->label(__('admin.resources.user_verify.fields.rejectionReason')),
                        DateConstraint::make('created_at')->label(__('admin.common.created_at')),
                    ])
                    ->constraintPickerColumns(2),
            ], layout: Tables\Enums\FiltersLayout::Dropdown)
            ->deferFilters()
            ->actions([
                ActionGroup::make([
                    Action::make('profile_url')
                        ->label(__('admin.resources.user_verify.actions.profile_url'))
                        ->icon('heroicon-o-globe-alt')
                        ->url(fn ($record) => route('profile', ['username' => $record->user->username]))
                        ->openUrlInNewTab()
                        ->color('info'),
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
            'index' => ListUserVerifies::route('/'),
            'create' => CreateUserVerify::route('/create'),
            'edit' => EditUserVerify::route('/{record}/edit'),
            'view' => ViewUserVerify::route('/{record}'),
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
//            Pages\ViewUserVerify::class,
//            Pages\EditUserVerify::class,
//            Pages\ViewUserVerifyAttachments::class,
        ]);
    }
}
