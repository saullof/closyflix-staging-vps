<div class="stream-chat {{$stream->canWatchStream ? '' : 'mt-3'}}">
    @if($stream->canWatchStream)

        @include('elements.streams.stream-tips-wrapper')

        <div class="chat-content conversations-wrapper overflow-hidden pb-1 px-3 flex-fill pt-3">
            <div class="conversation-content pt-1 pb-1 px-2 flex-fill">
                @if($stream->messages->count())
                    @foreach($stream->messages as $message)
                        @include('elements.streams.stream-chat-message',['message'=>$message, 'streamOwnerId' => $stream->user_id])
                    @endforeach
                @endif
                <div
                    class="d-{{$stream->messages->count() ? 'none' : 'flex'}} h-100 align-items-center justify-content-center no-chat-comments-label">
                    @if($stream->status == 'in-progress')
                        <div class="d-flex"><span>👋 {{__('There are no messages yet.')}} </span><span
                                class="d-none d-md-block d-lg-block d-xl-block">&nbsp;{{__("Say 'Hi!' to someone!")}}</span>
                        </div>
                    @else
                        <div class="d-flex"><span>⏲ {{__("Stream ended, can't add comments.")}} </span></div>
                    @endif

                </div>
            </div>
        </div>

        @if(!isset($streamEnded))
            <div class="conversation-writeup pt-1 pb-1 d-flex align-items-center mb-1">
                <form class="message-form w-100 pl-3">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="receiverID" id="receiverID" value="">
                    <textarea name="message" class="form-control messageBoxInput"
                              placeholder="{{__('Write a message..')}}" onkeyup="textAreaAdjust(this)"></textarea>
                </form>
                <div class="messenger-buttons-wrapper d-flex">
                    <button
                        class="btn btn-outline-primary btn-rounded-icon messenger-button send-message ml-3 mr-4 to-tooltip"
                        onClick="Stream.sendMessage({{$stream->id}})" data-placement="top"
                        title="{{__('Send message')}}">
                        <div class="d-flex justify-content-center align-items-center">
                            @include('elements.icon',['icon'=>'paper-plane','variant'=>''])
                        </div>
                    </button>
                </div>
            </div>
        @endif

    @else
        <div class="d-flex align-items-center justify-content-center mt-4 stream-chat-no-message">
            <span>🔒 {{__("Chat locked. Unlock the stream to see the messages.")}}</span></div>
    @endif
</div>
