@extends('layouts.app')

@section('title', __('Purchase Order'))

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
                <a class="btn btn-sm btn-info waves-effect" href="{{ route('vendors.index') }}">{{ __('financial.labels.back') }}</a>
            </div>

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>{{ __('financial.terms.purchase_order') }}</h2>
                        </div>
                        <div class="body">
                            @if($errors->any())
                                <div class="alert alert-danger" role="alert">
                                    <ul class="mb-0">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            @endif
                            <form class="form" id="form_validation" method="post" action="{{ route('vouchers.store') }}">
                                @csrf
                                <div class="row clearfix">
                                    <x-form.form-select :label="__('Select Vendor')" name="vendor_id" :options="$vendor_id_options" required colspan="3" />
                                </div>
                                <div class="row clearfix">
                                    {{-- CUSTOMER INFO --}}
                                    <x-form.form-input :label="__('Vendor Name')" name="name" required colspan="3" />
                                    <x-form.form-input :label="__('Account')" type="text" name="account" required colspan="3" />
                                    <x-form.form-input :label="__('Phone Number')" type="tel" name="phone" required colspan="3" />
                                    <x-form.form-input :label="__('Home Address')" name="address" required colspan="3" />

                                    {{-- INVOICE INFO --}}
                                    <x-form.form-input :label="__('Branch')" name="branch_name" required colspan="3" />
                                    <x-form.form-input :label="__('Invoice No.')" type="number" name="invoice_number" required colspan="3" value="" readonly />
                                    <x-form.form-input label="" type="date" name="invoice_date" required colspan="3" value="" />
                                    <x-form.form-input :label="__('Exchange Rate')" name="exchange_rate" required colspan="3" />

                                    {{-- INVOICE ITEMS --}}
                                    <div class="col-12">
                                        <table class="table table-bordered" id="invoice_items_table">
                                            <thead>
                                            <tr>
                                                <th>{{ __('financial.labels.item') }}</th>
                                                <th>{{ __('financial.terms.description') }}</th>
                                                <th>{{ __('financial.terms.quantity') }}</th>
                                                <th>{{ __('financial.labels.rate') }}</th>
                                                <th>{{ __('financial.terms.total_amount') }}</th>
                                                <th>{{ __('Action') }}</th>
                                            </tr>
                                            </thead>
                                            <tbody>
                                            <!-- Initial row -->
                                            <tr>
                                                <td>
                                                    <select name="items[0][item_id]" class="form-control item-select" onchange="setItemDescriptionAndRate(this)">
                                                        <option value="">{{ __('Select Item') }}</option>
                                                        @foreach($inventory_items as $item)
                                                            <option value="{{ $item->id }}" data-description="{{ $item->description }}" data-rate="{{ $item->unit_cost }}">{{ $item->type_name }}</option>
                                                        @endforeach
                                                    </select>
                                                </td>
                                                <td><input type="text" name="items[0][description]" class="form-control description" readonly></td>
                                                <td><input type="number" name="items[0][quantity]" class="form-control" oninput="calculateAmount(this)"></td>
                                                <td><input type="number" name="items[0][rate]" class="form-control rate" readonly></td>
                                                <td><input type="number" name="items[0][amount]" class="form-control amount" readonly></td>
                                                <td><button type="button" class="btn btn-danger delete-row">{{ __('Delete') }}</button></td>
                                            </tr>
                                            </tbody>
                                            <tfoot>
                                            <tr>
                                                <td colspan="4" align="right"><strong>{{ __('financial.terms.total') }}:</strong></td>
                                                <td>{{ $currency_symbol }} <input type="number" id="total_amount" class="form-control" readonly></td>
                                                <td></td>
                                            </tr>
                                            @if($wht_enabled && $wht_mode === 'manual')
                                                <tr>
                                                    <td colspan="4" align="right"><strong>{{ __('Withholding Tax') }} ({{ number_format((float) $wht_manual_rate, 2) }}%):</strong></td>
                                                    <td>{{ $currency_symbol }} <input type="number" id="withholding_tax_amount" class="form-control" readonly></td>
                                                    <td></td>
                                                </tr>
                                            @endif
                                            <tr>
                                                <td colspan="4" align="right"><strong>{{ __('financial.labels.balance_due') }}:</strong></td>
                                                <td>{{ $currency_symbol }} <input type="number" id="balance_due" class="form-control" readonly></td>
                                                <td></td>
                                            </tr>
                                            </tfoot>
                                        </table>
                                        <button type="button" class="btn btn-primary" id="add_row">{{ __('financial.screens.add_row') }}</button>
                                    </div>

                                    {{-- ACTION --}}
                                    <x-form.form-button as="Create" />
                                </div>
                            </form>
                            @if($wht_enabled && $wht_mode === 'rule_based')
                                <div class="alert alert-warning">{{ __('financial.labels.rule_based_wht_not_configured') }}</div>
                            @endif
                            <style>
                                .hidden-label {
                                    display: none;
                                }
                            </style>

                            <script>
                                const whtEnabled = @json($wht_enabled);
                                const whtMode = @json($wht_mode);
                                const manualWhtRate = Number(@json($wht_manual_rate));

                                function setItemDescriptionAndRate(select) {
                                    const selectedOption = select.options[select.selectedIndex];
                                    const row = select.closest('tr');
                                    const descriptionField = row.querySelector('.description');
                                    const rateField = row.querySelector('.rate');

                                    if (selectedOption) {
                                        descriptionField.value = selectedOption.getAttribute('data-description');
                                        rateField.value = selectedOption.getAttribute('data-rate');
                                        calculateAmount(select);
                                    } else {
                                        descriptionField.value = '';
                                        rateField.value = '';
                                    }
                                }

                                function calculateAmount(element) {
                                    const row = element.closest('tr');
                                    const quantity = parseFloat(row.querySelector('input[name$="[quantity]"]').value) || 0;
                                    const rate = parseFloat(row.querySelector('input[name$="[rate]"]').value) || 0;
                                    const amountField = row.querySelector('input[name$="[amount]"]');
                                    const amount = quantity * rate;
                                    amountField.value = amount.toFixed(2);

                                    updateTotalAndBalance();
                                }

                                function updateTotalAndBalance() {
                                    let total = 0;
                                    document.querySelectorAll('input[name$="[amount]"]').forEach(input => {
                                        total += parseFloat(input.value) || 0;
                                    });
                                    const manualApplies = whtEnabled && whtMode === 'manual';
                                    const withholdingTaxAmount = manualApplies ? (total * manualWhtRate) / 100 : 0;
                                    const balanceDue = total - withholdingTaxAmount;

                                    document.getElementById('total_amount').value = total.toFixed(2);
                                    const withholdingField = document.getElementById('withholding_tax_amount');
                                    if (withholdingField) withholdingField.value = withholdingTaxAmount.toFixed(2);
                                    document.getElementById('balance_due').value = whtEnabled && whtMode === 'rule_based' ? '' : balanceDue.toFixed(2);
                                }

                                document.addEventListener('DOMContentLoaded', function () {
                                    const vendors = @json($vendors);

                                    document.querySelector('select[name="vendor_id"]').addEventListener('change', function () {
                                        const vendorId = this.value;
                                        const vendor = vendors.find(c => c.id == vendorId);

                                        if (vendor) {
                                            populateField('name', vendor.name);
                                            populateField('account', vendor.account);
                                            populateField('phone', vendor.phone_number);
                                            populateField('address', vendor.address);
                                            populateField('branch_name', vendor.branch_id ? vendor.branch.name : '');

                                            const latestInvoiceNumber = @json($next_invoice_number);
                                            const nextInvoiceNumber = latestInvoiceNumber ? String(parseInt(latestInvoiceNumber) + 1).padStart(4, '0') : '0001';
                                            populateField('invoice_number', nextInvoiceNumber);
                                            populateField('invoice_date', new Date().toISOString().split('T')[0]);
                                        }
                                    });

                                    function populateField(fieldName, value) {
                                        const field = document.querySelector(`input[name="${fieldName}"]`);
                                        const label = field.closest('.form-group').querySelector('label');
                                        field.value = value;
                                        if (value) {
                                            label.classList.add('hidden-label');
                                        } else {
                                            label.classList.remove('hidden-label');
                                        }
                                    }

                                    document.getElementById('add_row').addEventListener('click', function () {
                                        const table = document.getElementById('invoice_items_table').getElementsByTagName('tbody')[0];
                                        const rowCount = table.rows.length;
                                        const newRow = table.insertRow();
                                        newRow.innerHTML = `
                                            <td>
                                                <select name="items[${rowCount}][item_id]" class="form-control item-select" onchange="setItemDescriptionAndRate(this)">
                                                    <option value="">Select Item</option>
                                                    @foreach($inventory_items as $item)
                                        <option value="{{ $item->id }}" data-description="{{ $item->description }}" data-rate="{{ $item->unit_cost }}">{{ $item->type_name }}</option>
                                                    @endforeach
                                        </select>
                                    </td>
                                    <td><input type="text" name="items[${rowCount}][description]" class="form-control description" readonly></td>
                                            <td><input type="number" name="items[${rowCount}][quantity]" class="form-control" oninput="calculateAmount(this)"></td>
                                            <td><input type="number" name="items[${rowCount}][rate]" class="form-control rate" readonly></td>
                                            <td><input type="number" name="items[${rowCount}][amount]" class="form-control amount" readonly></td>
                                            <td><button type="button" class="btn btn-danger delete-row">Delete</button></td>
                                        `;
                                        attachDeleteHandler();
                                    });

                                    function attachDeleteHandler() {
                                        const deleteButtons = document.querySelectorAll('.delete-row');
                                        deleteButtons.forEach(button => {
                                            button.removeEventListener('click', handleDeleteRow);
                                            button.addEventListener('click', handleDeleteRow);
                                        });
                                    }

                                    function handleDeleteRow(event) {
                                        const row = event.target.closest('tr');
                                        row.remove();
                                        updateTotalAndBalance();
                                    }

                                    attachDeleteHandler();
                                });
                            </script>
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
