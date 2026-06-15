@extends('layouts.install')
@section('page_title', __('Install the script'))
@section('scripts')
    {!!
        Minify::javascript([
            '/js/Installer.js',
         ])->withFullUrl()
    !!}
@stop
@section('content')
    <div class="container-fluid installer-bg">

        <div class="row no-gutter d-flex justify-content-center align-items-center min-vh-100">
            <div class="col-4">
                <div class="d-flex justify-content-center pb-5">
                    <a href="{{route('installer.install')}}">
                        <img class="brand-logo" src="{{asset('/img/logo-black.png')}}">
                    </a>
                </div>
                <div class="col card shadow-sm rounded-xl">
                    <div class="card-body">
                        <h4 class="card-title mt-2 mb-1 font-weight-bold">{{__('General info')}}</h4>
                        <p class="text-sm text-muted">{{__("Almost there, just a few more things required.")}}</p>
                        <hr/>
                        @if(session('error'))
                            <div class="alert alert-danger text-white font-weight-bold mt-2" role="alert">
                                {{session('error')}}
                                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                        @endif
                        <form method="POST" action="{{route('installer.beginInstall')}}" class="finalInstallStepForm">
                            @csrf
                            <div class="form-group ">
                                <label for="site_title" class="col-form-label">{{ __('Site name') }}</label>
                                <div class="">
                                    <input id="site_title" type="site_title" class="form-control @error('site_title') is-invalid @enderror"  name="site_title" value="{{ old('site_title') }}" autocomplete="site_title" autofocus>
                                    @error('site_title')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group ">
                                <label for="app_url" class="col-form-label">{{ __('Website url') }}</label>
                                <div class="">
                                    <input id="app_url" type="app_url" class="form-control @error('app_url') is-invalid @enderror"  name="app_url" value="{{ old('app_url') ? old('app_url') : ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] }}" autocomplete="app_url" autofocus>
                                    <small class="d-flex align-items-center mt-1"> @include('elements.icon',['icon'=>'information-circle-outline', 'variant' => 'small','centered'=>false]) {{__("Domain url or full installation path")}}</small>
                                    @error('app_url')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group ">
                                <label for="email" class="col-form-label">{{ __('Admin email') }}</label>
                                <div class="">
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror"  name="email" value="{{ old('email') }}" autocomplete="email" autofocus>
                                    @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group ">
                                <label for="password" class="col-form-label">{{ __('Admin password') }}</label>
                                <div class="">
                                    @include('elements.password-field', [
                                        'id' => 'password',
                                        'name' => 'password',
                                        'errorName' => 'password',
                                        'value' => old('password'),
                                        'autocomplete' => 'password',
                                        'autofocus' => true,
                                    ])
                                    @error('password')
                                    <span class="invalid-feedback d-block" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group ">
                                <label for="license" class="col-form-label">{{ __('Script License') }}</label>
                                <div class="">
                                    <input id="license" type="license" class="form-control @error('license') is-invalid @enderror"  name="license" value="{{ old('license') }}" autocomplete="license" autofocus>
                                    @error('license')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                    <small>You can grab the code from the envato <a href="https://codecanyon.net/downloads" rel="nofollow" target="_blank">Downloads</a> area or the purchase email.</small>
                                </div>
                            </div>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <a href="{{route('installer.install').'?step=2'}}" class="">{{__("Back")}}</a>
                                <button type="submit" class="btn btn-primary m-0">{{__("Install script")}}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
