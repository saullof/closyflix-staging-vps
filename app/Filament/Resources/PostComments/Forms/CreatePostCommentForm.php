<?php

namespace App\Filament\Resources\PostComments\Forms;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;

class CreatePostCommentForm
{
    public static function schema($postId = null): array
    {
        return [
            Textarea::make('message')
                ->label(__('admin.resources.post_comment.fields.message'))
                ->required()
                ->minLength(1),

            Select::make('user_id')
                ->label(__('admin.resources.post_comment.fields.author'))
                ->relationship('author', 'username')
                ->searchable()
                ->required()
                ->preload(true),

            Select::make('post_id')
                ->label(__('admin.resources.post_comment.fields.post_id'))
                ->relationship('post', 'id')
                ->searchable()
                ->default($postId)
                ->preload(true)
                ->required(),
        ];
    }
}
