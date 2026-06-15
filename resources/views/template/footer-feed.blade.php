<div class="{{getSetting('site.ads_sidebar_spot') ? 'mt-3' : 'mt-1'}} pt-3 text-center {{getSetting('site.ads_sidebar_spot') ? 'border-top' : ''}} widgets-footer">
    @foreach(GenericHelper::getFooterPublicPages() as $page)
        <a href="{{route('pages.get',['slug' => $page->slug])}}" target="" class="widgets-footer-link text-muted text-dark-r m-2">{{$page->translated('short_title') ? $page->translated('short_title') : $page->translated('title')}}</a>
    @endforeach
</div>
