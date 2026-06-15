{{-- Stories swiper skeleton --}}
@php($limit = $limit ?? 8)

<div class="stories-swiper-skeleton" aria-hidden="true">
    @for ($i = 0; $i < $limit; $i++)
        <div class="stories-swiper-skeleton__item">
            <div class="stories-swiper-skeleton__ring">
                <div class="stories-swiper-skeleton__avatar"></div>
            </div>

            <div class="stories-swiper-skeleton__name">
                <div class="stories-swiper-skeleton__line"></div>
            </div>
        </div>
    @endfor
</div>

<div id="svg-store" class="d-none">
    <div data-icon="verified">
        @include('elements.icon',['icon'=>'verified','centered'=>true,'classes'=>'ml-1 text-white', 'variant' => 'small'])
    </div>
</div>
