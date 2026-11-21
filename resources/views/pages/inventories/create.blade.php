@extends('layouts.app')

@section('title', __('financial.screens.create_inventory'))

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
                <a class="btn btn-sm btn-info waves-effect" href="{{ route('inventories.index') }}">{{ __('financial.labels.back') }}</a>
            </div>

            <ol class="breadcrumb breadcrumb-col-cyan pull-right">
                <li><a href="{{ route('dashboard') }}"><i class="material-icons">home</i> {{ __('root.common.home') }}</a></li>
                <li><a href="{{ route('inventories.index') }}"><i class="fas fa-archive"></i> {{ __('financial.terms.inventory') }}</a></li>
                <li class="active"><i class="material-icons">archive</i> {{ __('financial.screens.create_inventory') }}</li>
            </ol>

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>{{ __('financial.screens.create_inventory') }}</h2>
                            <small>{{ __('financial.screens.inventory_information') }}</small>
                        </div>
                        <div class="body">
                            <form method="POST" action="{{ route('inventories.store') }}" class="form2">
                                @csrf
                                <table class="table table-bordered">
                                    <tbody>
                                    <tr>
                                        <td><label for="branch_id">{{ __('financial.labels.branch') }}</label></td>
                                        <td>
                                            <select data-live-search="true" class="form-control show-tick" name="branch_id">
                                                <option value="0">{{ __('financial.labels.select_branch') }}</option>
                                                @foreach ($branches as $branch)
                                                    <option @if ($branch->id == old('branch_id')) selected @endif
                                                    value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="type_id">{{ __('financial.screens.inventory_type') }}</label></td>
                                        <td>
                                            <select id="type_id" name="type_id" class="form-control" onchange="toggleNewType()">
                                                <option value="" disabled selected>{{ __('financial.screens.select_type') }}</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="new_type">{{ __('financial.screens.new_type') }}</label></td>
                                        <td>
                                            <input id="new_type" name="new_type" type="text" class="form-control" placeholder="{{ __('financial.screens.new_type') }}" oninput="toggleTypeId()">
                                            @if ($errors->has('new_type'))
                                                <span id="new_type_error" class="text-danger">{{ $errors->first('new_type') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="track_stock">{{ __('financial.screens.track_stock') }}</label></td>
                                        <td>
                                            <select id="track_stock" name="track_stock" class="form-control">
                                                <option value="" {{ old('track_stock') === null || old('track_stock') === '' ? 'selected' : '' }}>{{ __('financial.screens.track_stock_unclassified') }}</option>
                                                <option value="1" {{ old('track_stock') === '1' ? 'selected' : '' }}>{{ __('financial.screens.track_stock_yes') }}</option>
                                                <option value="0" {{ old('track_stock') === '0' ? 'selected' : '' }}>{{ __('financial.screens.track_stock_no') }}</option>
                                            </select>
                                        </td>
                                    </tr>                                    <tr>
                                        <td><label for="description">{{ __('financial.terms.description') }}</label></td>
                                        <td>
                                            <textarea id="description" name="description" class="form-control"></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="sub_item">{{ __('financial.screens.sub_item') }}</label></td>
                                        <td>
                                            <input id="sub_item" name="sub_item" type="text" class="form-control">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="unit_of_measurement">{{ __('financial.screens.unit_of_measurement') }}</label></td>
                                        <td>
                                            <select id="unit_of_measurement" name="unit_of_measurement" class="form-control">
                                                <option value="" disabled selected>{{ __('financial.screens.unit_of_measurement') }}</option>
                                                <option value="Millimeter (mm)">{{ __('Millimeter (mm)') }}</option>
                                                <option value="Centimeter (cm)">{{ __('Centimeter (cm)') }}</option>
                                                <option value="Meter (m)">{{ __('Meter (m)') }}</option>
                                                <option value="Kilometer (km)">{{ __('Kilometer (km)') }}</option>
                                                <option value="Inch (in)">{{ __('Inch (in)') }}</option>
                                                <option value="Foot (ft)">{{ __('Foot (ft)') }}</option>
                                                <option value="Yard (yd)">{{ __('Yard (yd)') }}</option>
                                                <option value="Mile (mi)">{{ __('Mile (mi)') }}</option>
                                                <option value="Milligram (mg)">{{ __('Milligram (mg)') }}</option>
                                                <option value="Gram (g)">{{ __('Gram (g)') }}</option>
                                                <option value="Kilogram (kg)">{{ __('Kilogram (kg)') }}</option>
                                                <option value="Metric Ton (t)">{{ __('Metric Ton (t)') }}</option>
                                                <option value="Ounce (oz)">{{ __('Ounce (oz)') }}</option>
                                                <option value="Pound (lb)">{{ __('Pound (lb)') }}</option>
                                                <option value="Stone (st)">{{ __('Stone (st)') }}</option>
                                                <option value="Milliliter (ml)">{{ __('Milliliter (ml)') }}</option>
                                                <option value="Centiliter (cl)">{{ __('Centiliter (cl)') }}</option>
                                                <option value="Liter (l)">{{ __('Liter (l)') }}</option>
                                                <option value="Cubic Meter (m³)">{{ __('Cubic Meter (m³)') }}</option>
                                                <option value="Fluid Ounce (fl oz)">{{ __('Fluid Ounce (fl oz)') }}</option>
                                                <option value="Pint (pt)">{{ __('Pint (pt)') }}</option>
                                                <option value="Quart (qt)">{{ __('Quart (qt)') }}</option>
                                                <option value="Gallon (gal)">{{ __('Gallon (gal)') }}</option>
                                                <option value="Crates">{{ __('Crates') }}</option>
                                                <option value="Bags">{{ __('Bags') }}</option>
                                                <option value="Carton">{{ __('Carton') }}</option>
                                                <option value="Pieces">{{ __('Pieces') }}</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="unit_cost">{{ __('financial.screens.unit_cost') }}</label></td>
                                        <td>
                                            <input id="unit_cost" name="unit_cost" type="number" class="form-control" step="0.01" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="sale_price">{{ __('financial.screens.sale_price') }}</label></td>
                                        <td>
                                            <input id="sale_price" name="sale_price" type="number" class="form-control" step="0.01" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="quantity_at_hand">{{ __('financial.screens.quantity_at_hand') }}</label></td>
                                        <td>
                                            <input id="quantity_at_hand" name="quantity_at_hand" type="number" class="form-control" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="reorder_level">{{ __('financial.screens.reorder_level') }}</label></td>
                                        <td><input id="reorder_level" name="reorder_level" type="number" min="0" value="{{ old('reorder_level', 0) }}" class="form-control" required></td>
                                    </tr>
                                    <tr>
                                        <td><label for="account">{{ __('financial.terms.account') }}</label></td>
                                        <td>
                                            <select id="account" name="account" class="form-control" required>
                                                <option value="" disabled selected>{{ __('financial.screens.select_account') }}</option>
                                                @foreach($accounts as $id => $name)
                                                    <option value="{{ $name }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                                <button type="submit" class="btn btn-primary m-t-15 waves-effect">{{ __('financial.labels.save') }}</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script>
        function toggleNewType() {
            const typeId = document.getElementById('type_id').value;
            document.getElementById('new_type').disabled = typeId !== '';
        }

        function toggleTypeId() {
            const newType = document.getElementById('new_type').value;
            document.getElementById('type_id').disabled = newType !== '';
        }

        document.addEventListener('DOMContentLoaded', function() {
            toggleNewType();
            toggleTypeId();
        });
    </script>
@stop

@push('include-css')
    <link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet" />
@endpush

@push('include-js')
    <!-- Include JS files here -->
@endpush
