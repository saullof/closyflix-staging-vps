@extends('layouts.user-no-nav')
@section('page_title', __('New story'))

@section('styles')
    {!!
        Minify::stylesheet([
            '/libs/dropzone/dist/dropzone.css',
            '/libs/@selectize/selectize/dist/css/selectize.css',
            '/css/components/sound-select.css',
            '/css/pages/stories-create.css',
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
            '/js/stories/stories-creator.js',
            '/js/AISuggestions.js',
         ])->withFullUrl()
    !!}
@stop

@section('content')
    <div class="d-flex flex-wrap">
        <div class="col-12 px-0">
            @include('elements.uploaded-file-preview-template')
            @include('elements.attachments-uploading-dialog')

            <div class="d-flex justify-content-between pt-4 pb-3 px-3 border-bottom">
                <h5 class="text-truncate text-bold {{(Cookie::get('app_theme') == null ? (getSetting('site.default_user_theme') == 'dark' ? '' : 'text-dark-r') : (Cookie::get('app_theme') == 'dark' ? '' : 'text-dark-r'))}}">
                    {{ __("Create a story") }}
                </h5>
            </div>

            <div class="pl-3 pr-3 pt-3 pb-4">
                @if(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))
                    <div class="alert alert-warning text-white font-weight-bold mt-0 mb-3" role="alert">
                        {{__("Before being able to publish an item, you need to complete your")}} <a class="text-white" href="{{route('my.settings',['type'=>'verify'])}}">{{__("profile verification")}}</a>.
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                <div class="row">
                    {{-- LEFT: live preview --}}
                    <div class="col-md-6 mb-4">
                        <div class="story-preview-wrapper card border-0">
                            <div class="card-body p-3 story-preview-stage">

                                <div id="story-preview" class="story-canvas is-empty">

                                    {{-- top chrome --}}
                                    <div class="story-chrome story-chrome--top">
                                        <div class="story-progress">
                                            <span class="story-progress__seg is-active"></span>
                                            <span class="story-progress__seg"></span>
                                            <span class="story-progress__seg"></span>
                                        </div>

                                        <div class="story-header">
                                            <div class="story-user">
                                                <div class="story-avatar" aria-hidden="true"></div>
                                                <div class="story-user-meta">
                                                    <div class="story-user-name">{{ __("You") }}</div>
                                                    <div class="story-user-time">{{ __("now") }}</div>
                                                </div>
                                            </div>

                                            <div class="story-header-actions" aria-hidden="true">
                                                <button type="button" class="story-preview-expand" tabindex="-1" aria-hidden="true">
                                                    <ion-icon name="ellipsis-horizontal-outline"></ion-icon>
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- legibility overlays --}}
                                    <div class="story-legibility story-legibility--top" aria-hidden="true"></div>
                                    <div class="story-legibility story-legibility--bottom" aria-hidden="true"></div>

                                    {{-- overlay text (used in both modes) --}}
                                    <div id="story-preview-text" class="story-text-layer story-text-draggable"></div>

                                    {{-- bottom chrome --}}
                                    <div class="story-chrome story-chrome--bottom" aria-hidden="true">
                                        <div class="story-reply">
                                            <div class="story-reply-pill">{{ __("Send message…") }}</div>
                                            <div class="story-reply-btn">➤</div>
                                        </div>
                                    </div>

                                    {{-- empty hint (nice before upload) --}}
                                    <div class="story-empty-hint" aria-hidden="true">
                                        <div class="story-empty-hint__title">{{ __("Your story preview") }}</div>
                                        <div class="story-empty-hint__sub">{{ __("Upload media or write text to see it here.") }}</div>
                                    </div>

                                </div>

                            </div>

                            <div class="card-footer bg-transparent border-0 pt-0 text-center pt-1">
                                <small class="text-muted">{{ __("Preview of how your story will appear.") }}</small>
                            </div>
                        </div>
                    </div>

                    {{-- RIGHT: controls --}}
                    <div class="col-md-6 mb-4">
                        <div class="story-panel card border">
                            <div class="card-body">

                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="mb-0 text-muted text-uppercase story-panel-label">
                                        {{ __("Story type") }}
                                    </h6>
                                </div>

                                <ul class="nav nav-pills story-tabs mb-3" id="story-type-tabs">
                                    <li class="nav-item">
                                        <a class="nav-link active" data-toggle="pill" href="#story-tab-media">
                                            {{ __("Photo / Video") }}
                                        </a>
                                    </li>
                                    <li class="nav-item">
                                        <a class="nav-link" data-toggle="pill" href="#story-tab-text">
                                            {{ __("Text story") }}
                                        </a>
                                    </li>
                                </ul>

                                <div class="tab-content">
                                    {{-- MEDIA STORY TAB --}}
                                    <div class="tab-pane fade show active" id="story-tab-media">
                                        <label class="mb-1">{{ __("Story media") }}</label>

                                        <div class="dropzone-previews dropzone w-100 ppl-0 pr-0 pt-1 pb-1 border rounded story-upload-zone file-upload-button {{!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks') ? 'is-disabled disabled' : ''}}">
                                            @if(!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks'))
                                                <div class="upload-zone-disabled-message">{{ __('Drop files here to upload') }}</div>
                                            @endif
                                        </div>

                                        <div class="mt-1">
                                            <p class="mb-2"><small class="form-text text-muted">{{ __("Max size") }}: 4 {{ __("MB") }}.</small></p>
                                        </div>

                                        <div class="mt-3">
                                            <div class="d-flex justify-content-between">
                                                <label for="story-text-overlay" class="mb-1">{{ __("Story text") }}</label>

                                                @if(getSetting('ai.text_enabled'))
                                                    <a href="javascript:void(0)"
                                                       class="ai-suggest-link"
                                                       data-ai-type="story"
                                                       data-ai-target="#story-text-overlay"
                                                       data-loading-text="{{ __('Generating') }}">
                                                        {{ __('AI Suggestion') }}
                                                    </a>
                                                @endif
                                            </div>
                                            <textarea id="story-text-overlay"
                                                      class="form-control"
                                                      rows="2"
                                                      placeholder="{{ __('Add a short message...') }}"
                                                      maxlength="{{ (int) getSetting('stories.max_text_length') ?: 2000 }}"
                                            ></textarea>
                                            <div class="mt-1">
                                                <p class="mb-2"><small class="form-text text-muted">{{__("Drag to reposition the text")}}.</small></p>
                                            </div>
                                        </div>

                                        @if(getSetting('stories.allow_cta_links'))
                                            <div class="mt-3">
                                                <label class="mb-1">{{ __("Story link") }}</label>

                                                <input type="url"
                                                       id="media-story-link-url"
                                                       class="form-control mb-2"
                                                       placeholder="https://example.com">

                                                <input type="text"
                                                       id="media-story-link-text"
                                                       class="form-control"
                                                       maxlength="80"
                                                       placeholder="{{ __('Link label (e.g. Learn more)') }}">

                                            </div>
                                        @endif

                                        @if(getSetting('stories.allow_sounds'))
                                            @include('elements.stories.story-sound-selector', [
                                                'idPrefix' => 'media',
                                                'label' => __('Sound')
                                            ])
                                        @endif

                                    </div>

                                    {{-- TEXT-ONLY STORY TAB --}}
                                    <div class="tab-pane fade" id="story-tab-text">
                                        <div class="d-flex justify-content-between">
                                            <label for="story-text-only" class="mb-1">{{ __("Story text") }}</label>
                                            @if(getSetting('ai.text_enabled'))
                                                <a href="javascript:void(0)"
                                                   class="ai-suggest-link"
                                                   data-ai-target="#story-text-only"
                                                   data-ai-type="story"
                                                   data-loading-text="{{ __('Generating') }}">
                                                    {{ __('AI Suggestion') }}
                                                </a>
                                            @endif
                                        </div>

                                        <textarea id="story-text-only"
                                                  class="form-control"
                                                  rows="2"
                                                  placeholder="{{ __('Add a short message...') }}"
                                                  maxlength="{{ (int) getSetting('stories.max_text_length') ?: 2000 }}"
                                        ></textarea>
                                        <div class="mt-1">
                                            <p class="mb-2"><small class="form-text text-muted">{{__("Drag to reposition the text")}}.</small></p>
                                        </div>
                                        <div class="mt-3">
                                            <div class="mb-2">{{ __("Background color") }}</div>
                                            <div class="d-flex flex-wrap" id="story-bg-picker">


                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--default"
                                                        data-preset="grad_default"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--black"
                                                        data-preset="solid_black"
                                                        data-color="#000000"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--orange"
                                                        data-preset="grad_orange"
                                                        data-color="#ff5722"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--pink"
                                                        data-preset="grad_pink"
                                                        data-color="#ff2d8d"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--red"
                                                        data-preset="grad_red"
                                                        data-color="#e53935"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--purple"
                                                        data-preset="grad_purple"
                                                        data-color="#9b3dff"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--indigo"
                                                        data-preset="grad_indigo"
                                                        data-color="#3f51b5"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--blue"
                                                        data-preset="grad_blue"
                                                        data-color="#2196f3"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--teal"
                                                        data-preset="grad_teal"
                                                        data-color="#00bcd4"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--green"
                                                        data-preset="grad_green"
                                                        data-color="#4caf50"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--yellow"
                                                        data-preset="grad_yellow"
                                                        data-color="#ffca28"></button>

                                                <button type="button"
                                                        class="btn btn-sm rounded-circle mr-2 mb-2 story-bg-choice story-bg-choice--brown"
                                                        data-preset="grad_brown"
                                                        data-color="#6d4c41"></button>

                                            </div>

                                        </div>

                                        @if(getSetting('stories.allow_cta_links'))
                                            <div class="mt-3">
                                                <label class="mb-1">{{ __("Story link") }}</label>

                                                <input type="url"
                                                       id="text-story-link-url"
                                                       class="form-control mb-2"
                                                       placeholder="https://example.com">

                                                <input type="text"
                                                       id="text-story-link-text"
                                                       class="form-control"
                                                       maxlength="80"
                                                       placeholder="{{ __('Link label (e.g. Learn more)') }}">

                                            </div>
                                        @endif

                                        @if(getSetting('stories.allow_sounds'))
                                            @include('elements.stories.story-sound-selector', [
                                                'idPrefix' => 'text',
                                                'label' => __('Sound')
                                            ])
                                        @endif

                                    </div>
                                </div>

                                {{-- Visibility + Share button --}}
                                <div class="story-footer d-flex justify-content-between align-items-center mt-4">
                                    <div class="d-flex align-items-center">
                                        @if(getSetting('stories.allow_public_stories'))

                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="is_public" name="is_public">
                                                <label class="custom-control-label" for="is_public">
                                                    {{ __("Is public") }}
                                                </label>
                                            </div>
                                        @endif
                                    </div>

                                    <button type="button"
                                            id="btn-story-share"
                                            class="btn btn-primary btn-story-share text-uppercase mb-0 {{!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks') ? 'disabled' : ''}}"
                                            {{!GenericHelper::isUserVerified() && getSetting('site.enforce_user_identity_checks') ? 'disabled' : ''}}>
                                        {{ __("Share story") }}
                                    </button>
                                </div>

                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
@stop
