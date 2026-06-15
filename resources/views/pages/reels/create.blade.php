@extends('layouts.user-no-nav')
@section('page_title', __('New reel'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/libs/dropzone/dist/dropzone.css',
            '/libs/@selectize/selectize/dist/css/selectize.css',
            '/css/components/sound-select.css',
            '/css/pages/reels.css',
         ])->withFullUrl()
    !!}
@stop

@section('scripts')
    {!!
        Minify::javascript([
            '/libs/dropzone/dist/dropzone.js',
            '/libs/@selectize/selectize/dist/js/selectize.min.js',
            '/js/FileUpload.js',
            '/js/stories/sound-select.js',
            '/js/AISuggestions.js',
            '/js/reels/reels-creator.js',
        ])->withFullUrl()
    !!}
@stop

@section('content')
    <div class="d-flex flex-wrap reel-create-page">
        <div class="col-12 px-0">
            @include('elements.uploaded-file-preview-template')
            @include('elements.attachments-uploading-dialog')

            <div class="d-flex justify-content-between align-items-center pt-4 pb-3 px-3 border-bottom">
                <h5 class="text-truncate text-bold mb-0 {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">
                    {{ __('Create a reel') }}
                </h5>
                <a href="{{ route('reels.index') }}" class="btn btn-sm btn-outline-primary">{{ __('View reels') }}</a>
            </div>

            <div class="px-3 py-3">
                @if(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))
                    <div class="alert alert-warning text-white font-weight-bold mt-0 mb-3" role="alert">
                        {{__("Before being able to publish an item, you need to complete your")}} <a class="text-white" href="{{route('my.settings',['type'=>'verify'])}}">{{__("profile verification")}}</a>.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="reel-preview-wrapper card border-0">
                            <div class="card-body p-3 reel-preview-stage">
                                <div id="reel-preview" class="reel-canvas is-empty is-no-media">
                                    <div class="reel-preview-top-controls" aria-hidden="true">
                                        <span class="reel-preview-control">
                                            <ion-icon name="pause-outline"></ion-icon>
                                        </span>
                                        <span class="reel-preview-control">
                                            <ion-icon name="volume-mute-outline"></ion-icon>
                                        </span>
                                    </div>

                                    <div class="reel-legibility reel-legibility--top" aria-hidden="true"></div>
                                    <div class="reel-legibility reel-legibility--bottom" aria-hidden="true"></div>

                                    <video id="reel-preview-video" class="d-none" autoplay muted loop playsinline></video>
                                    <img id="reel-preview-cover" class="d-none" alt="">

                                    <div class="reel-preview-actions" aria-hidden="true">
                                        <span class="reel-preview-action">
                                            <ion-icon name="heart-outline"></ion-icon>
                                            <small>0</small>
                                        </span>
                                        <span class="reel-preview-action">
                                            <ion-icon name="chatbubble-outline"></ion-icon>
                                            <small>0</small>
                                        </span>
                                        <span class="reel-preview-action">
                                            <ion-icon name="bookmark-outline"></ion-icon>
                                            <small>0</small>
                                        </span>
                                        <span class="reel-preview-action">
                                            <ion-icon name="share-social-outline"></ion-icon>
                                        </span>
                                        <span class="reel-preview-action">
                                            <ion-icon name="eye-outline"></ion-icon>
                                            <small>0</small>
                                        </span>
                                    </div>

                                    <div class="reel-preview-overlay" aria-hidden="true">
                                        <div class="reel-preview-user-row">
                                            <span class="reel-preview-avatar">
                                                <img src="{{ Auth::user()->avatar }}" alt="">
                                            </span>
                                            <span class="reel-preview-username">{{ '@'.Auth::user()->username }}</span>
                                        </div>
                                        <div class="reel-preview-caption d-none"></div>
                                        <div class="reel-preview-sound d-none">
                                            <ion-icon name="musical-notes-outline"></ion-icon>
                                            <span></span>
                                        </div>
                                    </div>

                                    <div class="reel-preview-progress" aria-hidden="true">
                                        <span></span>
                                    </div>

                                    <div class="reel-preview-empty" aria-hidden="true">
                                        <div class="reel-preview-empty__title">{{ __('Your reel preview') }}</div>
                                        <div class="reel-preview-empty__sub">{{ __('Upload a vertical video to see it here.') }}</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0 text-center pt-1">
                                <small class="text-muted">{{ __('Preview of how your reel will appear.') }}</small>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="reel-create-panel">
                            <label class="mb-1">{{ __('Reel media') }}</label>
                            <div class="dropzone-previews dropzone w-100 ppl-0 pr-0 pt-1 pb-1 border rounded reel-upload-zone file-upload-button {{!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks') ? 'is-disabled disabled' : ''}}">
                                @if(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))
                                    <div class="upload-zone-disabled-message">{{ __('Drop files here to upload') }}</div>
                                @endif
                            </div>
                            <p class="mb-0 mt-2">
                                <small class="form-text text-muted">
                                    {{ __('Upload one video. You can optionally add one image to use as the cover.') }}
                                </small>
                            </p>
                            <div id="reel-orientation-warning" class="reel-orientation-warning d-none mt-2">
                                <div class="reel-orientation-warning-icon">
                                    <ion-icon name="phone-portrait-outline"></ion-icon>
                                </div>
                                <div>
                                    <div class="text-bold">{{ __('Vertical videos work best for Reels.') }}</div>
                                    <div class="small">{{ __('Landscape or square videos may be cropped in Explore and fullscreen playback.') }}</div>
                                </div>
                            </div>

                            <div class="mt-3">
                                <div class="d-flex justify-content-between">
                                    <label for="reel-caption" class="mb-1">{{ __('Caption') }}</label>

                                    @if(getSetting('ai.text_enabled'))
                                        <a href="javascript:void(0)"
                                           class="ai-suggest-link"
                                           data-ai-type="reel"
                                           data-ai-target="#reel-caption"
                                           data-loading-text="{{ __('Generating') }}">
                                            {{ __('AI Suggestion') }}
                                        </a>
                                    @endif
                                </div>
                                <textarea id="reel-caption"
                                          class="form-control"
                                          rows="4"
                                          maxlength="2200"
                                          placeholder="{{ __('Write a caption...') }}"></textarea>
                            </div>

                            @if(getSetting('reels.allow_sounds'))
                                @include('elements.stories.story-sound-selector', [
                                    'idPrefix' => 'reel',
                                    'label' => __('Sound'),
                                    'helpText' => __('Start typing to search. Select a sound to attach it to this reel.'),
                                    'videoUnavailableText' => null
                                ])
                            @endif

                            <div class="d-flex justify-content-between align-items-center mt-4">
                                <div class="d-flex align-items-center">
                                    @if(getSetting('reels.allow_public_reels'))
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="reel-is-public" name="is_public">
                                            <label class="custom-control-label" for="reel-is-public">
                                                {{ __('Is public') }}
                                            </label>
                                        </div>
                                    @endif
                                </div>

                                <button type="button"
                                        id="btn-reel-share"
                                        class="btn btn-outline-primary {{!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks') ? 'disabled' : ''}}"
                                        {{!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks') ? 'disabled' : ''}}>
                                    {{ __('Publish reel') }}
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
