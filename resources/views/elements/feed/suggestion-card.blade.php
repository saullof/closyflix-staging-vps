<div class="suggestion-box {{isset($classes) ? $classes : ''}} card text-white border-0 {{$cardRadius}}" data-memberuserid="{{$profile->id}}">
    <div style="background: url({{$profile->cover}});" class="card-img suggestion-header-bg {{$cardRadius}}"></div>
    <div class="card-img-overlay p-0">
        <div class="h-100 w-100 p-0 m-0 position-absolute z-index-0">
            <div class="h-50">
            </div>
            <div class="h-50 w-100 half-bg d-flex {{str_replace('rounded-', 'rounded-bottom-', $cardRadius)}}"></div>
        </div>
        <div class="card-text w-100 h-100 d-flex">

            <div class="d-flex align-items-center justify-content-center pl-3 pr-2 z-index-3">
                <img src="{{$profile->avatar}}" class="avatar rounded-circle" alt="{{$profile->username}}{{__("'s avatar")}}" />
            </div>

            <div class="w-100 z-index-3 text-truncate">
                <div class="h-50 d-flex flex-row-reverse pr-1">
                    @if(isset($isListMode) && ($isListManageable))
                        <span class="h-pill h-pill-media h-pill-media--light rounded mt-1 suggestion-card-btn" data-toggle="tooltip" data-placement="bottom" title="" onclick="Lists.showListMemberRemoveModal({{$profile->id}})" data-original-title="{{__('Delete')}}">
                            @include('elements.icon',['icon'=>'trash-outline','variant'=>'medium'])
                        </span>
                    @endif
                </div>
                <div class="h-50 w-100 z-index-3 d-flex flex-column mt-1 text-truncate pr-2">
                    <div class="m-0 h6 text-truncate"><a href="{{route('profile',['username'=>$profile->username])}}" class="text-white d-flex align-items-center">{{$profile->name}}
                        @if(GenericHelper::isUserVerified($profile))
                            <span data-toggle="tooltip" data-placement="top" title="{{__('Verified user')}}">
                                @include('elements.icon',['icon'=>'verified','centered'=>true,'classes'=>'ml-1'])
                            </span>
                        @endif
                        </a>
                    </div>
                    <div class="m-0 text-truncate small"><span>@</span><a href="{{route('profile',['username'=>$profile->username])}}" class="text-white">{{$profile->username}}</a></div>
                </div>
            </div>

        </div>
    </div>
</div>
