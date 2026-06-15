<?php

namespace App\Filament\Resources\Attachments\Forms;

use App\Model\Attachment;
use App\Providers\AttachmentServiceProvider;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class CreateAttachmentForm
{
    public static function schema(
        ?int $postId = null,
        ?int $userMessageId = null,
        ?int $storyId = null,
        ?int $soundId = null,
        ?int $userId = null,
        ?int $reelId = null,
    ): array {
        $uuid = str_replace('-', '', Str::uuid()->toString());

        return [
            TextInput::make('id')
                ->label(__('admin.resources.attachment.fields.id'))
                ->helperText(__('admin.resources.attachment.help.id'))
                ->required()
                ->default($uuid),

            TextInput::make('filename')
                ->label(__('admin.resources.attachment.fields.filename'))
                ->required()
                ->reactive()
                ->default("assets/attachments/$uuid"),

            FileUpload::make('file')
                ->label(__('admin.resources.attachment.fields.file'))
                ->directory('assets/attachments')
                ->visibility(AttachmentServiceProvider::getAdminFileUploadVisibility())
                ->image()
                ->multiple(false)
                ->imagePreviewHeight('80px')
                ->maxSize(AttachmentServiceProvider::getUploadMaxFilesize())
                ->acceptedFileTypes(AttachmentServiceProvider::extensionsToMimeTypes(getSetting('media.allowed_file_extensions')))
                ->columnSpanFull()
                ->required(fn (string $operation) => $operation === 'create')
                ->getUploadedFileNameForStorageUsing(fn (TemporaryUploadedFile $file, $get) => $get('id').'.'.$file->getClientOriginalExtension())
                ->afterStateUpdated(function ($state, callable $set, callable $get) {
                    if ($state instanceof TemporaryUploadedFile) {
                        $filename = $get('filename');
                        $filenameWithoutExt = preg_replace('/\.[^\.]+$/', '', $filename);
                        $extension = $state->getClientOriginalExtension();
                        $set('filename', "$filenameWithoutExt.$extension");
                    }
                }),

            Select::make('driver')
                ->label(__('admin.resources.attachment.fields.driver'))
                ->options([
                    Attachment::PUBLIC_DRIVER => 'Public (Local)',
                    Attachment::S3_DRIVER => 'Amazon S3',
                    Attachment::DO_DRIVER => 'DigitalOcean Spaces',
                    Attachment::WAS_DRIVER => 'Wasabi',
                    Attachment::MINIO_DRIVER => 'MinIO',
                    Attachment::PUSHR_DRIVER => 'Pushr',
                ])
                ->required()
                ->default(AttachmentServiceProvider::getStorageProviderID(getSetting('storage.driver')))
                ->helperText(__('admin.resources.attachment.help.driver'))
                ->columnSpanFull(),

            Select::make('type')
                ->label(__('admin.resources.attachment.fields.type'))
                ->required()
                ->options(
                    collect(explode(',', trim(getSetting('media.allowed_file_extensions'))))
                        ->mapWithKeys(fn ($item) => [trim($item) => strtoupper(trim($item))])
                        ->toArray()
                ),

            Select::make('user_id')
                ->label(__('admin.resources.attachment.fields.user_id'))
                ->relationship('user', 'username')
                ->searchable()
                ->required()
                ->preload(true)
                ->default($userId),

            Select::make('post_id')
                ->label(__('admin.resources.attachment.fields.post_id'))
                ->relationship('post', 'id')
                ->searchable()
                ->default($postId)
                ->preload(true),

            Select::make('message_id')
                ->label(__('admin.resources.attachment.fields.message_id'))
                ->relationship('message', 'id')
                ->searchable()
                ->default($userMessageId)
                ->preload(true),

            Select::make('story_id')
                ->label(__('admin.resources.attachment.fields.story_id'))
                ->relationship('story', 'id')
                ->searchable()
                ->default($storyId)
                ->preload(true),

            Select::make('reel_id')
                ->label(__('admin.resources.attachment.fields.reel_id'))
                ->relationship('reel', 'id')
                ->searchable()
                ->default($reelId)
                ->preload(true),

            Select::make('sound_id')
                ->label(__('admin.resources.attachment.fields.sound_id'))
                ->relationship('sound', 'title')
                ->searchable()
                ->default($soundId)
                ->preload(true),

            Select::make('payment_request_id')
                ->label(__('admin.resources.attachment.fields.payment_request_id'))
                ->relationship('paymentRequest', 'id')
                ->searchable()
                ->default(null)
                ->preload(true),

            TextInput::make('coconut_id')
                ->label(__('admin.resources.attachment.fields.coconut_id'))
                ->maxLength(191)
                ->default(null),

            Toggle::make('has_thumbnail')->hidden(),
            Toggle::make('has_blurred_preview')->hidden(),
        ];
    }
}
