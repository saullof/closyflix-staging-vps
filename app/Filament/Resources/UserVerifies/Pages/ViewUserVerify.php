<?php

namespace App\Filament\Resources\UserVerifies\Pages;

use App\Filament\Resources\UserVerifies\UserVerifyResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewUserVerify extends ViewRecord
{
    protected static string $resource = UserVerifyResource::class;

    protected function getActions(): array
    {
        return [EditAction::make()];
    }
}
