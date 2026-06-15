<meta charset="utf-8">

{{-- Page title --}}
@hasSection('page_title')
    <title>@yield('page_title') - {{getSetting('site.name')}} </title>
@else
    <title>{{getSetting('site.name')}} -  {{getSetting('site.slogan')}}</title>
@endif

{{-- Generic Meta tags --}}
@hasSection('page_description')
    <meta name="description" content="@yield('page_description')">
@endif

{{-- Mobile tab color --}}
<meta name="theme-color" content="#505050">
<meta name="color-scheme" content="dark light">

{{-- Facebook share section --}}
<meta property="og:url"           content="@yield('share_url')" />
<meta property="og:type"          content="@yield('share_type')" />
<meta property="og:title"         content="@yield('share_title')" />
<meta property="og:description"   content="@yield('share_description')" />
<meta property="og:image"         content="@yield('share_img')" />

{{-- Twitter share section --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:site" content="@yield('share_url')">
<meta name="twitter:creator" content="@yield('author')">
<meta name="twitter:title" content="@yield('share_title')">
<meta name="twitter:description" content="@yield('share_description')">
<meta name="twitter:image" content="@yield('share_img')">

{{-- CSRF Baby --}}
<meta name="csrf-token" content="{{ csrf_token() }}" />
<meta name="viewport" content="width=device-width, initial-scale=1.0">

@yield('meta')

@if(getSetting('site.pwa_enabled'))
    @laravelPWA
@endif

@if(getSetting('site.pwa_enabled') || getSetting('profiles.push_notifications_enabled'))
    <script>
        (function () {
            if (!('serviceWorker' in navigator)) {
                return;
            }
            navigator.serviceWorker.register('{{ rtrim(getSetting('site.app_url'), '/') }}/serviceworker.js', {
                scope: '/'
            }).then(function (registration) {
                console.log('ServiceWorker registration successful with scope:', registration.scope);
            }).catch(function (err) {
                console.log('ServiceWorker registration failed:', err);
            });
        })();
    </script>
@endif
<script src="{{asset('libs/pusher-js/dist/web/pusher.min.js')}}"></script>

{{-- Favicon --}}
<link rel="shortcut icon" href="{{ getSetting('site.favicon') }}" type="image/x-icon">

<!-- Google fonts -->
<link rel="preload" as="style" href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" onload="this.onload=null;this.rel='stylesheet'">
<noscript>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
</noscript>

{{-- Global CSS Assets --}}
{!!
    Minify::stylesheet(
        array_merge([
            '/css/theme/bootstrap'.GenericHelper::getThemeCssSuffix().'.css',
            '/css/app.css',
         ],
         (isset($additionalCss) ? $additionalCss : [])
         ))->withFullUrl()
!!}

@if(getSetting('compliance.enable_cookies_box'))
    <link rel="stylesheet" href="{{ asset('/libs/vanilla-cookieconsent/dist/cookieconsent.css') }}">
@endif

{{-- Page specific CSS --}}
@yield('styles')

@if(getSetting('site.custom_code_css'))
    <style>
        {!! getSetting('site.custom_code_css') !!}
    </style>
@endif

@if(getSetting('site.enable_smooth_page_change_transitions'))
    <style>
        @view-transition {
            navigation: auto;
        }
    </style>
@endif

@include('elements.ageverif-checker')
