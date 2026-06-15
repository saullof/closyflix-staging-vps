@extends('layouts.user-no-nav')

@section('page_title', __('Messenger'))

@section('styles')
    {!!
        Minify::stylesheet(array_merge([
            '/libs/@selectize/selectize/dist/css/selectize.css',
            '/libs/@selectize/selectize/dist/css/selectize.bootstrap4.css',
            '/libs/dropzone/dist/dropzone.css',
            '/libs/photoswipe/dist/photoswipe.css',
            '/libs/photoswipe/dist/default-skin/default-skin.css',
            '/css/pages/messenger.css',
            '/css/pages/checkout.css',
            '/libs/@selectize/selectize/dist/css/selectize.bootstrap4.css'
         ],$additionalAssets['css']))->withFullUrl()
    !!}
    @if(getSetting('stories.stories_enabled'))
        <link rel="stylesheet" href="{{asset('/libs/swiper/swiper-bundle.min.css')}}">
    @endif
@stop

@section('scripts')
    {!!
        Minify::javascript(array_merge([
            '/js/messenger/messenger.js',
            '/js/messenger/elements.js',
            '/libs/@selectize/selectize/dist/js/selectize.min.js',
            '/libs/dropzone/dist/dropzone.js',
            '/js/FileUpload.js',
            '/js/plugins/media/photoswipe.js',
            '/libs/photoswipe/dist/photoswipe-ui-default.min.js',
            '/js/plugins/media/mediaswipe.js',
            '/js/plugins/media/mediaswipe-loader.js',
            '/js/pages/lists.js',
            '/js/pages/checkout.js',
            '/libs/autolinker/dist/autolinker.min.js',
            '/libs/@selectize/selectize/dist/js/selectize.min.js'
         ],$additionalAssets['js']))->withFullUrl()
    !!}
@stop

@section('content')
    @include('elements.uploaded-file-preview-template')
    @include('elements.photoswipe-container')
    @include('elements.report-user-or-post',['reportStatuses' => ListsHelper::getReportTypes()])
    @include('elements.feed.post-delete-dialog')
    @include('elements.feed.post-list-management')
    @include('elements.messenger.message-price-dialog')
    @include('elements.checkout.checkout-box')
    @include('elements.attachments-uploading-dialog')
    @include('elements.messenger.locked-message-no-attachments-dialog', ['type' => lcfirst(__('Messages'))])
    <div class="d-flex flex-wrap">
        <div class="col-12 px-0">
            <div class="container-fluid messenger px-0">
                <div class="messenger-shell {{ GenericHelper::isDarkMode() ? 'messenger-dark' : 'messenger-light' }} border border-left-0 border-right-0 rounded-0 d-flex flex-column flex-lg-row overflow-hidden">
                    <aside class="messenger-sidebar conversations-wrapper d-flex flex-column border-right">
                        <div class="d-flex align-items-center justify-content-between px-3 py-3 border-bottom">
                            <div class="pr-3 overflow-hidden">
                                <h5 class="mb-0 text-truncate font-weight-bold {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">{{__('Messages')}}</h5>
                            </div>
	                            <div class="d-flex align-items-center flex-shrink-0">
	                                <a title="{{__('Search conversations')}}" class="h-pill h-pill-primary pointer-cursor messenger-contacts-search-toggle rounded-circle d-flex align-items-center justify-content-center mb-0 mr-2" data-toggle="tooltip" data-placement="bottom" data-original-title="{{__('Search conversations')}}">
	                                    @include('elements.icon',['icon'=>'search-outline','variant'=>'mediumish'])
	                                </a>
	                                <a title="{{__('Message automations')}}" class="h-pill h-pill-primary pointer-cursor messenger-templates-toggle rounded-circle d-flex align-items-center justify-content-center mb-0 mr-2" data-toggle="tooltip" data-placement="bottom" data-original-title="{{__('Message automations')}}">
	                                    @include('elements.icon',['icon'=>'chatbubble-ellipses-outline','variant'=>'mediumish'])
	                                </a>
	                                <span data-toggle="tooltip" title="" class="pointer-cursor flex-shrink-0"
	                                      @if(!count($availableContacts))
	                                          data-original-title="{{trans_choice('Before sending a new message, please subscribe to a creator a follow a free profile.',['user' => 0])}}"
                                      @else
                                          data-original-title="{{trans_choice('Send a new message',['user' => 0])}}"
                                      @endif
                                >
                                    <a title="" class="h-pill h-pill-primary pointer-cursor new-conversation-toggle rounded-circle d-flex align-items-center justify-content-center mb-0" data-original-title="{{trans_choice('Send a new message',['user' => 0])}}">
                                        @include('elements.icon',['icon'=>'create-outline','variant'=>'mediumish'])
                                    </a>
                                </span>
                            </div>
                        </div>
                        <div class="conversation-search d-none px-3 py-2 border-bottom">
                            <div class="messenger-search-field d-flex align-items-center">
                                <span class="messenger-search-icon text-muted d-flex align-items-center">
                                    @include('elements.icon',['icon'=>'search-outline','variant'=>'small'])
                                </span>
                                <input type="search" class="form-control messenger-contacts-search border-0 shadow-none" placeholder="{{__('Search conversations')}}" autocomplete="off">
                                <button type="button" class="btn btn-link messenger-contacts-search-clear d-none p-0 text-muted mb-0" aria-label="{{__('Clear search')}}">
                                    @include('elements.icon',['icon'=>'close-outline','variant'=>'small'])
                                </button>
                            </div>
                        </div>
                        <div class="conversations-list flex-fill">
                            @if($lastContactID == false)
                                <div class="d-flex mt-3 mt-md-2 pl-3 pl-md-0 mb-3 pl-md-0"><span>{{__('Click the text bubble to send a new message.')}}</span></div>
                            @else
                                @include('elements.preloading.messenger-contact-box', ['limit'=>5])
                            @endif
                        </div>
                        <div class="contacts-pagination d-none border-top px-3 py-2">
                            <div class="d-flex align-items-center justify-content-center messenger-mobile-back">
                                <div class="py-2 spinner d-none messenger-contacts-spinner">
                                    <div class="spinner-border text-primary messenger-pagination-spinner" role="status">
                                        <span class="sr-only">{{__('Loading...')}}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </aside>
                    <section class="conversation-wrapper d-flex flex-column flex-fill border-right">
                        @include('elements.message-alert')
                        @include('elements.messenger.messenger-conversation-header')
                        @include('elements.messenger.messenger-new-conversation-header')
                        @include('elements.preloading.messenger-conversation-header-box')
                        <div class="conversation-message-search d-none px-3 py-2 border-bottom">
                            <div class="messenger-search-field d-flex align-items-center">
                                <span class="messenger-search-icon text-muted d-flex align-items-center">
                                    @include('elements.icon',['icon'=>'search-outline','variant'=>'small'])
                                </span>
                                <input type="search" class="form-control conversation-message-search-input border-0 shadow-none" placeholder="{{__('Search messages')}}" autocomplete="off">
                                <span class="conversation-message-search-count small text-muted d-none mr-2"></span>
                                <button type="button" class="btn btn-link conversation-message-search-clear d-none p-0 text-muted mb-0" aria-label="{{__('Clear search')}}">
                                    @include('elements.icon',['icon'=>'close-outline','variant'=>'small'])
                                </button>
                            </div>
                        </div>
	                        <div class="conversation-history-control d-none px-3 pt-3">
	                            <div class="d-flex align-items-center justify-content-center posts-loading-indicator">
	                                <div class="py-2 spinner d-none messenger-messages-spinner">
	                                    <div class="spinner-border text-primary messenger-pagination-spinner" role="status">
	                                        <span class="sr-only">{{__('Loading...')}}</span>
                                    </div>
	                                </div>
	                            </div>
	                        </div>
	                        <div class="message-templates-panel d-none flex-fill overflow-auto px-3 py-3">
	                            <div class="d-flex align-items-start justify-content-between mb-3">
	                                <div class="pr-3">
	                                    <h5 class="mb-1 font-weight-bold">{{__('Message automations')}}</h5>
	                                    <div class="text-muted small">{{__('Set up welcome messages for new followers or subscribers.')}}</div>
	                                </div>
	                                <a class="h-pill h-pill-primary pointer-cursor messenger-templates-close rounded-circle d-flex align-items-center justify-content-center mb-0" data-toggle="tooltip" data-placement="bottom" title="{{__('Close')}}" data-original-title="{{__('Close')}}">
	                                    @include('elements.icon',['icon'=>'close-outline','variant'=>'mediumish'])
	                                </a>
	                            </div>
	                            <div class="message-template-trigger-list inline-border-tabs mb-3">
	                                <nav class="nav nav-pills nav-justified">
	                                    <a href="javascript:void(0)" class="nav-item nav-link message-template-trigger active" data-trigger="follower_created">
	                                        <div class="d-flex align-items-center justify-content-center">
	                                            @include('elements.icon',['icon'=>'people','variant'=>'medium','classes'=>'mr-2'])
	                                            {{__('New followers')}}
	                                        </div>
	                                    </a>
	                                    <a href="javascript:void(0)" class="nav-item nav-link message-template-trigger" data-trigger="subscription_created">
	                                        <div class="d-flex align-items-center justify-content-center">
	                                            @include('elements.icon',['icon'=>'logo-usd','variant'=>'medium','classes'=>'mr-2'])
	                                            {{__('New subscribers')}}
	                                        </div>
	                                    </a>
	                                </nav>
	                            </div>
	                            <div class="card shadow-none border message-template-card">
	                                <div class="card-body">
	                                    <div class="d-flex align-items-start justify-content-between">
	                                        <div class="pr-3">
	                                            <h6 class="message-template-title font-weight-bold mb-1">{{__('New followers')}}</h6>
	                                            <div class="message-template-description text-muted small">{{__('Sent once when someone follows you.')}}</div>
	                                        </div>
	                                        <div class="custom-control custom-switch">
	                                            <input type="checkbox" class="custom-control-input message-template-enabled" id="message-template-enabled">
	                                            <label class="custom-control-label" for="message-template-enabled">{{__('Enabled')}}</label>
	                                        </div>
	                                    </div>
	                                    <div class="mt-3 small text-muted">
	                                        {{__('Use the composer to write the template, attach media, and optionally set PPV.')}}
	                                    </div>
	                                </div>
	                            </div>
	                        </div>
	                        @include('elements.preloading.messenger-conversation-box')
	                        <div class="conversation-content px-3 pb-3 flex-fill">
	                        </div>
                        <div class="dropzone-previews dropzone w-100 px-3 py-2"></div>
                        <div class="conversation-writeup d-flex align-items-center {{!$lastContactID ? 'hidden' : ''}}">
                            {{-- Left side--}}
                            <div class="messenger-buttons-wrapper d-flex align-items-center">
                                <button class="btn btn-outline-primary btn-rounded-icon messenger-button attach-file file-upload-button to-tooltip" data-placement="top" title="{{__('Attach file')}}">
                                    <div class="d-flex justify-content-center align-items-center">
                                        @include('elements.icon',['icon'=>'document','variant'=>''])
                                    </div>
                                </button>
                            </div>
                            {{-- Input --}}
                            <form class="message-form flex-fill">
                                <div class="input-group messageBoxInput-wrapper">
                                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                                    <input type="hidden" name="receiverID" id="receiverID" value="">
                                    <textarea name="message" class="form-control messageBoxInput dropzone" placeholder="{{__('Write a message..')}}" onkeyup="messenger.textAreaAdjust(this)"></textarea>
                                    {{--                                    <div class="input-group-append z-index-3 d-flex align-items-center justify-content-center">--}}
                                    {{--                                        <span class="h-pill h-pill-primary rounded mr-3 trigger" data-toggle="tooltip" data-placement="top" title="Like" >😊</span>--}}
                                    {{--                                    </div>--}}
                                </div>
                            </form>
                            {{-- Right side --}}
                            <div class="messenger-buttons-wrapper d-flex align-items-center">
                                @if((GenericHelper::creatorCanEarnMoney(Auth::user()) && !(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))) /*|| Auth::user()->role_id === 1*/)
                                    <button class="btn btn-outline-primary btn-rounded-icon messenger-button mx-0 mx-md-1 to-tooltip" data-placement="top" title="{{__('Message price')}}" onClick="messenger.showSetPriceDialog()">
                                        <div class="d-flex justify-content-center align-items-center">
                                            <span class="message-price-lock">@include('elements.icon',['icon'=>'lock-open','variant'=>''])</span>
                                            <span class="message-price-close d-none">@include('elements.icon',['icon'=>'lock-closed','variant'=>''])</span>
                                        </div>
                                    </button>
                                @endif
                                <button class="btn btn-outline-primary btn-rounded-icon messenger-button  mx-0 mx-md-1 tip-btn to-tooltip"
                                        title="{{__('Send a tip')}}"
                                        data-placement="top"
                                        data-toggle="modal"
                                        data-target="#checkout-center"
                                        data-type="chat-tip"
                                        data-first-name="{{Auth::user()->first_name}}"
                                        data-last-name="{{Auth::user()->last_name}}"
                                        data-billing-address="{{Auth::user()->billing_address}}"
                                        data-country="{{Auth::user()->country}}"
                                        data-city="{{Auth::user()->city}}"
                                        data-state="{{Auth::user()->state}}"
                                        data-postcode="{{Auth::user()->postcode}}"
                                        data-available-credit="{{Auth::user()->wallet->total}}"
                                >
                                    <div class="d-flex justify-content-center align-items-center">
                                        @include('elements.icon',['icon'=>'wallet','variant'=>''])
                                    </div>
                                </button>
                                <button class="btn btn-outline-primary btn-rounded-icon messenger-button send-message mx-0 mx-md-1 to-tooltip" onClick="messenger.sendMessage()" data-placement="top" title="{{__('Send message')}}">
                                    <div class="d-flex justify-content-center align-items-center">
                                        @include('elements.icon',['icon'=>'paper-plane','variant'=>''])
                                    </div>
                                </button>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </div>
    </div>
    @include('elements.standard-dialog',[
    'dialogName' => 'message-delete-dialog',
    'title' => __('Delete message'),
    'content' => __('Are you sure you want to delete this message?'),
    'actionLabel' => __('Delete'),
    'actionFunction' => 'messenger.deleteMessage();',
])
    @if(getSetting('stories.stories_enabled'))
        @include('elements.stories.delete-dialog')
    @endif
@stop
