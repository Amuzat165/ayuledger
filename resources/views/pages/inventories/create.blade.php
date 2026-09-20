@extends('layouts.app')

@section('title', 'Create Inventory')

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
                <a class="btn btn-sm btn-info waves-effect" href="{{ route('inventories.index') }}">Back</a>
            </div>

            <ol class="breadcrumb breadcrumb-col-cyan pull-right">
                <li><a href="{{ route('dashboard') }}"><i class="material-icons">home</i> Home</a></li>
                <li><a href="{{ route('inventories.index') }}"><i class="fas fa-archive"></i> Inventories</a></li>
                <li class="active"><i class="material-icons">archive</i> Create Inventory</li>
            </ol>

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>Create Inventory</h2>
                            <small>Enter Inventory Information</small>
                        </div>
                        <div class="body">
                            <form method="POST" action="{{ route('inventories.store') }}" class="form2">
                                @csrf
                                <table class="table table-bordered">
                                    <tbody>
                                    <tr>
                                        <td><label for="branch_id">Branch</label></td>
                                        <td>
                                            <select data-live-search="true" class="form-control show-tick" name="branch_id">
                                                <option value="0">Select Branch Name</option>
                                                @foreach ($branches as $branch)
                                                    <option @if ($branch->id == old('branch_id')) selected @endif
                                                    value="{{ $branch->id }}">{{ $branch->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="type_id">Inventory Type</label></td>
                                        <td>
                                            <select id="type_id" name="type_id" class="form-control" onchange="toggleNewType()">
                                                <option value="" disabled selected>Select Type</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}">{{ $type->name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="new_type">Add New Inventory</label></td>
                                        <td>
                                            <input id="new_type" name="new_type" type="text" class="form-control" placeholder="New Type" oninput="toggleTypeId()">
                                            @if ($errors->has('new_type'))
                                                <span id="new_type_error" class="text-danger">{{ $errors->first('new_type') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="description">Description</label></td>
                                        <td>
                                            <textarea id="description" name="description" class="form-control"></textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="sub_item">Sub Item</label></td>
                                        <td>
                                            <input id="sub_item" name="sub_item" type="text" class="form-control">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="unit_of_measurement">Unit of Measurement</label></td>
                                        <td>
                                            <select id="unit_of_measurement" name="unit_of_measurement" class="form-control">
                                                <option value="" disabled selected>Select Unit</option>
                                                <option value="Millimeter (mm)">Millimeter (mm)</option>
                                                <option value="Centimeter (cm)">Centimeter (cm)</option>
                                                <option value="Meter (m)">Meter (m)</option>
                                                <option value="Kilometer (km)">Kilometer (km)</option>
                                                <option value="Inch (in)">Inch (in)</option>
                                                <option value="Foot (ft)">Foot (ft)</option>
                                                <option value="Yard (yd)">Yard (yd)</option>
                                                <option value="Mile (mi)">Mile (mi)</option>
                                                <option value="Milligram (mg)">Milligram (mg)</option>
                                                <option value="Gram (g)">Gram (g)</option>
                                                <option value="Kilogram (kg)">Kilogram (kg)</option>
                                                <option value="Metric Ton (t)">Metric Ton (t)</option>
                                                <option value="Ounce (oz)">Ounce (oz)</option>
                                                <option value="Pound (lb)">Pound (lb)</option>
                                                <option value="Stone (st)">Stone (st)</option>
                                                <option value="Milliliter (ml)">Milliliter (ml)</option>
                                                <option value="Centiliter (cl)">Centiliter (cl)</option>
                                                <option value="Liter (l)">Liter (l)</option>
                                                <option value="Cubic Meter (m³)">Cubic Meter (m³)</option>
                                                <option value="Fluid Ounce (fl oz)">Fluid Ounce (fl oz)</option>
                                                <option value="Pint (pt)">Pint (pt)</option>
                                                <option value="Quart (qt)">Quart (qt)</option>
                                                <option value="Gallon (gal)">Gallon (gal)</option>
                                                <option value="Crates">Crates</option>
                                                <option value="Bags">Bags</option>
                                                <option value="Carton">Carton</option>
                                                <option value="Pieces">Pieces</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="unit_cost">Unit Cost</label></td>
                                        <td>
                                            <input id="unit_cost" name="unit_cost" type="number" class="form-control" step="0.01" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="sale_price">Sale Price</label></td>
                                        <td>
                                            <input id="sale_price" name="sale_price" type="number" class="form-control" step="0.01" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="quantity_at_hand">Quantity at Hand</label></td>
                                        <td>
                                            <input id="quantity_at_hand" name="quantity_at_hand" type="number" class="form-control" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="reorder_level">Reorder Level</label></td>
                                        <td><input id="reorder_level" name="reorder_level" type="number" min="0" value="{{ old('reorder_level', 0) }}" class="form-control" required></td>
                                    </tr>
                                    <tr>
                                        <td><label for="account">Account</label></td>
                                        <td>
                                            <select id="account" name="account" class="form-control" required>
                                                <option value="" disabled selected>Select Account</option>
                                                @foreach($accounts as $id => $name)
                                                    <option value="{{ $name }}">{{ $name }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                                <button type="submit" class="btn btn-primary m-t-15 waves-effect">Save</button>
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
