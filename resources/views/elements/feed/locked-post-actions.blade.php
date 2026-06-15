<div class="{{isset($post->attachments[0]) && AttachmentHelper::hasBlurredPreview($post->attachments[0]) ? 'position-absolute bottom-0' : ''}} w-100 py-3 px-2 px-md-3">
    <div class="card ppv-info-bg rounded-lg">
        <div class="card-body p-3 small">
            {{--  Post attachments info --}}

            <div class="row mb-1 pb-2">

                <div class="col-3">
                    <div class="d-flex flex-row">
                        @foreach(PostsHelper::getAttachmentsTypesCount($post->attachments) as $attachmenttype => $count)
                            @switch($attachmenttype)
                                @case('image')
                                    <div class="d-flex justify-content-center align-items-center mr-2">
                                        @include('elements.icon',['icon'=>'images-outline','centered' => false,'variant'=>'small', 'classes' => 'mr-1']) {{$count}}
                                    </div>
                                    @break
                                @case('video')
                                    <div class="d-flex justify-content-center align-items-center mr-2">
                                        @include('elements.icon',['icon'=>'videocam-outline','centered' => false,'variant'=>'small', 'classes' => 'mr-1']) {{$count}}
                                    </div>
                                    @break
                                @case('audio')
                                    <div class="d-flex justify-content-center align-items-center mr-2">
                                        @include('elements.icon',['icon'=>'musical-notes-outline','centered' => false,'variant'=>'small', 'classes' => 'mr-1']) {{$count}}
                                    </div>
                                    @break
                            @endswitch
                        @endforeach
                        @if(PostsHelper::hasNoMedia($post->attachments))
                            <div class="d-flex justify-content-center align-items-center mr-2">
                                @include('elements.icon',['icon'=>'chatbox-ellipses-outline','centered' => false,'variant'=>'small', 'classes' => 'mr-1']) {{strlen($post->text)}}
                            </div>
                        @endif
                    </div>
                </div>

                <div class="col-6">
                    <div class="d-none d-md-block">
                        <div class="text-center align-items-center justify-content-center">
                            <div class="{{isset($post->attachments[0]) && AttachmentHelper::hasBlurredPreview($post->attachments[0]) ? 'text-white-50' : 'text-muted'}} small ">
                                {{__('PPV content, unlock to view this post.')}}
                            </div>
                        </div>
                    </div>
                </div>


                <div class="col-3">
                    <div class="d-flex justify-content-end align-items-center mr-2">
                        @include('elements.icon',['icon'=>'lock-closed-outline','centered' => false,'variant'=>'small', 'classes' => 'mr-1']) {{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($post->price)}}
                    </div>
                </div>

            </div>
            {{--  Sub button --}}
            <div class="">
                <button class=" btn btn-primary btn-block to-tooltip {{(!GenericHelper::creatorCanEarnMoney($post->user) || ($type == 'subscription')) ? 'disabled' : ''}} mb-0"
                        @if(Auth::check())
                            @if(!GenericHelper::creatorCanEarnMoney($post->user) || ($type == 'subscription'))
                                data-placement="top"
                        @if(($type == 'subscription'))
                            title="{{__('A subscription is required.')}}"
                        @else
                            title="{{__('This creator cannot earn money yet')}}"
                        @endif
                        @else
                            data-toggle="modal"
                        data-target="#checkout-center"
                        data-type="post-unlock"
                        data-recipient-id="{{$post->user->id}}"
                        data-amount="{{$post->price}}"
                        data-first-name="{{Auth::user()->first_name}}"
                        data-last-name="{{Auth::user()->last_name}}"
                        data-billing-address="{{Auth::user()->billing_address}}"
                        data-country="{{Auth::user()->country}}"
                        data-city="{{Auth::user()->city}}"
                        data-state="{{Auth::user()->state}}"
                        data-postcode="{{Auth::user()->postcode}}"
                        data-available-credit="{{Auth::user()->wallet->total}}"
                        data-username="{{$post->user->username}}"
                        data-name="{{$post->user->name}}"
                        data-avatar="{{$post->user->avatar}}"
                        data-post-id="{{$post->id}}"
                        @endif
                        @else
                            data-placement="top"
                        title="{{__('You need to login first')}}."
                    @endif
                >{{__('Unlock post for')}} {{\App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($post->price)}}</button>
            </div>

        </div>
    </div>
</div>

