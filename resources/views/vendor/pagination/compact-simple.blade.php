@if ($paginator->hasPages())
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

            <li class="page-item disabled pagination-status" aria-disabled="true">
                <span class="page-link">{{ $paginator->currentPage() }}</span>
            </li>

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
