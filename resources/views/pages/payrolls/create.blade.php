@extends('layouts.app')

@section('title', 'Add Payroll')

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
                <a class="btn btn-sm btn-info waves-effect" href="{{ route('payrolls.index') }}">Back</a>
            </div>

            <ol class="breadcrumb breadcrumb-col-cyan pull-right">
                <li><a href="{{ route('dashboard') }}"><i class="material-icons">home</i> Home</a></li>
                <li><a href="{{ route('payrolls.index') }}"><i class="fas fa-archive"></i> Payrolls</a></li>
                <li class="active"><i class="material-icons">archive</i> Add Payroll</li>
            </ol>

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>Add Payroll</h2>
                            <small>Enter Payroll Information</small>
                        </div>
                        <div class="body">
                            <form method="POST" action="{{ route('payrolls.store') }}" class="form2">
                                @csrf
                                <div class="row clearfix">
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="employee" name="employee" type="text" class="form-control"
                                                       value="{{ old('employee') }}" required>
                                                <label class="form-label">Employee</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="basic_salary" name="basic_salary" type="number" class="form-control"
                                                       value="{{ old('basic_salary') }}" required>
                                                <label class="form-label">Basic Salary</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="housing_allowance" name="housing_allowance" type="number" class="form-control"
                                                       value="{{ old('housing_allowance') }}" required>
                                                <label class="form-label">Housing Allowance</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="transport_allowance" name="transport_allowance" type="number" class="form-control"
                                                       value="{{ old('transport_allowance') }}" required>
                                                <label class="form-label">Transport Allowance</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="medical_allowance" name="medical_allowance" type="number" class="form-control"
                                                       value="{{ old('medical_allowance') }}" required>
                                                <label class="form-label">Medical Allowance</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="other_allowance" name="other_allowance" type="number" class="form-control"
                                                       value="{{ old('other_allowance') }}" required>
                                                <label class="form-label">Other Allowance</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="deduction_payee" name="deduction_payee" type="number" class="form-control"
                                                       value="{{ old('deduction_payee') }}" required>
                                                <label class="form-label">Deduction Payee (%)</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-3 col-md-3 col-sm-3 col-xs-6">
                                        <div class="form-group form-float">
                                            <div class="form-line">
                                                <input id="pension" name="pension" type="number" class="form-control"
                                                       value="{{ old('pension') }}" required>
                                                <label class="form-label">Pension (%)</label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            <label for="type">Branch</label>
                                            <select id="branch_id" class="form-control" name="branch_id" required>
                                                <option value="">Select Branch</option>
                                                @foreach($params['branches'] as $branch_id => $branchName)
                                                    <option value="{{ $branch_id }}">{{ $branchName }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-6">
                                        <div class="form-group form-float">
                                            <label for="gross_salary">Gross Salary</label>
                                            <div class="form-line">
                                                <input id="gross_salary" name="gross_salary" type="number" class="form-control"
                                                       value="{{ old('gross_salary') }}" required readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-6">
                                        <div class="form-group form-float">
                                            <label for="net_pay">Net Pay</label>
                                            <div class="form-line">
                                                <input id="net_pay" name="net_pay" type="number" class="form-control"
                                                       value="{{ old('net_pay') }}" required readonly>
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
    <link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet" />
@endpush

@push('include-js')
    <script>
        $(document).ready(function() {
            // Function to calculate gross_salary and net_pay
            $('#basic_salary, #housing_allowance, #transport_allowance, #medical_allowance, #other_allowance, #deduction_payee, #pension').on('input', function() {
                calculateTotals();
            });

            function calculateTotals() {
                var basic_salary = parseFloat($('#basic_salary').val()) || 0;
                var housing_allowance = parseFloat($('#housing_allowance').val()) || 0;
                var transport_allowance = parseFloat($('#transport_allowance').val()) || 0;
                var medical_allowance = parseFloat($('#medical_allowance').val()) || 0;
                var other_allowance = parseFloat($('#other_allowance').val()) || 0;

                var gross_salary = basic_salary +
                    housing_allowance +
                    transport_allowance +
                    medical_allowance +
                    other_allowance;
                $('#gross_salary').val(gross_salary);

                var deduction_payee_percentage = parseFloat($('#deduction_payee').val()) || 0;
                var pension_percentage = parseFloat($('#pension').val()) || 0;

                var deduction_payee = (deduction_payee_percentage / 100) * gross_salary;
                var pension = (pension_percentage / 100) * gross_salary;

                var net_pay = gross_salary - deduction_payee - pension;
                $('#net_pay').val(net_pay);
            }
        });
    </script>
@endpush
