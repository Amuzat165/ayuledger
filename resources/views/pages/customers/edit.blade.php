@extends('layouts.form-layout')

@section('subtitle', $params->title)

@section('container')
    <x-breadcrumb :level="$params->resource" :location="$params->title" :as="$params->type" />
    {{-- --}}
    <div class="row clearfix">
        <div class="col-xs-12">
            <div class="card">
                <x-form.form-title-wrapper :h1="$params->title" p="customer" :as="$params->type">
                    {{-- Use PUT method for update --}}
                    <form action="{{ $params->action }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="row clearfix">
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="branch">Branch</label>
                                    <select class="form-control" id="branch_id" name="branch_id" required>
                                        @foreach ($params->branch_name as $id => $name)
                                            <option value="{{ $id }}" {{ $params->defaults->branch_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="name">Customer Name</label>
                                    <input type="text" class="form-control" id="name" name="name" value="{{ $params->defaults->name }}" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="email">Email Address</label>
                                    <input type="email" class="form-control" id="email" name="email" value="{{ $params->defaults->email }}" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="phone">Phone Number</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" value="{{ $params->defaults->phone }}" required>
                                </div>
                            </div>
                            <div class="col-sm-12">
                                <div class="form-group">
                                    <label for="address">Home Address</label>
                                    <input type="text" class="form-control" id="address" name="address" value="{{ $params->defaults->address }}" required>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="income_expense_type_id">Sales Ledger</label>
                                    <select class="form-control" id="income_expense_type_id" name="income_expense_type_id" required>
                                        @foreach ($params->sales_ledger_options as $id => $name)
                                            <option value="{{ $id }}" {{ $params->defaults->income_expense_type_id == $id ? 'selected' : '' }}>{{ $name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label for="terms_of_payment">Terms of Payment</label>
                                    <select class="form-control" id="terms_of_payment" name="terms_of_payment" required>
                                        @foreach ($params->terms_of_payment_options as $option)
                                            <option value="{{ $option }}" {{ $params->defaults->terms_of_payment == $option ? 'selected' : '' }}>{{ $option }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-sm-6" id="terms-of-payment-days-container" style="display: none;">
                                <div class="form-group">
                                    <label for="terms_of_payment_in_days">Number of Days</label>
                                    <input type="number" class="form-control" id="terms_of_payment_in_days" name="terms_of_payment_in_days" value="{{ old('terms_of_payment_in_days', $params->defaults->terms_of_payment_in_days ?? null) }}" min="1">
                                </div>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save</button>
                    </form>
                </x-form.form-title-wrapper>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const termsOfPaymentSelect = document.getElementById('terms_of_payment');
            const daysContainer = document.getElementById('terms-of-payment-days-container');
            const daysInput = document.getElementById('terms_of_payment_in_days');

            const toggleDaysInput = function () {
                const showDays = termsOfPaymentSelect && termsOfPaymentSelect.value === 'Pay in number of days';
                daysContainer.style.display = showDays ? 'block' : 'none';
                daysInput.required = showDays;
                if (!showDays) {
                    daysInput.value = '';
                }
            };

            if (termsOfPaymentSelect) {
                termsOfPaymentSelect.addEventListener('change', toggleDaysInput);
                toggleDaysInput();
            }
        });
    </script>
@endsection
