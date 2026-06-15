@extends('layouts.user-no-nav')
@section('page_title', __('Your lists'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/css/pages/lists.css'
         ])->withFullUrl()
    !!}
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/js/ListsPaginator.js',
            '/js/pages/lists.js'
         ])->withFullUrl()
    !!}
@stop

@section('content')
    <div class="d-flex flex-wrap">
        <div class="min-vh-100 border-right col-12 pr-md-0 px-0">
            <div class="pt-4 d-flex justify-content-between align-items-center px-3 pb-3 border-bottom">
                <div>
                    <h5 class="text-truncate text-bold mb-0 {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{__('Lists')}}</h5>
                </div>
                <button  class="btn btn-sm btn-outline-primary mb-0" onclick="Lists.showListEditDialog()">{{ __('Create') }}</button>
            </div>
            <div class="lists-wrapper mt-2">
                @if(count($lists))
                    @foreach($lists as $key => $list)
                        @include('elements.lists.list-box', ['list'=>$list, 'isLastItem' => (count($lists) == $key + 1)])
                    @endforeach
                @else
                    <p class="ml-4">{{__('No lists available')}}</p>
                @endif
            </div>
            @include('elements.feed.posts-loading-spinner')
        </div>
    </div>
    @include('elements.lists.list-update-dialog',['mode'=>'create'])
@stop
