@php
    $gridPreferredType = in_array(request()->get('filter'), ['image', 'video'], true)
        ? request()->get('filter')
        : null;
    $gridAttachment = $gridPreferredType
        ? $post->attachments->first(function ($attachment) use ($gridPreferredType) {
            return AttachmentHelper::getAttachmentType($attachment->type) === $gridPreferredType;
        })
        : null;
    $gridAttachment = $gridAttachment ?: $post->attachments->first();
    $gridAttachmentType = $gridAttachment ? AttachmentHelper::getAttachmentType($gridAttachment->type) : null;
    $gridIsOwner = Auth::check() && Auth::id() === $post->user_id;
    $gridIsPackLocked = !$gridIsOwner
        && $post->price > 0
        && (!Auth::check() || !PostsHelper::hasUserUnlockedPost($post->postPurchases));
    $gridCanShowOriginal = PostsHelper::isPostSubscriptionUnlocked($post) && !$gridIsPackLocked;
    $gridBackgroundImage = null;

    if ($gridAttachment && $gridCanShowOriginal) {
        if ($gridAttachmentType === 'image') {
            $gridBackgroundImage = $gridAttachment->path;
        } elseif ($gridAttachmentType === 'video' && $gridAttachment->has_thumbnail) {
            $gridBackgroundImage = $gridAttachment->thumbnail;
        }
    } elseif ($gridAttachment) {
        $gridPreview = AttachmentHelper::getPostPreviewData($post);
        $gridBackgroundImage = $gridPreview['backgroundImage'];
    }

    $gridMediaCounts = PostsHelper::getAttachmentsTypesCount($post->attachments);
    $gridIsLocked = !$gridCanShowOriginal;
    $gridAccessLabel = $post->price > 0
        ? 'Pack'
        : ($post->is_free ? 'Gratuito' : 'Assinatura');
@endphp

<a class="profile-grid-card"
   href="{{route('posts.get',['post_id'=>$post->id,'username'=>$post->user->username])}}"
   onclick="event.preventDefault(); PostsPaginator.goToPostPageKeepingNav({{$post->id}},{{$post->postPage}},this.href)">
    <span class="profile-grid-media {{$gridBackgroundImage ? 'has-image' : 'has-fallback'}}"
          @if($gridBackgroundImage) style="background-image: url('{{$gridBackgroundImage}}');" @endif>
        @if(!$gridBackgroundImage)
            <span class="profile-grid-fallback-icon">
                @include('elements.icon',[
                    'icon'=>$gridAttachmentType === 'video' ? 'videocam-outline' : ($gridAttachmentType === 'audio' ? 'musical-notes-outline' : 'document-text-outline'),
                    'centered'=>true
                ])
            </span>
        @endif

        <span class="profile-grid-shade"></span>

        <span class="profile-grid-badges">
            <span class="profile-grid-badge">
                @if($gridIsLocked)
                    @include('elements.icon',['icon'=>'lock-closed-outline','centered'=>true])
                @endif
                {{$gridAccessLabel}}
            </span>
        </span>

        <span class="profile-grid-media-info">
            @if(($gridMediaCounts['image'] ?? 0) > 0)
                <span>
                    @include('elements.icon',['icon'=>'images-outline','centered'=>true])
                    {{$gridMediaCounts['image']}}
                </span>
            @endif
            @if(($gridMediaCounts['video'] ?? 0) > 0)
                <span>
                    @include('elements.icon',['icon'=>'videocam-outline','centered'=>true])
                    {{$gridMediaCounts['video']}}
                </span>
            @endif
        </span>
    </span>
</a>
