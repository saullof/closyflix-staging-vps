<?php

namespace App\Filament\Resources\PostComments\Pages;

use App\Filament\Resources\PostComments\PostCommentResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePostComment extends CreateRecord
{
    protected static string $resource = PostCommentResource::class;
}
