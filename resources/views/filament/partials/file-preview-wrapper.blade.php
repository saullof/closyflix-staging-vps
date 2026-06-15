@php
    $files = is_array($record?->files)
        ? $record->files
        : (is_string($record?->files) ? json_decode($record->files, true) : []);
@endphp

<div class="space-y-2">
    {{-- Label (just like other inputs) --}}
    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ __('admin.common.files') }}
    </label>

    @if (!empty($files))
        <div class="flex overflow-x-auto gap-4 py-2">
            @foreach ($files as $file)
                <div class="w-150 shrink-0">
                    @include('filament.partials.file-preview-box', [
                        'asset' => $file,
                        'attachment' => $record,
                    ])
                </div>
            @endforeach
        </div>
    @else
        <span class="text-sm text-gray-500">{{ __('No attachments available.') }}</span>
    @endif
</div>
