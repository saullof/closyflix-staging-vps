@if(getSetting('site.allow_language_switch'))
    <span class="text-link pointer-cursor nav-link d-flex justify-content-between" onclick="openLanguageSelectorDialog()">
        <div class="d-flex justify-content-center align-items-center">
            <div class="icon-wrapper d-flex justify-content-center align-items-center">
                @include('elements.icon',['icon'=>'language','variant'=>'small', 'classes' => 'mr-1'])
            </div>
            <span class="d-block  text-truncate side-menu-label ml-1">{{__("Language")}}</span>
        </div>
    </span>
@endif
