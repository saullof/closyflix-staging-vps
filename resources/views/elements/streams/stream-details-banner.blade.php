<div class="stream-details d-flex justify-content-between">
    <div class="mr-4 overflow-hidden">

        <div class="d-flex flex-row <!--my-1-->">
            <div class="d-flex justify-content-center">
                <img class="rounded-circle avatar" src="{{$stream->user->avatar}}" alt="{{$stream->user->username}}">
            </div>
            <div class="pl-3 w-100 d-flex align-items-center">
                <div>
                    <div class="d-flex flex-column overflow-hidden">
                        <div class="text-bold max-width-150 text-truncate d-flex align-items-center h5 font-weight-bold">
                            <a href="{{route('profile',['username'=>$stream->user->username])}}" class="text-dark-r">{{$stream->user->name}}</a>
                            @if(GenericHelper::isUserVerified($stream->user))
                                <span data-toggle="tooltip" data-placement="top" title="{{__('Verified user')}}">
                                @include('elements.icon',['icon'=>'verified','centered'=>true,'classes'=>'ml-1', 'variant' => 'small'])
                            </span>
                            @endif
                        </div>

                    </div>
                    @if(!isset($streamEnded))
                        <span class=""><span class=""><span class="live-stream-users-count">0</span> {{__("Watching")}} </span>• {{__("Started streaming")}} {{\Carbon\Carbon::parse($stream->created_at)->diffForhumans()}}.</span>
                    @else
                        {{__('Stream ended :time time ago',['time'=>\Carbon\Carbon::parse($stream->ended_at)->diffForhumans()])}}
                    @endif
                </div>

            </div>
        </div>

    </div>
    @if(!isset($streamEnded))
        <div class="d-flex align-items-center">
            @if(isset($subLocked) && $stream->user->id !== Auth::user()->id)
                <div class="d-none d-sm-block">
                                <span class="p-pill ml-2 pointer-cursor to-tooltip stream-subscribe-button"
                                      @if(!\App\Providers\GenericHelperServiceProvider::creatorCanEarnMoney($stream->user))
                                          data-placement="top"
                                      title="{{__('This creator cannot earn money yet')}}"
                                      @else
                                          data-toggle="modal"
                                      data-target="#checkout-center"
                                      data-type="one-month-subscription"
                                      data-recipient-id="{{$stream->user->id}}"
                                      data-amount="{{$stream->user->profile_access_price}}"
                                      data-first-name="{{Auth::user()->first_name}}"
                                      data-last-name="{{Auth::user()->last_name}}"
                                      data-billing-address="{{Auth::user()->billing_address}}"
                                      data-country="{{Auth::user()->country}}"
                                      data-city="{{Auth::user()->city}}"
                                      data-state="{{Auth::user()->state}}"
                                      data-postcode="{{Auth::user()->postcode}}"
                                      data-available-credit="{{Auth::user()->wallet->total}}"
                                      data-username="{{$stream->user->username}}"
                                      data-name="{{$stream->user->name}}"
                                      data-avatar="{{$stream->user->avatar}}"
                                      data-stream-id="{{$stream->id}}"
                                      @endif
                                >
                                 @include('elements.icon',['icon'=>'person-add-outline'])
                                </span>
                </div>
            @endif

            @if(isset($priceLocked) && $stream->user->id !== Auth::user()->id)
                <div class="d-none d-sm-block">
                                <span class="p-pill ml-2 pointer-cursor to-tooltip stream-unlock-button"
                                      @if(!GenericHelper::creatorCanEarnMoney($stream->user))
                                          data-placement="top"
                                      title="{{__('This creator cannot earn money yet')}}"
                                      @else
                                          data-toggle="modal"
                                      data-target="#checkout-center"
                                      data-type="stream-access"
                                      data-recipient-id="{{$stream->user->id ? $stream->user->id : ''}}"
                                      data-amount="{{$stream->price}}"
                                      data-first-name="{{Auth::user()->first_name}}"
                                      data-last-name="{{Auth::user()->last_name}}"
                                      data-billing-address="{{Auth::user()->billing_address}}"
                                      data-country="{{Auth::user()->country}}"
                                      data-city="{{Auth::user()->city}}"
                                      data-state="{{Auth::user()->state}}"
                                      data-postcode="{{Auth::user()->postcode}}"
                                      data-available-credit="{{Auth::user()->wallet->total}}"
                                      data-username="{{$stream->user->username}}"
                                      data-name="{{$stream->user->name}}"
                                      data-avatar="{{$stream->user->avatar}}"
                                      data-stream-id="{{$stream->id}}"
                                      @endif
                                >
                                 @include('elements.icon',['icon'=>'lock-open-outline'])
                                </span>
                </div>
            @endif

            @if($stream->canWatchStream && $stream->user->id !== Auth::user()->id)
                <div class="">
                                <span class="p-pill ml-2 pointer-cursor to-tooltip"
                                      @if(!GenericHelper::creatorCanEarnMoney($stream->user))
                                          data-placement="top"
                                      title="{{__('This creator cannot earn money yet')}}"
                                      @else
                                          data-placement="top"
                                      title="{{__('Send a tip')}}"
                                      data-toggle="modal"
                                      data-target="#checkout-center"
                                      data-type="tip"
                                      data-first-name="{{Auth::user()->first_name}}"
                                      data-last-name="{{Auth::user()->last_name}}"
                                      data-billing-address="{{Auth::user()->billing_address}}"
                                      data-country="{{Auth::user()->country}}"
                                      data-city="{{Auth::user()->city}}"
                                      data-state="{{Auth::user()->state}}"
                                      data-postcode="{{Auth::user()->postcode}}"
                                      data-available-credit="{{Auth::user()->wallet->total}}"
                                      data-username="{{$stream->user->username}}"
                                      data-name="{{$stream->user->name}}"
                                      data-avatar="{{$stream->user->avatar}}"
                                      data-recipient-id="{{$stream->user->id}}"
                                      data-stream-id="{{$stream->id}}"
                                    @endif
                                >
                                 @include('elements.icon',['icon'=>'cash-outline'])
                                </span>
                </div>

                <div class="dropdown {{GenericHelper::getSiteDirection() == 'rtl' ? 'dropright' : 'dropleft'}}">
                    <div class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="p-pill ml-2 pointer-cursor to-tooltip" title="{{__("More")}}" >
                            @include('elements.icon',['icon'=>'ellipsis-horizontal-outline'])
                        </span>
                    </div>

                    <div class="dropdown-menu">
                        <a class="dropdown-item" href="javascript:void(0);" onclick="Lists.showReportBox({{$stream->user->id}},null,null,{{$stream->id}});">{{__('Report')}}</a>
                    </div>
                </div>

            @endif

            @if(!$stream->isLivekitDriver())
                @if($stream->user->id === Auth::user()->id)
                    <div class="d-none d-sm-block">
                        <a class="p-pill ml-2 pointer-cursor to-tooltip" href="{{route('my.streams.get')}}?action=details" title="{{__("Stream details")}}">
                            @include('elements.icon',['icon'=>'server-outline'])
                        </a>
                    </div>
                    <div class="d-none d-sm-block">
                        <a class="p-pill ml-2 pointer-cursor to-tooltip" href="{{route('my.streams.get')}}?action=edit" title="{{__("Edit stream")}}">
                            @include('elements.icon',['icon'=>'create-outline'])
                        </a>
                    </div>
                @endif
            @endif

        </div>
    @endif
</div>
