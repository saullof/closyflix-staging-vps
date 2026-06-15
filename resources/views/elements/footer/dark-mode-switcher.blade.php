@if(getSetting('site.allow_theme_switch'))
    <span class="text-link pointer-cursor nav-link d-flex justify-content-between dark-mode-switcher">
        <div class="d-flex justify-content-center align-items-center">
            <div class="icon-wrapper d-flex justify-content-center align-items-center">
                @if(Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark'))
                    @include('elements.icon',['icon'=>'contrast-outline','variant'=>'small','centered'=>false,'classes'=>'mr-1'])
                @else
                    @include('elements.icon',['icon'=>'contrast','variant'=>'small','centered'=>false,'classes'=>'mr-1'])
                @endif
            </div>
            <span class="d-block text-truncate side-menu-label ml-1">
                                        @if(Cookie::get('app_theme') == 'dark' || (!Cookie::get('app_theme') && getSetting('site.default_user_theme') == 'dark') )
                    {{__('Light')}}
                @else
                    {{__('Dark')}}
                @endif
                            </span>
        </div>
    </span>
@endif
