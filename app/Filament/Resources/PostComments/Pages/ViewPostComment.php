<?php

namespace App\Filament\Resources\PostComments\Pages;

use App\Filament\Resources\PostComments\PostCommentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewPostComment extends ViewRecord
{
    protected static string $resource = PostCommentResource::class;

    protected function getActions(): array
    {
        return [EditAction::make()];
    }
}
