@extends('layouts.user-no-nav')

@section('page_title', __('Complete your access'))

@section('content')
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="card">
                    <div class="card-body p-4 text-center">
                        <img src="{{ $checkout->recipient->avatar }}" class="rounded-circle user-avatar mb-3" width="80" height="80">
                        <h4 class="mb-2">{{ __('Complete your access') }}</h4>
                        <p class="text-muted mb-4">
                            @if($checkout->status === \App\Model\GuestCheckout::PENDING_STATUS)
                                {{ __('Your PIX payment was created. Once Stripe confirms it, enter or create your account and we will activate your access automatically.') }}
                            @elseif($checkout->status === \App\Model\GuestCheckout::CLAIMED_STATUS)
                                {{ __('This purchase has already been linked to an account.') }}
                            @else
                                {{ __('Payment confirmed. Enter or create your account to activate access to') }} {{ $checkout->recipient->name }}.
                            @endif
                        </p>

                        @if($checkout->status !== \App\Model\GuestCheckout::CLAIMED_STATUS)
                            <div class="d-flex flex-column flex-sm-row justify-content-center">
                                <a href="{{ route('register') }}" class="btn bg-gradient-primary btn-round mb-2 mb-sm-0 mr-sm-2">
                                    {{ __('Create account') }}
                                </a>
                                <a href="{{ route('login') }}" class="btn btn-outline-primary btn-round mb-0">
                                    {{ __('I already have an account') }}
                                </a>
                            </div>
                        @else
                            <a href="{{ route('login') }}" class="btn bg-gradient-primary btn-round mb-0">
                                {{ __('Login') }}
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
