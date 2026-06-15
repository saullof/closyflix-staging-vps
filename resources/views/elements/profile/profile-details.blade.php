<div class="pt-2 pl-4 pr-4">
    <h5 class="text-bold d-flex align-items-center">
        <span class="text-truncate">{{$user->name}}</span>
        @if(GenericHelper::isUserVerified($user))
            <span data-toggle="tooltip" data-placement="top" title="{{__('Verified user')}}">
                @include('elements.icon',['icon'=>'verified','centered'=>true,'classes'=>'ml-1 text-primary'])
            </span>
        @endif
        @if($hasActiveStream)
            <span data-toggle="tooltip" data-placement="right" title="{{__('Live streaming')}}">
                            <div class="blob red ml-3"></div>
                            </span>
        @endif
    </h5>
    <h6 class="text-muted"><span class="text-bold"><span>@</span>{{$user->username}}</span>
        @if(getSetting('profiles.show_online_users_indicator'))
            <span class="font-weight-bold">•</span>
            @if(GenericHelper::isUserOnline($user->id))
                <span>{{__("Available now")}}</span>
            @else
                @if(getSetting('profiles.record_users_last_activity_time') && $user->last_active_at)
                    <span>{{__("Last seen")}} {{$user->last_active_for_humans}}</span>
                @else
                    <span>{{__("Online recently")}}</span>
                @endif
            @endif
        @endif
    </h6>
</div>

<div class="pt-2 pb-2 pl-4 pr-4 profile-description-holder">
    <div class="description-content {{$user->bio && !getSetting('profiles.disable_profile_bio_excerpt') ? 'line-clamp-3' : ''}}">
        @if($user->bio)
            @if(getSetting('profiles.allow_profile_bio_markdown'))
                {!!  GenericHelper::parseProfileMarkdownBio($user->bio) !!}
            @else
                {!!GenericHelper::parseSafeHTML($user->bio)!!}
            @endif
        @else
            {{__('No description available.')}}
        @endif
    </div>
    @if($user->bio && !getSetting('profiles.disable_profile_bio_excerpt'))
        <span class="text-primary pointer-cursor show-more-actions d-none" onclick="Profile.toggleFullDescription()">
                            <span class="label-more">{{__('More info')}}</span>
                            <span class="label-less d-none">{{__('Show less')}}</span>
                        </span>
    @endif
</div>

@if(getSetting('profiles.social_links_enabled'))
    <div class="px-4 py-2">
        @include('elements.profile.profile-social-links', [
         'items' => $profileSocialLinks ?? []
     ])
    </div>
@endif

@if(!getSetting('profiles.disable_website_link_on_profile') && $user->website)
    <div class="profile-instagram-link px-4 py-1">
        <a href="{{$user->website}}" target="_blank" rel="nofollow noopener" title="{{__('Instagram')}}" aria-label="{{__('Instagram')}}">
            <img src="{{asset('img/logos/instagram.png')}}" alt="{{__('Instagram')}}">
        </a>
    </div>
@endif

@if(!empty($storiesEnabled) && !empty($allowHighlights) && !empty($hasHighlights))
    <div class="profile-highlights mb-3" id="profile-highlights">
        <div class="d-flex justify-content-between align-items-center px-4 pt-2 pb-1">
            <div class="text-muted text-uppercase small font-weight-bold">{{ __('Highlights') }}</div>
        </div>

        <div class="swiper profile-highlights-swiper px-4">
            {{-- Skeleton only when highlights exist --}}
            <div class="profile-highlights-skeleton">
                @include('elements.preloading.stories-swiper-skeleton', ['limit' => 4])
            </div>

            <div class="swiper-wrapper" id="profile-highlights-wrapper"></div>
        </div>
    </div>
@endif

<div class="profile-meta-row d-flex flex-column flex-md-row align-items-md-center pb-2 pl-4 pr-4 mb-3 mt-1">
    <div class="profile-meta-details d-flex flex-wrap align-items-center">
        <div class="profile-join-date d-flex align-items-center mr-2 text-truncate">
            @include('elements.icon',['icon'=>'calendar-clear-outline','centered'=>false,'classes'=>'mr-1'])
            <div class="text-truncate ml-1">
                {{ucfirst($user->created_at->translatedFormat('F d'))}}
            </div>
        </div>

        @if($user->location)
            <div class="d-flex align-items-center mr-2 text-truncate">
                @include('elements.icon',['icon'=>'location-outline','centered'=>false,'classes'=>'mr-1'])
                <div class="text-truncate ml-1">
                    {{$user->location}}
                </div>
            </div>
        @endif

        @if(getSetting('profiles.allow_gender_pronouns') && $user->gender_pronoun)
            <div class="d-flex align-items-center mr-2 text-truncate">
                @include('elements.icon',['icon'=>'male-female-outline','centered'=>false,'classes'=>'mr-1'])
                <div class="text-truncate ml-1">
                    {{$user->gender_pronoun}}
                </div>
            </div>
        @endif
    </div>

    <div class="profile-stats-inline text-muted">
        <a class="{{$activeFilter == false ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username])}}">
            <strong>{{short_number($posts->total())}}</strong> posts
        </a>
        <a class="{{$activeFilter == 'image' ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username]) . '?filter=image'}}">
            <strong>{{short_number($filterTypeCounts['image'] ?? 0)}}</strong> fotos
        </a>
        <a class="{{$activeFilter == 'video' ? 'active' : ''}}" href="{{route('profile',['username'=> $user->username]) . '?filter=video'}}">
            <strong>{{short_number($filterTypeCounts['video'] ?? 0)}}</strong> vídeos
        </a>
    </div>
</div>
