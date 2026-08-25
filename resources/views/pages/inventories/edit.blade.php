@extends('layouts.app')

@section('title', 'Edit Inventory')

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
                <li class="active"><i class="material-icons">archive</i> Edit Inventory</li>
            </ol>

            <div class="row clearfix">
                <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                    <div class="card">
                        <div class="header">
                            <h2>Edit Inventory</h2>
                            <small>Update Inventory Information</small>
                        </div>
                        <div class="body">
                            <form method="POST" action="{{ route('inventories.update', $inventory->id) }}" class="form2">
                                @csrf
                                @method('PUT')

                                <table class="table table-bordered">
                                    <tbody>
                                    <tr>
                                        <td><label for="branch_id">Branch</label></td>
                                        <td>
                                            <select id="branch_id" name="branch_id" class="form-control" required>
                                                <option value="" disabled>Select Branch</option>
                                                @foreach($branches as $branch)
                                                    <option value="{{ $branch->id }}" {{ $branch->id == $inventory->branch_id ? 'selected' : '' }}>
                                                        {{ $branch->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="type_id">Inventory Type</label></td>
                                        <td>
                                            <select id="type_id" name="type_id" class="form-control" onchange="toggleNewType()">
                                                <option value="" disabled>Select Type</option>
                                                @foreach($types as $type)
                                                    <option value="{{ $type->id }}" {{ $type->id == $inventory->type_id ? 'selected' : '' }}>
                                                        {{ $type->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="new_type">Add New Inventory</label></td>
                                        <td>
                                            <input id="new_type" name="new_type" type="text" class="form-control" placeholder="New Type" value="{{ old('new_type') }}">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="description">Description</label></td>
                                        <td>
                                            <textarea id="description" name="description" class="form-control">{{ $inventory->description }}</textarea>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="sub_item">Sub Item</label></td>
                                        <td>
                                            <input id="sub_item" name="sub_item" type="text" class="form-control" value="{{ $inventory->sub_item }}">
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="unit_of_measurement">Unit of Measurement</label></td>
                                        <td>
                                            <select id="unit_of_measurement" name="unit_of_measurement" class="form-control">
                                                <option value="" disabled>Select Unit</option>
                                                <option value="Millimeter (mm)" {{ $inventory->unit_of_measurement == 'Millimeter (mm)' ? 'selected' : '' }}>Millimeter (mm)</option>
                                                <option value="Centimeter (cm)" {{ $inventory->unit_of_measurement == 'Centimeter (cm)' ? 'selected' : '' }}>Centimeter (cm)</option>
                                                <option value="Meter (m)" {{ $inventory->unit_of_measurement == 'Meter (m)' ? 'selected' : '' }}>Meter (m)</option>
                                                <option value="Kilometer (km)" {{ $inventory->unit_of_measurement == 'Kilometer (km)' ? 'selected' : '' }}>Kilometer (km)</option>
                                                <option value="Inch (in)" {{ $inventory->unit_of_measurement == 'Inch (in)' ? 'selected' : '' }}>Inch (in)</option>
                                                <option value="Foot (ft)" {{ $inventory->unit_of_measurement == 'Foot (ft)' ? 'selected' : '' }}>Foot (ft)</option>
                                                <option value="Yard (yd)" {{ $inventory->unit_of_measurement == 'Yard (yd)' ? 'selected' : '' }}>Yard (yd)</option>
                                                <option value="Mile (mi)" {{ $inventory->unit_of_measurement == 'Mile (mi)' ? 'selected' : '' }}>Mile (mi)</option>
                                                <option value="Milligram (mg)" {{ $inventory->unit_of_measurement == 'Milligram (mg)' ? 'selected' : '' }}>Milligram (mg)</option>
                                                <option value="Gram (g)" {{ $inventory->unit_of_measurement == 'Gram (g)' ? 'selected' : '' }}>Gram (g)</option>
                                                <option value="Kilogram (kg)" {{ $inventory->unit_of_measurement == 'Kilogram (kg)' ? 'selected' : '' }}>Kilogram (kg)</option>
                                                <option value="Metric Ton (t)" {{ $inventory->unit_of_measurement == 'Metric Ton (t)' ? 'selected' : '' }}>Metric Ton (t)</option>
                                                <option value="Ounce (oz)" {{ $inventory->unit_of_measurement == 'Ounce (oz)' ? 'selected' : '' }}>Ounce (oz)</option>
                                                <option value="Pound (lb)" {{ $inventory->unit_of_measurement == 'Pound (lb)' ? 'selected' : '' }}>Pound (lb)</option>
                                                <option value="Stone (st)" {{ $inventory->unit_of_measurement == 'Stone (st)' ? 'selected' : '' }}>Stone (st)</option>
                                                <option value="Milliliter (ml)" {{ $inventory->unit_of_measurement == 'Milliliter (ml)' ? 'selected' : '' }}>Milliliter (ml)</option>
                                                <option value="Centiliter (cl)" {{ $inventory->unit_of_measurement == 'Centiliter (cl)' ? 'selected' : '' }}>Centiliter (cl)</option>
                                                <option value="Liter (l)" {{ $inventory->unit_of_measurement == 'Liter (l)' ? 'selected' : '' }}>Liter (l)</option>
                                                <option value="Cubic Meter (m³)" {{ $inventory->unit_of_measurement == 'Cubic Meter (m³)' ? 'selected' : '' }}>Cubic Meter (m³)</option>
                                                <option value="Fluid Ounce (fl oz)" {{ $inventory->unit_of_measurement == 'Fluid Ounce (fl oz)' ? 'selected' : '' }}>Fluid Ounce (fl oz)</option>
                                                <option value="Pint (pt)" {{ $inventory->unit_of_measurement == 'Pint (pt)' ? 'selected' : '' }}>Pint (pt)</option>
                                                <option value="Quart (qt)" {{ $inventory->unit_of_measurement == 'Quart (qt)' ? 'selected' : '' }}>Quart (qt)</option>
                                                <option value="Gallon (gal)" {{ $inventory->unit_of_measurement == 'Gallon (gal)' ? 'selected' : '' }}>Gallon (gal)</option>
                                                <option value="Crates" {{ $inventory->unit_of_measurement == 'Crates' ? 'selected' : '' }}>Crates</option>
                                                <option value="Pieces" {{ $inventory->unit_of_measurement == 'Pieces' ? 'selected' : '' }}>Pieces</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="unit_cost">Unit Cost</label></td>
                                        <td>
                                            <input id="unit_cost" name="unit_cost" type="number" class="form-control" step="0.01" value="{{ $inventory->unit_cost }}" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="sale_price">Sale Price</label></td>
                                        <td>
                                            <input id="sale_price" name="sale_price" type="number" class="form-control" step="0.01" value="{{ $inventory->sale_price }}" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="quantity_at_hand">Quantity at Hand</label></td>
                                        <td>
                                            <input id="quantity_at_hand" name="quantity_at_hand" type="number" class="form-control" value="{{ $inventory->quantity_at_hand }}" required>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><label for="account">Account</label></td>
                                        <td>
                                            <select id="account" name="account" class="form-control" required>
                                                <option value="" disabled>Select Account</option>
                                                @foreach($accounts as $id => $name)
                                                    <option value="{{ $name }}" {{ $name == $inventory->account ? 'selected' : '' }}>
                                                        {{ $name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>

                                <button type="submit" class="btn btn-primary m-t-15 waves-effect">Update</button>
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
