<?php

namespace App\Filament\Traits;

use Illuminate\Database\Eloquent\Model;

trait ResolvesRecordUrl
{
    public static function resolveRecordUrl(Model $record): ?string
    {
        $user = auth()->user();

        // Prefer edit if user can update; otherwise fall back to view
        if (array_key_exists('edit', static::getPages()) && $user?->can('update', $record)) {
            return static::getUrl('edit', ['record' => $record]);
        }

        if (array_key_exists('view', static::getPages()) && $user?->can('view', $record)) {
            return static::getUrl('view', ['record' => $record]);
        }

        // No allowed destination → disable row click
        return null;
    }
}
