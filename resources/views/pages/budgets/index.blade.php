@extends('layouts.app')

@section('title', 'Budget Management')

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
                <a class="btn btn-sm btn-info waves-effect" href="{{ route('budgets.index') }}">Back</a>
            </div>

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>Budget Management</h2>
                        </div>
                        <div class="body">
                            <form id="budgetForm" method="POST" action="{{ route('budgets.store') }}">
                                @csrf
                                <div class="row clearfix">
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            <label for="date">Select Date</label>
                                            <input id="date" name="date" type="date" class="form-control" required>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            <label for="branch">Branch</label>
                                            <select data-live-search="true" class="form-control show-tick" name="branch_id">
                                                <option value="0">Select Branch</option>
                                                @foreach ($branches as $branch)
                                                    <option @if ($branch->id == old('branch_id')) selected @endif
                                                    value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-lg-4 col-md-4 col-sm-6 col-xs-12">
                                        <div class="form-group">
                                            <label for="type">Select Type</label>
                                            <select id="type" class="form-control" name="type" required>
                                                <option value="">Select Type</option>
                                                <option value="profit_loss">Profit or Loss</option>
                                                <option value="financial_position">Financial Position</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                                <div id="profitLossTable" class="table-responsive" style="display: none;">
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th>Jan</th>
                                            <th>Feb</th>
                                            <th>Mar</th>
                                            <th>Apr</th>
                                            <th>May</th>
                                            <th>June</th>
                                            <th>July</th>
                                            <th>Aug</th>
                                            <th>Sept</th>
                                            <th>Oct</th>
                                            <th>Nov</th>
                                            <th>Dec</th>
                                            <th>Annual Amount</th>
                                            <th>del</th>
                                        </tr>
                                        </thead>
                                        <tbody id="profitLossRows">
{{--                                        <tr>--}}
{{--                                            <td>--}}
{{--                                                <select name="account[]" class="form-control">--}}
{{--                                                    <option value="" disabled selected>Select Account</option>--}}
{{--                                                    @foreach($accounts as $accountId => $accountName)--}}
{{--                                                        <option value="{{ $accountName }}">{{ $accountName }}</option>--}}
{{--                                                    @endforeach--}}
{{--                                                </select>--}}
{{--                                            </td>--}}
{{--                                        @for($i = 0; $i < 12; $i++)--}}
{{--                                                <td><input type="number" name="months[]" class="form-control"></td>--}}
{{--                                            @endfor--}}
{{--                                            <td><input type="number" name="annual_amount[]" class="form-control" readonly></td>--}}
{{--                                            <td><button type="button" class="btn btn-danger" onclick="deleteRow(this)"><i class="fa fa-trash"></i></button></td>--}}
{{--                                        </tr>--}}
                                        </tbody>
                                    </table>
                                    <button type="button" class="btn btn-primary" onclick="addRow()">Add Row</button>
                                    <button type="submit" class="btn btn-success">Save</button>
                                </div>
                                <div id="financialPositionTable" class="table-responsive" style="display: none;">
                                    <table class="table table-bordered">
                                        <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Account</th>
                                            <th>Jan</th>
                                            <th>Feb</th>
                                            <th>Mar</th>
                                            <th>Apr</th>
                                            <th>May</th>
                                            <th>June</th>
                                            <th>July</th>
                                            <th>Aug</th>
                                            <th>Sept</th>
                                            <th>Oct</th>
                                            <th>Nov</th>
                                            <th>Dec</th>
                                            <th>Annual Amount</th>
                                            {{--                                            <th>Actions</th>--}}
                                        </tr>
                                        </thead>
                                        <tbody id="financialPositionRows">
                                        {{-- Data will be appended here by JS --}}
                                        </tbody>
                                    </table>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@stop

@push('include-css')

{{--    <link href="{{ asset('asset/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}" rel="stylesheet">--}}

{{--    <!-- Colorpicker Css -->--}}
{{--    <link href="{{ asset('asset/plugins/bootstrap-colorpicker/css/bootstrap-colorpicker.css') }}" rel="stylesheet" />--}}

{{--    <!-- Dropzone Css -->--}}
{{--    <link href="{{ asset('asset/plugins/dropzone/dropzone.css') }}" rel="stylesheet">--}}

{{--    <!-- Multi Select Css -->--}}
{{--    <link href="{{ asset('asset/plugins/multi-select/css/multi-select.css') }}" rel="stylesheet">--}}

{{--    <!-- Bootstrap Spinner Css -->--}}
{{--    <link href="{{ asset('asset/plugins/jquery-spinner/css/bootstrap-spinner.css') }}" rel="stylesheet">--}}

{{--    <!-- Bootstrap Tagsinput Css -->--}}
{{--    <link href="{{ asset('asset/plugins/bootstrap-tagsinput/bootstrap-tagsinput.css') }}" rel="stylesheet">--}}

    <!-- Bootstrap Select Css -->
    <link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet" />


{{--    <!-- Bootstrap Material Datetime Picker Css -->--}}
{{--    <link href="{{ asset('asset/plugins/bootstrap-material-datetimepicker/css/bootstrap-material-datetimepicker.css') }}"--}}
{{--          rel="stylesheet" />--}}

{{--    <!-- Bootstrap DatePicker Css -->--}}
{{--    <link href="{{ asset('asset/plugins/bootstrap-datepicker/css/bootstrap-datepicker.css') }}" rel="stylesheet" />--}}


{{--    <!-- noUISlider Css -->--}}
{{--    <link href="{{ asset('asset/plugins/nouislider/nouislider.min.css') }}" rel="stylesheet" />--}}

{{--    <!-- Sweet Alert Css -->--}}
{{--    <link href="{{ asset('asset/plugins/sweetalert/sweetalert.css') }}" rel="stylesheet" />--}}

@endpush

@push('include-js')
    <script src="{{ asset('asset/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js') }}"></script>
    <!-- Add additional JS scripts here if necessary -->
    <script>
        $(document).ready(function() {
            $('#datepicker').datepicker({
                format: 'yyyy-mm-dd'
            });

            $('#type').change(function() {
                if ($(this).val() == 'profit_loss') {
                    $('#profitLossTable').show();
                    $('#financialPositionTable').hide();
                } else if ($(this).val() == 'financial_position') {
                    $('#profitLossTable').hide();
                    $('#financialPositionTable').show();
                    fetchFinancialPositionData();
                } else {
                    $('#profitLossTable').hide();
                    $('#financialPositionTable').hide();
                }
            });

            // Calculate the total annual amount on input change
            $(document).on('input', 'input[name="months[]"]', function() {
                calculateTotal();
            });
        });

        function addRow() {
            let row = `
                <tr>
                    <td>
                        <select name="account[]" class="form-control">
                            <option value="" disabled selected>Select Account</option>
                            @foreach($accounts as $accountId => $accountName)
                            <option value="{{ $accountName }}">{{ $accountName }}</option>
                            @endforeach
                        </select>
                    </td>
@for($i = 0; $i < 12; $i++)
            <td><input type="number" name="months[]" class="form-control"></td>
@endfor
            <td><input type="number" name="annual_amount[]" class="form-control" readonly></td>
            <td><button type="button" class="btn btn-danger" onclick="deleteRow(this)"><i class="fa fa-trash"></i></button></td>
        </tr>`;
            $('#profitLossRows').append(row);
        }

        function deleteRow(button) {
            $(button).closest('tr').remove();
            calculateTotal();
        }

        function calculateTotal() {
            $('#profitLossRows tr').each(function() {
                let total = 0;
                $(this).find('input[name="months[]"]').each(function() {
                    let value = parseFloat($(this).val()) || 0;
                    total += value;
                });
                $(this).find('input[name="annual_amount[]"]').val(total);
            });
        }
        function formatCurrency(value) {
            return '₦' + value.toLocaleString('en-NG', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function fetchFinancialPositionData() {
            $.ajax({
                url: '{{ route('budgets.index') }}',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    if (data && data.length > 0) {
                        let rows = '';
                        data.forEach(function(budget) {
                            let total = 0;
                            let months = [budget.january, budget.february, budget.march, budget.april, budget.may, budget.june, budget.july, budget.august, budget.september, budget.october, budget.november, budget.december];
                            months.forEach(function(month) {
                                total += parseFloat(month) || 0;
                            });

                            rows += `
                                <tr>
                                    <td>${budget.id}</td>
                                    <td>${budget.account}</td>
                                    <td>${formatCurrency(budget.january)}</td>
                                    <td>${formatCurrency(budget.february)}</td>
                                    <td>${formatCurrency(budget.march)}</td>
                                    <td>${formatCurrency(budget.april)}</td>
                                    <td>${formatCurrency(budget.may)}</td>
                                    <td>${formatCurrency(budget.june)}</td>
                                    <td>${formatCurrency(budget.july)}</td>
                                    <td>${formatCurrency(budget.august)}</td>
                                    <td>${formatCurrency(budget.september)}</td>
                                    <td>${formatCurrency(budget.october)}</td>
                                    <td>${formatCurrency(budget.november)}</td>
                                    <td>${formatCurrency(budget.december)}</td>
                                    <td>${formatCurrency(total)}</td>
                                </tr>`;
                        });
                        $('#financialPositionRows').html(rows);
                    }
                },
                error: function(xhr, status, error) {
                    console.error(error);
                }
            });
        }
    </script>
@endpush
