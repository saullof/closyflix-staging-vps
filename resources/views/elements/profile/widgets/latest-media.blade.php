<div class="card recent-media rounded-lg">
    <div class="card-body m-0 pb-0">
    </div>
    <h5 class="card-title pl-3 mb-0 card-title text-uppercase fs-point-85 font-weight-bold">{{__('Recent')}}</h5>
    <div class="card-body {{$recentMedia ? '' : ''}}">
        @if($recentMedia && count($recentMedia) && Auth::check())
            @foreach($recentMedia as $media)
                <a href="{{$media->path}}" rel="mswp" class="mr-1">
                    <img src="{{AttachmentHelper::getThumbnailPathForAttachmentByResolution($media, 150, 150)}}" class="rounded mb-2 mb-md-2 mb-lg-2 mb-xl-0 img-fluid">
                </a>
            @endforeach
        @else
            <p class="m-0">{{__('Latest media not available.')}}</p>
        @endif

    </div>
</div>
