@extends('layouts.user-no-nav')

@section('page_title', isset($coupon) ? __('Edit coupon') : __('New coupon'))

@section('content')
    <div class="container py-4">
        <h4 class="mb-3">{{ isset($coupon) ? __('Edit coupon') : __('New coupon') }}</h4>

        <form method="POST" action="{{ isset($coupon) ? route('my.coupons.update', ['id' => $coupon->id]) : route('my.coupons.store') }}">
            @csrf
            @if(isset($coupon))
                @method('PUT')
            @endif

            <div class="row">
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>{{__('Code')}}</label>
                        <input class="form-control @error('coupon_code') is-invalid @enderror" name="coupon_code" value="{{ old('coupon_code', $coupon->coupon_code ?? '') }}">
                        @error('coupon_code')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="col-12 col-md-6">
                    <div class="form-group">
                        <label>{{__('Payment method')}}</label>
                        <select class="form-control" name="payment_method">
                            @foreach(['all' => __('All'), 'credit_card' => __('Credit card'), 'pix' => __('PIX')] as $value => $label)
                                <option value="{{ $value }}" {{ old('payment_method', $coupon->payment_method ?? 'all') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('Discount type')}}</label>
                        <select class="form-control" name="discount_type">
                            <option value="percent" {{ old('discount_type', $coupon->discount_type ?? 'percent') === 'percent' ? 'selected' : '' }}>{{__('Percent')}}</option>
                            <option value="fixed" {{ old('discount_type', $coupon->discount_type ?? '') === 'fixed' ? 'selected' : '' }}>{{__('Fixed amount')}}</option>
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('Percent')}}</label>
                        <input class="form-control" type="number" step="0.01" name="discount_percent" value="{{ old('discount_percent', $coupon->discount_percent ?? '') }}">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('Fixed amount in cents')}}</label>
                        <input class="form-control" type="number" name="amount_off" value="{{ old('amount_off', $coupon->amount_off ?? '') }}">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('Expiration')}}</label>
                        <select class="form-control" name="expiration_type">
                            @foreach(['never' => __('Never'), 'date' => __('Date'), 'usage' => __('Usage limit')] as $value => $label)
                                <option value="{{ $value }}" {{ old('expiration_type', $coupon->expiration_type ?? 'never') === $value ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('Expires at')}}</label>
                        <input class="form-control" type="datetime-local" name="expires_at" value="{{ old('expires_at', isset($coupon) && $coupon->expires_at ? $coupon->expires_at->format('Y-m-d\TH:i') : '') }}">
                    </div>
                </div>
                <div class="col-12 col-md-4">
                    <div class="form-group">
                        <label>{{__('Usage limit')}}</label>
                        <input class="form-control" type="number" name="usage_limit" value="{{ old('usage_limit', $coupon->usage_limit ?? '') }}">
                    </div>
                </div>
            </div>

            <button class="btn btn-primary mb-0" type="submit">{{__('Save')}}</button>
            <a class="btn btn-outline-secondary mb-0" href="{{ route('my.coupons.index') }}">{{__('Cancel')}}</a>
        </form>
    </div>
@stop