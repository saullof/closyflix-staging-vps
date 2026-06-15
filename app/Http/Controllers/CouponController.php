<?php

namespace App\Http\Controllers;

use App\Model\Coupon;
use App\Model\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CouponController extends Controller
{
    public function index(Request $request)
    {
        $activeTab = $request->input('type', 'active');
        $query = Coupon::query()->where('creator_id', Auth::id());

        if ($activeTab === 'active') {
            $query->valid();
        } else {
            $query->where(function ($query) {
                $query->where('status', '!=', 'active')
                    ->orWhere('expires_at', '<=', now())
                    ->orWhereRaw('times_used >= usage_limit');
            });
        }

        return view('pages.coupons', [
            'coupons' => $query->latest()->paginate(10),
            'activeTab' => $activeTab,
        ]);
    }

    public function create()
    {
        return view('pages.coupons.form');
    }

    public function store(Request $request)
    {
        Coupon::create($this->validatedCouponData($request));

        return redirect()->route('my.coupons.index')->with('success', __('Coupon created successfully.'));
    }

    public function edit(int $id)
    {
        return view('pages.coupons.form', [
            'coupon' => Coupon::query()->where('creator_id', Auth::id())->findOrFail($id),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $coupon = Coupon::query()->where('creator_id', Auth::id())->findOrFail($id);
        $coupon->update($this->validatedCouponData($request, $coupon));

        return redirect()->route('my.coupons.index')->with('success', __('Coupon updated successfully.'));
    }

    public function delete(int $id)
    {
        Coupon::query()->where('creator_id', Auth::id())->findOrFail($id)->delete();

        return redirect()->route('my.coupons.index')->with('success', __('Coupon deleted successfully.'));
    }

    public function validateCoupon(Request $request)
    {
        $validated = $request->validate([
            'coupon' => ['required', 'string', 'max:255'],
            'creator_id' => ['nullable', 'integer'],
            'provider' => ['nullable', 'string', Rule::in(Transaction::NEW_PAYMENT_PROVIDERS)],
        ]);

        $query = Coupon::query()
            ->valid()
            ->where('coupon_code', $validated['coupon']);

        if (!empty($validated['creator_id'])) {
            $query->where('creator_id', $validated['creator_id']);
        }

        $coupon = $query->first();
        $provider = $validated['provider'] ?? null;
        if (!$coupon || ($provider && !$coupon->supportsPaymentProvider($provider))) {
            return response()->json(['success' => false, 'message' => __('Invalid coupon.')], 422);
        }

        return response()->json([
            'success' => true,
            'coupon_code' => $coupon->coupon_code,
            'payment_method' => $coupon->payment_method,
            'discount' => [
                'type' => $coupon->discount_type,
                'value' => $coupon->discount_value,
            ],
        ]);
    }

    public function disable(int $id)
    {
        Coupon::query()->where('creator_id', Auth::id())->findOrFail($id)->update(['status' => 'disabled']);

        return back()->with('success', __('Coupon disabled successfully.'));
    }

    public function enable(int $id)
    {
        Coupon::query()->where('creator_id', Auth::id())->findOrFail($id)->update(['status' => 'active']);

        return back()->with('success', __('Coupon enabled successfully.'));
    }

    private function validatedCouponData(Request $request, ?Coupon $coupon = null): array
    {
        $validated = $request->validate([
            'coupon_code' => [
                'required',
                'max:20',
                Rule::unique('coupons')->where(fn ($query) => $query->where('creator_id', Auth::id()))->ignore($coupon?->id),
            ],
            'discount_type' => ['required', Rule::in(['percent', 'fixed'])],
            'discount_percent' => ['required_if:discount_type,percent', 'nullable', 'numeric', 'between:0.01,99.99'],
            'amount_off' => ['required_if:discount_type,fixed', 'nullable', 'integer', 'min:1'],
            'expiration_type' => ['required', Rule::in(['never', 'date', 'usage'])],
            'duration_in_months' => ['nullable', 'integer', 'min:1', 'max:12'],
            'expires_at' => ['required_if:expiration_type,date', 'nullable', 'date', 'after:now'],
            'usage_limit' => ['required_if:expiration_type,usage', 'nullable', 'integer', 'min:1'],
            'payment_method' => ['required', Rule::in(['credit_card', 'pix', 'all'])],
        ]);

        return array_merge($validated, [
            'creator_id' => Auth::id(),
            'discount_percent' => $validated['discount_type'] === 'percent' ? $validated['discount_percent'] : null,
            'amount_off' => $validated['discount_type'] === 'fixed' ? $validated['amount_off'] : null,
            'duration_in_months' => $validated['expiration_type'] === 'date' ? ($validated['duration_in_months'] ?? null) : null,
            'expires_at' => $validated['expiration_type'] === 'date' ? $validated['expires_at'] : null,
            'usage_limit' => $validated['expiration_type'] === 'usage' ? $validated['usage_limit'] : null,
        ]);
    }
}