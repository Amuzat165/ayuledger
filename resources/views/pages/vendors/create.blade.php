@extends('layouts.app')

@section('title', 'Add Vendor')

@section('top-bar')
    @include('shared.header')
@stop

@section('left-sidebar')
    @include('shared.sidebar')
@stop

@section('content')
    <section class="content">
        <div class="container-fluid">
            <div class="block-header">
                <a class="btn btn-sm btn-info waves-effect" href="{{ route('vendors.index') }}">Back</a>
            </div>

            <ol class="breadcrumb breadcrumb-col-cyan pull-right">
                <li><a href="{{ route('dashboard') }}"><i class="material-icons">home</i> Home</a></li>
                <li><a href="{{ route('vendors.index') }}"><i class="fas fa-archive"></i> Vendors</a></li>
                <li class="active"><i class="material-icons">archive</i> Add Vendor</li>
            </ol>

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>Add Vendor</h2>
                            <small>Enter Vendor Information</small>
                        </div>
                        <div class="body">
                            <form method="POST" action="{{ route('vendors.store') }}" class="form2">
                                @csrf
                                <div class="row clearfix">
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="name" name="name" type="text" class="form-control"
                                                       value="{{ old('name') }}" required>
                                                <label class="form-label">Name</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="address" name="address" type="text" class="form-control"
                                                       value="{{ old('address') }}" required>
                                                <label class="form-label">Address</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="phone_number" name="phone_number" type="text" class="form-control"
                                                       value="{{ old('phone_number') }}" required>
                                                <label class="form-label">Phone Number</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <select id="branch_id" name="branch_id" class="form-control" required>
                                                    <option value="" disabled selected>Branch</option>
                                                    @foreach($params['branches'] as $branch_id => $branchName)
                                                        <option value="{{ $branch_id }}">{{ $branchName }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <select id="account" name="account" class="form-control" required>
                                                    <option value="" disabled selected>Select Account</option>
                                                    @foreach($params['accounts'] as $accountId => $accountName)
                                                        <option value="{{ $accountName }}">{{ $accountName }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <select id="terms_of_payment" name="terms_of_payment" class="form-control" required>
                                                    <option value="" disabled selected>Terms of payment</option>
                                                    <option value="cod">COD</option>
                                                    <option value="days">Pay in number of days</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6" id="days-input-container" style="display: none;">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="days" name="days" type="number" class="form-control"
                                                       value="{{ old('days') }}">
                                                <label class="form-label">Number of Days</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="form-line">
                                            <button type="submit" class="btn btn-primary m-t-15 waves-effect">Save</button>
                                        </div>
                                    </div>
                                </div>
                            </form>
                            @if(session('success'))
                                <div class="alert alert-success">
                                    {{ session('success') }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@stop

@push('include-css')
    <!-- Include any CSS files needed for this form -->
    <link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet" />
@endpush

@push('include-js')
    <!-- Include any JS files needed for this form -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const termsOfPaymentSelect = document.getElementById('terms_of_payment');
            const daysInputContainer = document.getElementById('days-input-container');
            const daysInput = document.getElementById('days');

            termsOfPaymentSelect.addEventListener('change', function () {
                if (this.value === 'days') {
                    daysInputContainer.style.display = 'block';
                    daysInput.required = true;
                } else {
                    daysInputContainer.style.display = 'none';
                    daysInput.required = false;
                    daysInput.value = ''; // Reset the value if hidden
                }
            });
        });
    </script>
@endpush
