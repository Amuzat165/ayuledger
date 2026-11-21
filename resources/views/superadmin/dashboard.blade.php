@extends('layouts.app')

@section('title', 'Super Admin Console')

@section('top-bar')
    @include('shared.header')
@stop

@section('left-sidebar')
    @include('shared.sidebar')
@stop

@section('content')
<section @if ($is_rtl) dir="rtl" @endif class="content super-admin-console">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Super Admin Console</h2>
        <div>
            <a href="{{ route('superadmin.database') }}" class="btn btn-outline-primary mr-2">Database browser</a>
            <a href="{{ route('superadmin.email-settings') }}" class="btn btn-outline-primary mr-2">Email & Notifications</a>
            <a href="{{ route('superadmin.site-builder') }}" class="btn btn-primary">Site builder</a>
        </div>
    </div>
    @if (session('status')) <div class="alert alert-success">{{ session('status') }}</div> @endif
    <table class="table table-striped">
        <thead><tr><th>Business</th><th>Email</th><th>Subscription</th><th>Registration</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($businesses as $business)
            <tr>
                <td>{{ $business->business_name }}</td>
                <td>{{ $business->business_email }}</td>
                <td>{{ $business->subscription_status }}</td>
                <td>{{ $business->registration_status }}</td>
                <td>
                    <form method="POST" action="{{ route('superadmin.business.approve', $business) }}" style="display:inline">@csrf<button class="btn btn-success btn-sm">Approve</button></form>
                    <form method="POST" action="{{ route('superadmin.business.reject', $business) }}" style="display:inline">@csrf
                        <input type="hidden" name="admin_note" value="Registration rejected by administrator">
                        <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $businesses->links() }}
    <h3 class="mt-5">Payment history and subscriptions</h3>
    <div class="table-responsive"><table class="table table-striped"><thead><tr><th>Business</th><th>Account email</th><th>Plan</th><th>Method</th><th>Reference</th><th>Amount</th><th>Paid on</th><th>Proof</th><th>Payment status</th><th>Subscription</th><th>Expires</th></tr></thead><tbody>
    @foreach($allPayments as $payment)<tr><td>{{ optional($payment->business)->business_name }}</td><td>{{ optional($payment->business)->business_email }}</td><td>{{ optional($payment->business)->subscription_plan }} ({{ optional($payment->business)->subscription_interval }})</td><td>{{ $payment->payment_method }}</td><td>{{ $payment->reference }}</td><td>₦{{ number_format((float)$payment->amount, 2) }}</td><td>{{ optional($payment->paid_on)->format('d M Y') ?: '—' }}</td><td>@if($payment->proof_path)<a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($payment->proof_path) }}" target="_blank" rel="noopener">View proof</a>@else—@endif</td><td>{{ $payment->status }}</td><td>{{ optional($payment->business)->subscription_status }}</td><td>{{ optional(optional($payment->business)->subscription_expires_at)->format('d M Y') ?: '—' }}</td></tr>@endforeach
    </tbody></table></div>{{ $allPayments->links() }}
    @if($payments->isNotEmpty())
    <h3 class="mt-5">Payment verifications</h3>
    <table class="table">
        <thead><tr><th>Business</th><th>Reference</th><th>Amount</th><th>Date</th><th></th></tr></thead>
        <tbody>
        @foreach($payments as $payment)
        <tr><td>{{ $payment->business->business_name }}</td><td>{{ $payment->reference }}</td><td>{{ $payment->amount }}</td><td>{{ $payment->paid_on }}</td><td>
            <form method="POST" action="{{ route('superadmin.payment.verify', $payment) }}">@csrf<button class="btn btn-primary btn-sm">Verify payment</button></form>
        </td></tr>
        @endforeach
        </tbody>
    </table>
    @endif
</div>
</section>
@endsection
