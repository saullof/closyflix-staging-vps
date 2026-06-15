@extends('layouts.user-no-nav')
@section('page_title', __('Reels'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/css/pages/reels.css',
         ])->withFullUrl()
    !!}
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/js/pages/lists.js',
            '/js/reels/reels-api.js',
            '/js/reels/reels-renderer.js',
            '/js/reels/reels-comments.js',
            '/js/reels/reels-player.js',
         ])->withFullUrl()
    !!}
@stop

@section('content')
    <div class="reels-page">
        @include('elements.verified-svg-store')

        <div class="reels-topbar border-bottom  pt-4 pb-3">
            <h5 class="mb-0 text-bold {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">
                {{ __('Explore') }} {{ __('Reels') }}
            </h5>
            <a href="{{ route('reels.create') }}" class="btn btn-sm btn-outline-primary mb-0">{{ __('Create') }}</a>
        </div>

        <div id="reels-feed"
             class="reels-feed"
             data-initial-reel="{{ $initialReelId ?? '' }}"
             data-initial-unavailable="{{ !empty($initialReelUnavailable) ? 1 : 0 }}"
             data-allow-progress-scrubbing="{{ getSetting('reels.allow_progress_scrubbing') ? 1 : 0 }}"
             data-base-url="{{ route('reels.index') }}"
             data-empty-action-url="{{ route('reels.create') }}"
             data-empty-action-label="{{ __('Create your reel') }}"
             data-permalink-template="{{ route('reels.get', ['reel_id' => '__REEL_ID__']) }}">
            <div class="reels-empty-state">
                <div class="spinner-border text-primary" role="status"></div>
            </div>
        </div>
    </div>

    @include('elements.report-user-or-post',['reportStatuses' => ListsHelper::getReportTypes()])
    @include('elements.reels.icon-preload')
    @include('elements.reels.delete-dialogs')
@stop
