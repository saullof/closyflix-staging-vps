@php
    $popularHashtags = \App\Providers\PostsHelperServiceProvider::getTopHashtags(10);
@endphp

<div class="card mt-3">
    <div class="card-body">
        <h5 class="card-title text-uppercase fs-point-85 font-weight-bold mb-3">{{__('Popular hashtags')}}</h5>

        @if(!empty($popularHashtags))
            <div class="d-flex flex-wrap mt-2 mb-n2">
                @foreach($popularHashtags as $row)
                    <div class="badge badge-pill border mr-2 mb-2">

                    <a href="{{ route('search.get', ['filter' => 'top', 'query' => '#'.$row['tag']])  }}"
                       class="text-dark-r text-hover">
                        #{{ $row['tag'] }}
                    </a>
                        <span class="ml-1 text-muted">({{ $row['uses'] }})</span>

                    </div>

                @endforeach
            </div>
        @else
            <div class="text-muted small mt-2">{{__('No hashtags yet.')}}</div>
        @endif
    </div>
</div>
