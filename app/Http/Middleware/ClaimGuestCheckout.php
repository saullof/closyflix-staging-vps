<?php

namespace App\Http\Middleware;

use App\Services\GuestCheckoutService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClaimGuestCheckout
{
    public function handle(Request $request, Closure $next)
    {
        if (Auth::check()) {
            app(GuestCheckoutService::class)->claimPendingCheckoutFromSession(Auth::user());
        }

        return $next($request);
    }
}
