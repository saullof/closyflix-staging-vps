<?php

namespace App\Filament\Resources\UserMessages\Pages;

use App\Filament\Resources\UserMessages\UserMessageResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserMessage extends ViewRecord
{
    protected static string $resource = UserMessageResource::class;

    protected function getActions(): array
    {
        return [EditAction::make()];
    }
}
