@extends('layouts.user-no-nav')

@section('page_title',  ucfirst(__(str_replace('-', ' ', $activeSettingsTab))))

@section('scripts')
    {!!
        Minify::javascript(
            array_merge($additionalAssets['js'],[
                '/js/pages/settings/settings.js',
                '/js/AISuggestions.js',
         ])
        )->withFullUrl()
    !!}
    @if(getSetting('profiles.allow_profile_bio_markdown'))
        <script src="{{asset('/libs/easymde/dist/easymde.min.js')}}"></script>
    @endif
@stop

@section('styles')
    {!!
        Minify::stylesheet(
            array_merge($additionalAssets['css'],[
                '/css/pages/settings.css',
                ])
         )->withFullUrl()
    !!}
    <style>
        .selectize-control.multi .selectize-input>div.active {
            background:#{{getSetting('colors.theme_color_code')}};
        }
    </style>
    @if(getSetting('profiles.allow_profile_bio_markdown'))
        <link href="{{asset('/libs/easymde/dist/easymde.min.css')}}" rel="stylesheet">
    @endif
@stop

@section('content')
    <div class="">
        <div class="d-flex flex-wrap">
            <div class="col-12 col-md-4 col-lg-3 mb-3 pr-0 px-0 settings-menu">

                <div class="settings-menu-wrapper sticky-sm">

                    <div class="d-none d-md-block">
                        @include('elements.settings.settings-header',['type'=>'generic'])
                    </div>
                    <div class="d-block d-md-none mt-3">
                        @include('elements.settings.settings-header',['type'=>'settingTab'])
                    </div>
                    <hr class="mb-0">
                    <div class="">
                        @include('elements.settings.settings-menu',['availableSettings' => $availableSettings])
                    </div>
                </div>
            </div>
            <div class="col-md-8 col-lg-9 mb-5 mb-lg-0 min-vh-100 border-left border-right settings-content mt-1 mt-md-0 px-0">
                <div class="ml-3 d-none d-md-flex justify-content-between">
                    <div>
                        <h5 class="text-bold mt-0 mt-md-3 mb-0 {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{ ucfirst(__(str_replace('-', ' ', $activeSettingsTab)))}}</h5>
                        <h6 class="mt-2 text-muted">{{__($currentSettingTab['heading'])}}</h6>
                    </div>
                </div>
                <hr class="{{in_array($activeSettingsTab, ['subscriptions','payments']) ? 'mb-0' : ''}} d-none d-md-block mt-2">
                <div class="{{in_array($activeSettingsTab, ['subscriptions','payments', 'referrals']) ? '' : 'px-3 px-md-3'}}">
                    @include('elements.settings.settings-'.$activeSettingsTab)
                </div>
            </div>
        </div>
    </div>
@stop
