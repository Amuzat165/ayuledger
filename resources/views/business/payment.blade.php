<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Complete payment | AYULEDGERS</title>
    @include('shared.favicon')
    <link href="{{ asset('asset/plugins/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <style>
        body{font-family:Inter,"Segoe UI",Arial,sans-serif;background:#f4f7fb;color:#101828}
        .topbar{height:72px;background:#fff;border-bottom:1px solid #eaecf0}.brand{font-size:21px;font-weight:800;color:#155eef}.secure{color:#667085;font-size:13px}
        .page{padding:44px 0 70px}.steps{display:flex;justify-content:center;gap:52px;margin-bottom:34px}.step{position:relative;color:#98a2b3;font-size:13px;font-weight:700;text-align:center}.step span{display:flex;width:34px;height:34px;border-radius:50%;align-items:center;justify-content:center;margin:0 auto 8px;background:#eaecf0}.step.done span,.step.active span{background:#155eef;color:#fff}.step.done,.step.active{color:#344054}.step:after{content:"";position:absolute;width:52px;height:2px;background:#eaecf0;left:calc(100% + 1px);top:16px}.step:last-child:after{display:none}
        .payment-card{background:#fff;border:1px solid #eaecf0;border-radius:18px;box-shadow:0 18px 50px rgba(16,24,40,.07);overflow:hidden}.card-title{padding:28px 32px;border-bottom:1px solid #eaecf0}.card-title h1{font-size:28px;font-weight:800;margin:0}.card-body{padding:32px}.label{font-weight:700;font-size:13px}.form-control{height:50px;border-radius:9px;border-color:#d0d5dd}.custom-file-input,.custom-file-label{height:50px;padding-top:13px;border-radius:9px}.custom-file-label:after{height:48px;padding-top:13px}.plan-summary{background:#102a56;color:#fff;border-radius:14px;padding:24px}.plan-summary h2{font-size:24px;font-weight:800}.plan-price{font-size:34px;font-weight:800}.bank-box{background:rgba(255,255,255,.08);border:1px solid rgba(255,255,255,.14);border-radius:10px;padding:16px;margin-top:18px;font-size:14px}.bank-box strong{display:block;font-size:16px;margin-bottom:5px}.submit-btn{height:52px;border-radius:9px;background:#155eef;border:0;font-weight:700}.notice{border-radius:10px;background:#eff4ff;color:#344054;padding:14px;font-size:13px}
        @media(max-width:767px){.page{padding-top:28px}.steps{gap:18px}.step:after{width:18px}.card-title,.card-body{padding:22px}.plan-summary{margin-top:24px}}
    </style>
</head>
<body>
<header class="topbar d-flex align-items-center"><div class="container d-flex justify-content-between align-items-center"><a class="brand" href="/">AYULEDGERS</a><span class="secure">🔒 Secure onboarding</span></div></header>
<main class="page"><div class="container">
    <div class="steps"><div class="step done"><span>✓</span>Account</div><div class="step active"><span>2</span>Payment</div><div class="step"><span>3</span>Activation</div></div>
    <div class="row justify-content-center"><div class="col-xl-10">
        @if(session('status'))<div class="alert alert-success">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="alert alert-danger">@foreach($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>@endif
        <div class="payment-card">
            <div class="card-title"><h1>Complete your subscription payment</h1><p class="text-muted mb-0 mt-2">Submit your transfer details and our team will activate your workspace.</p></div>
            <div class="card-body"><div class="row">
                <div class="col-lg-8 pr-lg-4">
                    <form method="POST" action="{{ route('business.payment.submit') }}" enctype="multipart/form-data">@csrf
                        <div class="mb-3">
                            <button type="button" id="paystack-button" class="btn btn-primary btn-block submit-btn" @if(!config('paystack.public_key')) disabled @endif>
                                Click Here to Pay Online
                            </button>
                            @if(!config('paystack.public_key'))
                                <small class="text-danger d-block mt-2">Paystack keys have not been configured yet.</small>
                            @endif
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-7"><label class="label">Payment reference / transaction ID</label><input name="reference" class="form-control" placeholder="e.g. TRX-123456789" value="{{ old('reference', $settings->payment_reference) }}" required></div>
                            <div class="form-group col-md-5"><label class="label">Payment method</label><select name="payment_method" class="form-control" required><option value="">Choose method</option>@foreach(['Bank transfer','Card payment','Direct Deposit','Mobile transfer','Online Payment'] as $method)<option value="{{ $method }}" {{ old('payment_method',$settings->payment_method)==$method?'selected':'' }}>{{ $method }}</option>@endforeach</select></div>
                        </div>
                        <div class="form-row">
                            <div class="form-group col-md-7"><label class="label">Amount paid ({{ $settings->subscription_interval === 'yearly' ? 'yearly' : 'monthly' }})</label><div class="input-group"><div class="input-group-prepend"><span class="input-group-text">₦</span></div><input type="number" step="0.01" min="0.01" name="amount" class="form-control" value="{{ old('amount', $settings->subscription_interval === 'yearly' ? ($plan->yearly_price ?? '') : ($plan->monthly_price ?? '')) }}" required></div></div>
                            <div class="form-group col-md-5"><label class="label">Payment date</label><input type="date" name="paid_on" class="form-control" value="{{ old('paid_on',date('Y-m-d')) }}" required></div>
                        </div>
                        <div class="form-group mb-4"><label class="label">Proof of payment</label><div class="custom-file"><input type="file" name="proof" class="custom-file-input" id="proof" accept=".jpg,.jpeg,.png,.pdf"><label class="custom-file-label" for="proof">Choose receipt, transfer slip, or screenshot</label></div><small class="text-muted">JPG, PNG or PDF, maximum 5 MB. Optional but recommended.</small></div>
                        <div class="notice mb-4">Make sure the reference and amount match the transfer details before submitting.</div>
                        <button class="btn btn-primary btn-block submit-btn">Submit payment for verification</button>
                    </form>
                </div>
                <div class="col-lg-4">
                    <aside class="plan-summary">
                        <span class="text-uppercase small">Selected subscription</span><h2 class="mt-2">{{ $plan->name ?? ucwords($settings->subscription_plan ?: 'Standard') }} plan</h2>
                        <div class="plan-price mt-3">₦{{ number_format($settings->subscription_interval === 'yearly' ? ($plan->yearly_price ?? 0) : ($plan->monthly_price ?? 0)) }}<small style="font-size:14px;font-weight:500"> / {{ $settings->subscription_interval === 'yearly' ? 'year' : 'month' }}</small></div>
                        <hr style="border-color:rgba(255,255,255,.18)">
                        <p class="mb-1 small">Business</p><strong>{{ $settings->business_name }}</strong>
                        <div class="bank-box"><span>Payment instructions</span><strong>AYULEDGERS payments</strong><div>Bank: Contact support for the active account details</div><div class="mt-2">Include your business name in the transfer reference.</div></div>
                    </aside>
                </div>
            </div></div>
        </div>
    </div></div>
</div></main>
@if(config('paystack.public_key'))
<script src="https://js.paystack.co/v1/inline.js"></script>
@endif
<script>
document.getElementById('proof').addEventListener('change',function(){this.nextElementSibling.textContent=this.files[0]?this.files[0].name:'Choose receipt, transfer slip, or screenshot';});

@if(config('paystack.public_key'))
document.getElementById('paystack-button').addEventListener('click', function () {
    var handler = PaystackPop.setup({
        key: @json(config('paystack.public_key')),
        email: @json(auth()->user()->email),
        amount: {{ (int) round(($settings->subscription_interval === 'yearly' ? ($plan->yearly_price ?? 0) : ($plan->monthly_price ?? 0)) * 100) }},
        currency: 'NGN',
        ref: 'AYU-' + Date.now(),
        metadata: {business_id: {{ (int) $settings->id }}},
        callback: function(response) {
            fetch(@json(route('business.payment.paystack.verify')), {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': @json(csrf_token()), 'Accept': 'application/json'},
                body: JSON.stringify({reference: response.reference})
            }).then(function(result) { return result.json().then(function(data) { return {status: result.status, data: data}; }); })
            .then(function(result) {
                if (!result.status || !result.data.success) throw new Error(result.data.message || 'Payment verification failed.');
                if (result.data.redirect) {
                    window.location.assign(result.data.redirect);
                    return;
                }
                alert('Payment successful.');
            }).catch(function(error) { alert(error.message); });
        },
        onClose: function() {}
    });
    handler.openIframe();
});
@endif
</script>
</body>
</html>
