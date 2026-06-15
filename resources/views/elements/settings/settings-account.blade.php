@if(!Auth::user()->email_verified_at) @include('elements.resend-verification-email-box') @endif

<form method="POST" action="{{route('my.settings.account.save')}}">
    @csrf
    @if(session('success'))
        <div class="alert alert-success text-white font-weight-bold mt-2" role="alert">
            {{session('success')}}
            <button type="button" class="close" data-dismiss="alert" aria-label="{{__('Close')}}">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="form-group">
        <label for="username">{{__('Password')}}</label>
        @include('elements.password-field', [
            'id' => 'username',
            'name' => 'password',
            'errorName' => 'password',
            'autocomplete' => 'current-password',
        ])
        @if($errors->has('password'))
            <span class="invalid-feedback d-block" role="alert">
                <strong>{{$errors->first('password')}}</strong>
            </span>
        @endif
    </div>

    <div class="form-group">
        <label for="new_password">{{__('New password')}}</label>
        @include('elements.password-field', [
            'id' => 'new_password',
            'name' => 'new_password',
            'errorName' => 'new_password',
            'autocomplete' => 'new-password',
        ])
        @if($errors->has('new_password'))
            <span class="invalid-feedback d-block" role="alert">
                <strong>{{$errors->first('new_password')}}</strong>
            </span>
        @endif
    </div>

    <div class="form-group">
        <label for="confirm_password">{{__('Confirm password')}}</label>
        @include('elements.password-field', [
            'id' => 'confirm_password',
            'name' => 'confirm_password',
            'errorName' => 'confirm_password',
            'autocomplete' => 'new-password',
        ])
        @if($errors->has('confirm_password'))
            <span class="invalid-feedback d-block" role="alert">
                <strong>{{$errors->first('confirm_password')}}</strong>
            </span>
        @endif
    </div>
    <button class="btn btn-primary btn-block rounded mr-0" type="submit">{{__('Save')}}</button>

</form>
