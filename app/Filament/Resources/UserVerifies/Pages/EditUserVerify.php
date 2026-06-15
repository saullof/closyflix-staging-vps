<?php

namespace App\Filament\Resources\UserVerifies\Pages;

use App\Filament\Resources\UserVerifies\UserVerifyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserVerify extends EditRecord
{
    protected static string $resource = UserVerifyResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
