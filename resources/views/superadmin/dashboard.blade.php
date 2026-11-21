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
    @if (session('error')) <div class="alert alert-danger">{{ session('error') }}</div> @endif
    @if ($errors->any()) <div class="alert alert-danger">{{ $errors->first() }}</div> @endif
    <div class="table-responsive">
    <table class="table table-striped">
        <thead><tr><th>Business</th><th>Email</th><th>Subscription</th><th>Registration</th><th>Maximum users</th><th>Maximum branches</th><th>Actions</th></tr></thead>
        <tbody>
        @foreach($businesses as $business)
            @php
                $businessPlan = $plansBySlug->get($business->subscription_plan);
                $planUserLimit = $businessPlan ? $businessPlan->max_users : null;
                $planBranchLimit = $businessPlan ? $businessPlan->max_branches : null;
            @endphp
            <tr>
                <td>
                    <form id="business-limits-{{ $business->id }}" method="POST" action="{{ route('superadmin.business.limits', $business) }}">
                        @csrf
                    </form>
                    {{ $business->business_name }}
                </td>
                <td>{{ $business->business_email }}</td>
                <td>{{ $business->subscription_status }}</td>
                <td>{{ $business->registration_status }}</td>
                <td>
                    <input form="business-limits-{{ $business->id }}" class="form-control" type="number" min="0" max="100000" name="max_users_override" value="{{ $business->max_users_override }}" placeholder="{{ $planUserLimit === null ? 'No plan limit' : $planUserLimit }}" aria-label="User limit override for {{ $business->business_name }}">
                    <small class="text-muted">Plan: {{ $planUserLimit === null ? 'Unlimited' : $planUserLimit }}. Blank inherits.</small>
                </td>
                <td>
                    <input form="business-limits-{{ $business->id }}" class="form-control" type="number" min="0" max="100000" name="max_branches_override" value="{{ $business->max_branches_override }}" placeholder="{{ $planBranchLimit === null ? 'Unlimited' : $planBranchLimit }}" aria-label="Branch limit override for {{ $business->business_name }}">
                    <small class="text-muted">Plan: {{ $planBranchLimit === null ? 'Unlimited' : $planBranchLimit }}. Blank inherits.</small>
                </td>
                <td>
                    <button form="business-limits-{{ $business->id }}" class="btn btn-primary btn-sm mb-1" type="submit">Save limits</button>
                    <form method="POST" action="{{ route('superadmin.business.approve', $business) }}" style="display:inline">@csrf<button class="btn btn-success btn-sm">Approve</button></form>
                    <form method="POST" action="{{ route('superadmin.business.reject', $business) }}" style="display:inline">@csrf
                        <input type="hidden" name="admin_note" value="Registration rejected by administrator">
                        <button class="btn btn-danger btn-sm">Reject</button>
                    </form>
                    <form method="POST" action="{{ route('superadmin.business.destroy', $business) }}" style="display:inline"
                        onsubmit="return confirm('Permanently delete this business and all related data? This cannot be undone.');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger btn-sm">Delete</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
    {{ $businesses->links() }}
    <h3 class="mt-5">Payment history and subscriptions</h3>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
                <tr><th>Business</th><th>Account email</th><th>Plan</th><th>Method</th><th>Reference</th><th>Amount</th><th>Paid on</th><th>Proof</th><th>Payment status</th><th>Subscription</th><th>Expires</th><th>Reminder</th></tr>
            </thead>
            <tbody>
            @foreach($allPayments as $payment)
                @php
                    $paymentBusiness = $payment->business;
                    $expiresAt = optional($paymentBusiness)->subscription_expires_at;
                    $daysToExpiry = $expiresAt
                        ? \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($expiresAt)->startOfDay(), false)
                        : null;
                    $isExpiring = $daysToExpiry !== null && $daysToExpiry < 10;
                @endphp
                <tr class="{{ $isExpiring ? 'table-danger' : '' }}">
                    <td>{{ optional($paymentBusiness)->business_name }}</td>
                    <td>{{ optional($paymentBusiness)->business_email }}</td>
                    <td>{{ optional($paymentBusiness)->subscription_plan }} ({{ optional($paymentBusiness)->subscription_interval }})</td>
                    <td>{{ $payment->payment_method }}</td>
                    <td>{{ $payment->reference }}</td>
                    <td>₦{{ number_format((float) $payment->amount, 2) }}</td>
                    <td>{{ optional($payment->paid_on)->format('d M Y') ?: '—' }}</td>
                    <td>
                        @if($payment->proof_path)
                            <a href="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($payment->proof_path) }}" target="_blank" rel="noopener">View proof</a>
                        @else
                            —
                        @endif
                    </td>
                    <td>{{ $payment->status }}</td>
                    <td>{{ optional($paymentBusiness)->subscription_status }}</td>
                    <td>{{ optional($expiresAt)->format('d M Y') ?: '—' }}</td>
                    <td>
                        @if($paymentBusiness && $expiresAt)
                            <form method="POST" action="{{ route('superadmin.business.reminder', $paymentBusiness) }}">
                                @csrf
                                <button class="btn btn-warning btn-sm" type="submit">Send Reminder</button>
                            </form>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    {{ $allPayments->links() }}
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
