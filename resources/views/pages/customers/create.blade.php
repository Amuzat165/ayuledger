@extends('layouts.form-layout')

<?php $params = (object)$params ?>

@section('subtitle', $params->title)

@section('container')
<x-breadcrumb :level="$params->resource" :location="$params->title" :as="$params->type" />
{{-- --}}
<div class="row clearfix">
  <div class="col-xs-12">
    <div class="card">
      <x-form.form-title-wrapper :h1="$params->title" p="customer" :as="$params->type">
        <x-form.form-method-post :action="$params->action">
          <div class="row clearfix">
              <x-form.form-select label="Branch" name="branch_id" :defaults="$params->defaults"
                                  :options="$params->branch_name" required colspan="6" />
              <x-form.form-input label="Customer Name" name="name" :defaults="$params->defaults" required
                                 colspan="6" />
            {{-- --}}
            <x-form.form-input label="Email Address" type="email" name="email" :defaults="$params->defaults" required
              colspan="6" />
            <x-form.form-input label="Phone Number" type="tel" name="phone" :defaults="$params->defaults" required
              colspan="6" />
            {{-- --}}
            <x-form.form-input label="Home Address" name="address" :defaults="$params->defaults" required />
            {{-- --}}
            <x-form.form-select label="Sales Ledger" name="income_expense_type_id" :defaults="$params->defaults"
              :options="$params->sales_ledger_options" required colspan="6" />
            <x-form.form-select label="Terms of Payment" name="terms_of_payment" :defaults="$params->defaults"
              :options="$params->terms_of_payment_options" required colspan="6" />
            <div class="col-sm-6" id="terms-of-payment-days-container" style="display: none;">
              <div class="form-group form-float">
                <div class="form-line">
                  <input type="number" class="form-control" id="terms_of_payment_in_days"
                         name="terms_of_payment_in_days"
                         value="{{ old('terms_of_payment_in_days', $params->defaults['terms_of_payment_in_days'] ?? null) }}"
                         min="1" />
                  <label class="form-label">Number of Days</label>
                </div>
              </div>
            </div>
            {{-- --}}
            <x-form.form-button as="Save" />
          </div>
        </x-form.form-method-post>
      </x-form.form-title-wrapper>
    </div>
  </div>
</div>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const termsOfPaymentSelect = document.querySelector('select[name="terms_of_payment"]');
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
