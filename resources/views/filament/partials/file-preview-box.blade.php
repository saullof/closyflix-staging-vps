@php
    if (!isset($asset) && isset($attachment)) {
        $asset = $attachment->path ?? '';
    }

    if (!empty($asset)) {
        $parts = explode('.', $asset);
        $extension = AttachmentHelper::getAttachmentType(end($parts));
        $asset = Storage::url($asset);
    } else {
        $extension = 'unknown';
    }
@endphp

@if(!empty($asset))
    <a href="{{ $asset }}" target="_blank">
        @switch($extension)
            @case('document')
                <img src="{{ asset('/img/pdf-preview.svg') }}" class="admin-id-asset"/>
                @break
            @case('image')
                <img src="{{ $asset }}" class="admin-id-asset"/>
                @break
            @case('video')
                <video class="video-preview w-75" src="{{ $asset }}#t=0.001" controls controlsList="nodownload" preload="metadata"></video>
                @break
            @case('audio')
                <audio class="video-preview w-75" src="{{ $asset }}#t=0.001" controls controlsList="nodownload" preload="metadata"></audio>
                @break
        @endswitch
    </a>
@else
    <span class="text-sm text-gray-500">No preview available</span>
@endif
