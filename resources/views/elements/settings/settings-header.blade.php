<div class="d-flex justify-content-between">
    <div>
        @if($type == 'generic')
            <div class="ml-3">
                <h5 class="text-bold {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{__('Settings')}}</h5>
                <h6 class="mt-2 text-muted mb-0">{{__('Manage your account')}}</h6>
            </div>
        @else
            <div class="ml-3">
                <h5 class="text-bold mt-0 mt-md-3 mb-0 {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{ ucfirst(__($activeSettingsTab))}}</h5>
                <h6 class="mt-2 text-muted">{{__($currentSettingTab['heading'])}}</h6>
            </div>
        @endif
    </div>
    <div class="d-flex align-content-center pt-2">
        <div class="w-40 navbar-toggler d-lg-none mr-4 h-pill h-pill-primary rounded d-flex justify-content-center align-items-center " data-toggle="collapse" data-target="#settingsNav" aria-controls="settingsNav" aria-expanded="false" aria-label="Toggle navigation">
            @include('elements.icon',['icon'=>'options-outline','variant'=>'medium','centered'=>true])
        </div>
    </div>
</div>
