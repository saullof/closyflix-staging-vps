@if(getSetting('site.allow_direction_switch'))
    <span class="text-link pointer-cursor nav-link d-flex justify-content-between rtl-mode-switcher">
        <div class="d-flex justify-content-center align-items-center">
            <div class="icon-wrapper d-flex justify-content-center align-items-center">
                @include('elements.icon',['icon'=>'return-up-back','variant'=>'small', 'classes' => 'mr-1'])
            </div>
            <span class="d-block  text-truncate side-menu-label ml-1">
                                        @if(GenericHelper::getSiteDirection() == 'rtl')
                    {{__('LTR')}}
                @else
                    {{__('RTL')}}
                @endif

                                    </span>
        </div>
    </span>
@endif
