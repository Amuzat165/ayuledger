@extends('layouts.app')
@section('title', 'Payroll Report')
@section('top-bar') @include('shared.header') @stop
@section('left-sidebar') @include('shared.sidebar') @stop
@section('content')
<section class="content payroll-report"><div class="container-fluid"><div class="card"><div class="header"><h2>Payroll Report</h2></div><div class="body">
<form method="post" action="{{ route('reports.accounts.payroll.report') }}">@csrf
<div class="row clearfix"><div class="col-md-4"><label for="payroll-branch">Branch</label><select id="payroll-branch" class="form-control branch-report-select" name="branch_id"><option value="">All Branches</option>@forelse($branches as $branchId => $branchLabel)<option value="{{ $branchId }}" @selected((string) old('branch_id') === (string) $branchId)>{{ $branchLabel }}</option>@empty<option value="" disabled>No branches available</option>@endforelse</select></div><div class="col-md-3"><label>From</label><input class="form-control" type="date" name="from" value="{{ old('from') }}"></div><div class="col-md-3"><label>To</label><input class="form-control" type="date" name="to" value="{{ old('to') }}"></div><div class="col-md-2"><button class="btn btn-primary m-t-15" name="action" value="Show">Show</button><button class="btn btn-default m-t-15" name="action" value="Pdf">PDF</button></div></div>
</form></div></div></div></section>
@stop

@push('include-css')
<link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet">
<style>.payroll-report .branch-report-select { background: #fff; border: 1px solid #cbd8de; color: #183b56; cursor: pointer; opacity: 1; }</style>
@endpush
