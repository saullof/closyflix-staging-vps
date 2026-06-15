<div class="px-3 new-post-comment-area">
    <div class="d-flex justify-content-center align-items-center">
        <img class="rounded-circle mr-2 ml-3" src="{{Auth::user()->avatar}}">
        <div class="input-group">
            <div class="hl-textarea-wrap w-100">
                <div class="hl-backdrop" aria-hidden="true">
                    <div class="hl-highlights"></div>
                </div>

                <textarea name="message"
                          class="form-control comment-textarea comment-text new-comment-textarea hl-textarea"
                          placeholder="{{__('Write a message..')}}"
                          onkeyup="textAreaAdjust(this)"></textarea>
            </div>

            <span class="invalid-feedback pl-4 text-bold" role="alert"></span>
        </div>
        <div class="pl-2">
            <button class="btn btn-outline-primary btn-rounded-icon btn-sm" onclick="Post.addComment({{$post->id}})">
                <div class="d-flex justify-content-center align-items-center">
                    @include('elements.icon',['icon'=>'paper-plane','size'=>'medium'])
                </div>
            </button>
        </div>
    </div>
</div>
