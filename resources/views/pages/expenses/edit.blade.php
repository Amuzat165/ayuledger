@extends('layouts.app')

@section('title', __('financial.screens.edit_expense'))

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
                <a class="btn btn-sm btn-info waves-effect" href="{{ route('expenses.index') }}">{{ __('financial.screens.back') }}</a>
            </div>

            <ol class="breadcrumb breadcrumb-col-cyan pull-right">
                <li><a href="{{ route('dashboard') }}"><i class="material-icons">home</i> {{ __('root.common.home') }}</a></li>
                <li><a href="{{ route('expenses.index') }}"><i class="fas fa-receipt"></i> {{ __('financial.screens.expenses') }}</a></li>
                <li class="active"><i class="material-icons">edit</i> {{ __('financial.screens.edit_expense') }}</li>
            </ol>

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>{{ __('financial.screens.edit_expense') }}</h2>
                            <small>{{ __('financial.screens.expense_information') }}</small>
                        </div>
                        <div class="body">
                            <form method="POST" action="{{ route('expenses.update', $expense->id) }}" class="form2">
                                @csrf
                                @method('PUT')
                                <table class="table table-bordered">
                                    <tbody>
                                    <tr>
                                        <td><label for="branch">{{ __('financial.labels.branch') }}</label></td>
                                        <td>
                                            <select id="branch_id" name="branch_id" class="form-control" required>
                                                <option value="" disabled>{{ __('financial.labels.select_branch') }}</option>
                                                @foreach($branches as $branch)
                                                    <option value="{{ $branch->id }}" {{ $branch->id == $expense->branch_id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="account">{{ __('financial.terms.account') }}</label></td>
                                        <td>
                                            <select id="account" name="account" class="form-control" required>
                                                <option value="" disabled>{{ __('financial.screens.select_account') }}</option>
                                                @foreach($accounts as $id => $name)
                                                    <option value="{{ $name }}" {{ $expense->account === $name ? 'selected' : '' }}>{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="payment_mode">{{ __('financial.screens.payment_mode') }}</label></td>
                                        <td>
                                            <select id="payment_mode" name="bank_cash_id" class="form-control" required>
                                                <option value="" disabled selected>{{ __('financial.labels.select_payment_mode') }}</option>
                                                @foreach($bankCashes as $bank)
                                                    <option value="{{ $bank->id }}" {{ $bank->id == $expense->bank_cash_id ? 'selected' : '' }}>
                                                    {{ $bank->name }}
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="vendor">{{ __('financial.terms.vendor') }}</label></td>
                                        <td>
                                            <select id="vendor" name="vendor" class="form-control" required>
                                                <option value="" disabled>{{ __('financial.labels.select_vendor') }}</option>
                                                @foreach($vendors as $vendor)
                                                    <option value="{{ $vendor->name }}" {{ $expense->vendor === $vendor->name ? 'selected' : '' }}>{{ $vendor->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="expense_date">{{ __('financial.screens.expense_date') }}</label></td>
                                        <td>
                                            <input id="expense_date" name="expense_date" type="date" class="form-control" value="{{ $expense->expense_date }}" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="category">{{ __('financial.screens.category') }}</label></td>
                                        <td>
                                            <select id="type_id" name="type_id" class="form-control" onchange="toggleNewCategory()">
                                                <option value="" disabled>{{ __('financial.screens.category') }}</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}" {{ $expense->type_id === $type->id ? 'selected' : '' }}>{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="new_category">{{ __('financial.screens.add_new_category') }}</label></td>
                                        <td>
                                            <input id="new_type" name="new_type" type="text" class="form-control" placeholder="{{ __('financial.screens.new_category') }}" oninput="toggleCategory()">
                                            @if ($errors->has('new_type'))
                                                <span id="new_category_error" class="text-danger">{{ $errors->first('new_type') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="amount">{{ __('financial.labels.amount_to_pay') }}</label></td>
                                        <td>
                                            <input id="amount" name="amount" type="number" class="form-control" step="0.01" value="{{ $expense->amount }}" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="description">{{ __('financial.terms.description') }}</label></td>
                                        <td>
                                            <textarea id="description" name="description" class="form-control">{{ $expense->description }}</textarea>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                                <button type="submit" class="btn btn-primary m-t-15 waves-effect">{{ __('financial.labels.update') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function toggleNewCategory() {
            const category = document.getElementById('type_id').value;
            document.getElementById('new_type').disabled = category !== '';
        }

        function toggleCategory() {
            const newCategory = document.getElementById('new_type').value;
            document.getElementById('type_id').disabled = newCategory !== '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleNewCategory();
            toggleCategory();
        });
    </script>
@stop

@push('include-css')
    <link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet" />
@endpush

@push('include-js')
    <!-- Include JS files here -->
@endpush
