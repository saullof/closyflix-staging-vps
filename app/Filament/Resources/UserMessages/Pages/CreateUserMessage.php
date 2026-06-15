<?php

namespace App\Filament\Resources\UserMessages\Pages;

use App\Filament\Resources\UserMessages\UserMessageResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUserMessage extends CreateRecord
{
    protected static string $resource = UserMessageResource::class;
}
