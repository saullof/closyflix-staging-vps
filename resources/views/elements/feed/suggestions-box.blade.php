<div class="suggestions-box{{$isMobile ? '-mobile':''}} border rounded-lg px-2 {{isset($isMobile) ? 'pt-3 pb-1' : 'py-4'}}" id="{{$id}}">
    <div class="d-flex justify-content-between suggestions-header mb-3 px-1">
        <span class="card-title pl-2 mb-0 text-uppercase fs-point-85 font-weight-bold">{{$title}}</span>
        <div class="d-flex">
            <div class="d-flex">
            </div>
            <div class="d-flex">
                @if(!$hideControls)
                    <span class="pointer-cursor h-pill h-pill-primary rounded" data-toggle="tooltip" data-placement="top" title="{{__('Free account only')}}" onclick="SuggestionsSlider.loadSuggestions({'free':true {{isset($isMobile) ? ", 'isMobile': true" : ''}}});">
                        @include('elements.icon',['icon'=>'pricetag-outline','centered'=>false])
                    </span>
                    <span class=" pointer-cursor h-pill h-pill-primary rounded" data-toggle="tooltip" data-placement="top" title="{{__('Refresh suggestions')}}" onclick="SuggestionsSlider.loadSuggestions({{isset($isMobile) ? "{'isMobile': true}" : ""}})">
                       @include('elements.icon',['icon'=>'refresh','centered'=>false])
                    </span>
                @endif
            </div>
        </div>
    </div>
    @include('elements.feed.suggestions-wrapper',['profiles'=>$profiles, 'perPage' => $perPage])
</div>
