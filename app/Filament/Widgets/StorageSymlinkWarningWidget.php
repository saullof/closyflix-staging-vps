<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Artisan;
use Filament\Notifications\Notification;

class StorageSymlinkWarningWidget extends Widget
{
    public bool $symlinkFixed = false;

    protected string $view = 'filament.widgets.storage-symlink-warning-widget';

    public function getColumnSpan(): int|string|array
    {
        return 2; // Or 2 if dashboard uses 2-column layout
    }

    public function createSymlink(): void
    {
        Artisan::call('storage:link');

        Notification::make()
            ->title('Symlink created successfully.')
            ->success()
            ->send();

        $this->symlinkFixed = true;
    }

    public static function canView(): bool
    {
        $link = public_path('storage');
        $target = storage_path('app/public');

        // Widget should show if:
        // - the link doesn't exist
        // - the resolved path of the link doesn't match the expected target
        return !file_exists($link) || realpath($link) !== realpath($target);
    }

    public function getViewData(): array
    {
        return []; // no variables needed if logic is in canView()
    }
}
