@if(count($posts))
    @foreach($users as $user)
        @include('elements.search.users-list-element',['user'=>$user])
    @endforeach
@else

    <div class="d-flex justify-content-center align-items-center">
        <div class="col-10">
            <img src="{{asset('/img/no-content-available.svg')}}">
        </div>
    </div>
    <div class="d-flex justify-content-center align-items-center">
        <h5 class="text-center mb-2 mt-2">{{__('No users were found')}}</h5>
    </div>


@endif
