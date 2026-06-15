@extends('layouts.user-no-nav')

@section('page_title', __('Coupons'))

@section('content')
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div>
                <h4 class="mb-1">{{__('Coupons')}}</h4>
                <div class="text-muted">{{__('Create discount codes for your subscribers.')}}</div>
            </div>
            <a href="{{ route('my.coupons.create') }}" class="btn btn-primary mb-0">{{__('New coupon')}}</a>
        </div>

        <div class="mb-3">
            <a class="btn btn-sm {{ $activeTab === 'active' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('my.coupons.index', ['type' => 'active']) }}">{{__('Active')}}</a>
            <a class="btn btn-sm {{ $activeTab === 'expired' ? 'btn-primary' : 'btn-outline-primary' }}" href="{{ route('my.coupons.index', ['type' => 'expired']) }}">{{__('Expired')}}</a>
        </div>

        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>{{__('Code')}}</th>
                        <th>{{__('Discount')}}</th>
                        <th>{{__('Payment method')}}</th>
                        <th>{{__('Uses')}}</th>
                        <th>{{__('Expires')}}</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($coupons as $coupon)
                        <tr>
                            <td class="font-weight-bold">{{ $coupon->coupon_code }}</td>
                            <td>
                                @if($coupon->discount_type === 'percent')
                                    {{ $coupon->discount_percent }}%
                                @else
                                    {{ \App\Providers\SettingsServiceProvider::getWebsiteFormattedAmount($coupon->amount_off / 100) }}
                                @endif
                            </td>
                            <td>{{ strtoupper(str_replace('_', ' ', $coupon->payment_method)) }}</td>
                            <td>{{ $coupon->times_used }}{{ $coupon->usage_limit ? ' / '.$coupon->usage_limit : '' }}</td>
                            <td>{{ $coupon->expires_at ? $coupon->expires_at->format('Y-m-d') : __('Never') }}</td>
                            <td class="text-right">
                                <a href="{{ route('profile.checkout', ['username' => Auth::user()->username, 'coupon_code' => $coupon->coupon_code]) }}" class="btn btn-sm btn-outline-secondary mb-0">{{__('Checkout link')}}</a>
                                <a href="{{ route('my.coupons.edit', ['id' => $coupon->id]) }}" class="btn btn-sm btn-outline-primary mb-0">{{__('Edit')}}</a>
                                <form action="{{ route('my.coupons.delete', ['id' => $coupon->id]) }}" method="POST" class="d-inline" onsubmit="return confirm({{ \Illuminate\Support\Js::from(__('Delete this coupon?')) }})">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger mb-0" type="submit">{{__('Delete')}}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-muted">{{__('No coupons yet.')}}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $coupons->links() }}
    </div>
@stop