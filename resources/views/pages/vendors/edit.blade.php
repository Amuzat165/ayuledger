@extends('layouts.app')

@section('title', 'Edit Vendor')

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
                <li class="active"><i class="material-icons">archive</i> Edit Vendor</li>
            </ol>

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>Edit Vendor</h2>
                            <small>Update Vendor Information</small>
                        </div>
                        <div class="body">
                            <br/>
                            <form method="POST" action="{{ route('vendors.update', $params->defaults['id']) }}" class="form2">
                                @csrf
                                @method('PUT')
                                <div class="row clearfix">
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="name" name="name" type="text" class="form-control"
                                                       value="{{ old('name', $params->defaults['name']) }}" required>
                                                <label class="form-label">Name</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="address" name="address" type="text" class="form-control"
                                                       value="{{ old('address', $params->defaults['address']) }}" required>
                                                <label class="form-label">Address</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="phone_number" name="phone_number" type="text" class="form-control"
                                                       value="{{ old('phone_number', $params->defaults['phone_number']) }}" required>
                                                <label class="form-label">Phone Number</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <select id="account" name="account" class="form-control show-tick" required>
                                                    <option value="">-- Please select --</option>
                                                    @foreach($params->accounts as $id => $name)
                                                        <option value="{{ $name }}" {{ old('account', $params->defaults['account']) == $name? 'selected' : '' }}>
                                                            {{ $name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <label class="form-label">Account</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="terms_of_payment" name="terms_of_payment" type="text" class="form-control"
                                                       value="{{ old('terms_of_payment', $params->defaults['terms_of_payment']) }}" required>
                                                <label class="form-label">Terms of Payment</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-4 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <select id="branch_id" name="branch_id" class="form-control show-tick" required>
                                                    <option value="">-- Please select --</option>
                                                    @foreach($params->branches as $id => $name)
                                                        <option value="{{ $id }}" {{ old('branch_id', $params->defaults['branch_id']) == $id ? 'selected' : '' }}>
                                                            {{ $name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <label class="form-label">Branch</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                                        <div class="form-line">
                                            <button type="submit" class="btn btn-primary m-t-15 waves-effect">Update</button>
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
    <link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet" />
@endpush

@push('include-js')
    <script>
        // Include any JavaScript needed for this form
    </script>
@endpush
