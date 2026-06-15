<!doctype html>
<html class="h-100" dir="{{GenericHelper::getSiteDirection()}}" lang="{{session('locale')}}">
<head>
    @include('template.head',['additionalCss' => [
                '/libs/animate.css/animate.css',
                '/libs/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.css',
                '/css/side-menu.css',
             ]])
</head>
<body class="d-flex flex-column has-mobile-bottom-nav {{ GenericHelper::isDarkMode() ? 'theme-dark' : 'theme-light' }}">
@include('elements.impersonation-header')
@include('elements.global-announcement')
<div class="flex-fill">
    @include('template.user-side-menu')

    <div class="container-xl px-0">
        <div class="d-flex flex-wrap main-wrapper {{Route::currentRouteName() == 'my.messenger.get' ? 'messenger-main-wrapper' : ''}}">
            <div class="col-2 col-md-3 pt-4 p-0 d-none d-md-block side-menu-panel">
                @include('template.side-menu')
            </div>
            <div class="col-12 col-md-9 {{(!in_array(Route::currentRouteName(),['my.messenger.get']) ? 'min-vh-100' : '' )}}  border-left px-0 content-wrapper {{Route::currentRouteName() == 'my.messenger.get' ? 'messenger-content-wrapper' : ''}} {{(in_array(Route::currentRouteName(),['feed','profile','my.messenger.get','search.get','my.notifications','my.bookmarks','my.lists.all','my.lists.show','my.settings','posts.get']) ? '' : 'border-right' )}}">
                @yield('content')
            </div>
        </div>
        <div class="d-block d-md-none fixed-bottom">
            @include('elements.mobile-navbar')
        </div>
    </div>

</div>
@if(\App\Providers\InstallerServiceProvider::checkIfInstalled() && !request()->is('age-check*') && app(\App\Services\AgeCheck\AgeGate::class)->isBuiltIn())
    @include('elements.site-entry-approval-box')
@endif
{{--@include('template.footer-compact',['compact'=>true])--}}
@include('template.jsVars')
@include('template.jsAssets',['additionalJs' => [
               '/libs/jquery-backstretch/jquery.backstretch.min.js',
               '/libs/wow.js/dist/wow.min.js',
               '/libs/malihu-custom-scrollbar-plugin/jquery.mCustomScrollbar.concat.min.js',
               '/js/SideMenu.js'
]])
@include('elements.language-selector-box')
@if (getSetting('site.pwa_enabled') && getSetting('site.pwa_install_prompt_enabled'))
    @include('elements.pwa-banner')
@endif
</body>
</html>
