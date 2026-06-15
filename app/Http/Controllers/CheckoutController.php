<?php

namespace App\Http\Controllers;

use App\Model\Coupon;
use App\Model\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckoutController extends Controller
{
    public function index(Request $request, string $username, ?string $coupon_code = null)
    {
        if (!Auth::check()) {
            return redirect()->guest(route('login'));
        }

        $user = User::query()->where('username', $username)->firstOrFail();
        $coupon = null;

        if ($coupon_code) {
            $coupon = Coupon::query()
                ->valid()
                ->where('creator_id', $user->id)
                ->where('coupon_code', $coupon_code)
                ->first();
        }

        return view('pages.checkout', [
            'user' => $user,
            'coupon' => $coupon,
        ]);
    }
}
