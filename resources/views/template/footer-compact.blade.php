<footer class="d-none d-md-block py-3">
    <div class="container">
        <div class="copyRightInfo d-flex flex-column-reverse flex-md-row d-md-flex justify-content-md-between">
            <div class="d-flex flex-column flex-md-row">
                <div class="d-flex align-items-center justify-content-center mt-3 mt-md-0 mr-2">
                    <p class="mb-0 text-dark-r ">&copy; {{date('Y')}} {{getSetting('site.name')}}  {{-- {{__('All rights reserved.')}}--}}</p> <span class="text-muted ml-2">|</span>
                </div>
                <a href="{{route('contact')}}" class="text-dark-r mr-2 mt-0 mt-md-2 mb-2 ml-2 ml-md-0">
                    {{__('Contact page')}}
                </a>
                @foreach(GenericHelper::getFooterPublicPages() as $page)
                    <a href="{{route('pages.get',['slug' => $page->slug])}}" target="" class="text-dark-r m-2">{{__($page->short_title ? $page->translated('short_title') : $page->translated('title'))}}</a>
                @endforeach
            </div>

            <div class="d-flex justify-content-md-center align-items-center mt-4 mt-md-0 footer-social-links">
                <div class="d-flex justify-content-center">
                    @include('elements.footer.dark-mode-switcher')
                    @include('elements.footer.direction-switcher')
                    @include('elements.footer.language-switcher')
                </div>
            </div>
        </div>
    </div>
</footer>
