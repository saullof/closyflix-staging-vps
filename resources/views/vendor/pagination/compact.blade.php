@if ($paginator->hasPages())
    @php
        $currentPage = $paginator->currentPage();
        $lastPage = $paginator->lastPage();

        $pages = [1];

        if ($currentPage > 2) {
            $pages[] = $currentPage - 1;
        }

        $pages[] = $currentPage;

        if ($currentPage < $lastPage - 1) {
            $pages[] = $currentPage + 1;
        }

        $pages[] = $lastPage;
        $pages = array_values(array_unique(array_filter($pages, fn ($page) => $page >= 1 && $page <= $lastPage)));
    @endphp

    <nav class="pagination-compact-wrapper px-0 px-md-1 py-1" role="navigation" aria-label="{{ __('Pagination') }}">
        <ul class="pagination pagination-sm pagination-compact mb-0">
            @if ($paginator->onFirstPage())
                <li class="page-item disabled" aria-disabled="true" aria-label="{{ __('Previous') }}">
                    <span class="page-link" aria-hidden="true">&lsaquo;</span>
                </li>
            @else
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" aria-label="{{ __('Previous') }}">&lsaquo;</a>
                </li>
            @endif

            @if ($currentPage > 1)
                <li class="page-item d-none d-sm-block">
                    <a class="page-link" href="{{ $paginator->url(1) }}">1</a>
                </li>
            @endif

            @foreach($pages as $index => $page)
                @php
                    $previousPage = $pages[$index - 1] ?? null;
                @endphp

                @if($previousPage && $page - $previousPage > 1)
                    <li class="page-item disabled pagination-gap d-none d-sm-block" aria-disabled="true">
                        <span class="page-link">…</span>
                    </li>
                @endif

                @if ($page == 1 && $currentPage > 1)
                    @continue
                @endif

                @if ($page == $lastPage && $currentPage < $lastPage)
                    @continue
                @endif

                @if ($page == $currentPage)
                    <li class="page-item active" aria-current="page">
                        <span class="page-link">{{ $page }}</span>
                    </li>
                @else
                    <li class="page-item {{ ($page !== 1 && $page !== $lastPage) ? 'd-none d-sm-block' : '' }}">
                        <a class="page-link" href="{{ $paginator->url($page) }}">{{ $page }}</a>
                    </li>
                @endif
            @endforeach

            @if ($lastPage > 1 && $currentPage < $lastPage)
                @if ($lastPage - ($pages[count($pages) - 1] ?? $currentPage) > 1)
                    <li class="page-item disabled pagination-gap d-none d-sm-block" aria-disabled="true">
                        <span class="page-link">…</span>
                    </li>
                @endif

                <li class="page-item d-none d-sm-block">
                    <a class="page-link" href="{{ $paginator->url($lastPage) }}">{{ $lastPage }}</a>
                </li>
            @endif

            @if ($paginator->hasMorePages())
                <li class="page-item">
                    <a class="page-link" href="{{ $paginator->nextPageUrl() }}" rel="next" aria-label="{{ __('Next') }}">&rsaquo;</a>
                </li>
            @else
                <li class="page-item disabled" aria-disabled="true" aria-label="{{ __('Next') }}">
                    <span class="page-link" aria-hidden="true">&rsaquo;</span>
                </li>
            @endif
        </ul>
    </nav>
@endif
