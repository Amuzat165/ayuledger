<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;
use App\BusinessPaymentVerification;
use App\Services\TenantContext;
use App\SubscriptionPlan;
use Illuminate\Http\Request;
use App\Services\PlatformEmail;
use App\Tenant;
use App\TenantMembership;

class BusinessOnboardingController extends Controller
{
    public function payment(Request $request, TenantContext $tenantContext)
    {
        $settings = $this->settings($request, $tenantContext);
        $plan = SubscriptionPlan::where('slug', $settings->subscription_plan)->first()
            ?: SubscriptionPlan::where('slug', $settings->subscription_plan === 'demo' ? 'standard' : 'basic')->first();
        return view('business.payment', compact('settings', 'plan'));
    }

    public function submitPayment(Request $request, TenantContext $tenantContext)
    {
        $settings = $this->settings($request, $tenantContext);
        if (in_array($request->input('payment_method'), ['Card payment', 'Paystack'], true)) {
            return back()->withErrors(['payment_method' => 'Paystack payments are confirmed automatically. Please use Paystack to complete your payment.']);
        }
        $validated = $request->validate([
            'reference' => 'required|string|max:191',
            'payment_method' => 'required|string|max:191',
            'amount' => 'required|numeric|min:0.01',
            'paid_on' => 'required|date',
            'proof' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $plan = SubscriptionPlan::where('slug', $settings->subscription_plan)->first();
        $expectedAmount = (float) ($settings->subscription_interval === 'yearly'
            ? optional($plan)->yearly_price
            : optional($plan)->monthly_price);
        if ($expectedAmount <= 0 || abs((float) $validated['amount'] - $expectedAmount) > 0.01) {
            return back()->withErrors(['amount' => 'The amount does not match the selected plan and billing period.'])->withInput();
        }

        $proofPath = null;
        if ($request->hasFile('proof')) {
            $proofPath = $request->file('proof')->store('payment-proofs', 'public');
        }

        BusinessPaymentVerification::create([
            'business_id' => $settings->id,
            'reference' => $validated['reference'],
            'payment_method' => $validated['payment_method'],
            'amount' => $validated['amount'],
            'paid_on' => $validated['paid_on'],
            'proof_path' => $proofPath,
        ]);

        $settings->update([
            'payment_reference' => $validated['reference'],
            'payment_method' => $validated['payment_method'],
            'payment_amount' => $validated['amount'],
            'subscription_status' => 'verification_pending',
            'payment_submitted_at' => now(),
        ]);

        app(PlatformEmail::class)->notifyPayment($settings, $request->user(), (object) $validated, 'pending');

        return redirect()->route('business.payment')->with('status', 'Payment details submitted for verification.');
    }

    public function verifyPaystack(Request $request, TenantContext $tenantContext)
    {
        $request->validate(['reference' => 'required|string|max:191']);

        $secretKey = config('paystack.secret_key');
        if (!$secretKey) {
            return response()->json(['success' => false, 'message' => 'Paystack is not configured.'], 503);
        }

        $response = Http::withToken($secretKey)
            ->acceptJson()
            ->get('https://api.paystack.co/transaction/verify/' . rawurlencode($request->reference));

        if (!$response->successful() || !($response->json('data.status') === 'success')) {
            return response()->json([
                'success' => false,
                'message' => $response->json('message', 'Paystack could not verify this payment.'),
            ], 422);
        }

        $settings = $this->settings($request, $tenantContext);
        $data = $response->json('data');
        if (strcasecmp((string) data_get($data, 'customer.email'), (string) $request->user()->email) !== 0
            || (isset($data['metadata']['business_id']) && (int) $data['metadata']['business_id'] !== (int) $settings->id)) {
            return response()->json(['success' => false, 'message' => 'This Paystack transaction does not match your account.'], 422);
        }
        $plan = SubscriptionPlan::where('slug', $settings->subscription_plan)->first();
        $interval = $settings->subscription_interval === 'yearly' ? 'yearly' : 'monthly';
        $expectedAmount = (int) round((float) (optional($plan)->{$interval . '_price'} ?? 0) * 100);
        $paidAmount = (int) $data['amount'];
        if ($expectedAmount <= 0 || abs($paidAmount - $expectedAmount) > 1) {
            return response()->json(['success' => false, 'message' => 'The paid amount does not match the subscription amount.'], 422);
        }

        $reference = $data['reference'];
        if (\App\BusinessPaymentVerification::where('reference', $reference)->where('business_id', '<>', $settings->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'This payment reference is already linked to another account.'], 422);
        }
        $paidOn = isset($data['paid_at']) ? date('Y-m-d', strtotime($data['paid_at'])) : now()->toDateString();
        $existingPayment = \App\BusinessPaymentVerification::where('reference', $reference)->where('business_id', $settings->id)->first();
        if ($existingPayment && $existingPayment->status === 'verified') {
            return $this->finishPaystackLogin($request, [
                'reference' => $reference, 'amount' => $paidAmount / 100, 'currency' => $data['currency'],
                'method' => 'Paystack', 'paid_at' => $data['paid_at'] ?? null, 'already_processed' => true,
            ]);
        }
        $payment = \App\BusinessPaymentVerification::firstOrCreate(['reference' => $reference, 'business_id' => $settings->id], [
            'payment_method' => 'Paystack', 'amount' => $paidAmount / 100, 'paid_on' => $paidOn, 'status' => 'verified',
        ]);
        $interval = $settings->subscription_interval === 'yearly' ? 'yearly' : 'monthly';
        $start = $settings->subscription_expires_at && \Carbon\Carbon::parse($settings->subscription_expires_at)->isFuture()
            ? \Carbon\Carbon::parse($settings->subscription_expires_at) : now();
        $settings->update([
            'payment_reference' => $reference, 'payment_method' => 'Paystack', 'payment_amount' => $paidAmount / 100,
            'payment_submitted_at' => now(), 'subscription_status' => 'active', 'registration_status' => 'approved',
            'subscription_starts_at' => $settings->subscription_starts_at ?: now()->toDateString(),
            'subscription_expires_at' => ($interval === 'yearly' ? $start->copy()->addYear() : $start->copy()->addMonth())->toDateString(),
            'approved_at' => $settings->approved_at ?: now(),
        ]);
        Tenant::whereKey($settings->tenant_id)->update(['status' => 'active']);
        TenantMembership::where('tenant_id', $settings->tenant_id)->update(['status' => 'active']);
        app(PlatformEmail::class)->notifyPayment($settings->fresh(), $request->user(), $payment, 'successful');

        return $this->finishPaystackLogin($request, [
            'reference' => $data['reference'],
            'amount' => $data['amount'] / 100,
            'currency' => $data['currency'],
            'method' => 'Card payment',
            'paid_at' => $data['paid_at'] ?? null,
        ]);
    }

    private function finishPaystackLogin(Request $request, array $paymentDetails)
    {
        $email = $request->user()->email;
        auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        $request->session()->flash('status', 'Payment successful. Your subscription is active. Sign in with your registration email and password to continue.');
        $request->session()->flash('payment_login_email', $email);

        return response()->json(array_merge($paymentDetails, [
            'success' => true,
            'redirect' => route('login'),
        ]));
    }

    public function feedback(Request $request)
    {
        $request->validate(['subject' => 'required|max:191', 'message' => 'required|max:5000']);
        \App\BusinessFeedback::create([
            'business_id' => $request->user()->business_id,
            'user_id' => $request->user()->id,
            'subject' => $request->subject,
            'message' => $request->message,
        ]);
        return back()->with('status', 'Thank you for your feedback.');
    }

    private function settings(Request $request, TenantContext $tenantContext)
    {
        $tenant = $tenantContext->currentTenant($request);
        if ($tenant) {
            return \App\BusinessSetting::where('tenant_id', $tenant->id)->firstOrFail();
        }

        return $request->user()->business;
    }
}
