@extends('layouts.app')
@section('title', 'Budget Report')
@section('top-bar') @include('shared.header') @stop
@section('left-sidebar') @include('shared.sidebar') @stop
@section('content')
<section class="content"><div class="container-fluid"><div class="card"><div class="header"><h2>Budget Report</h2></div><div class="body">
<form method="post" action="{{ route('reports.accounts.budget.report') }}">@csrf
<div class="row clearfix"><div class="col-md-4"><label>Branch</label><select class="form-control" name="branch_id"><option value="">All Branches</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></div><div class="col-md-3"><label>From</label><input class="form-control" type="date" name="from"></div><div class="col-md-3"><label>To</label><input class="form-control" type="date" name="to"></div><div class="col-md-2"><button class="btn btn-primary m-t-15" name="action" value="Show">Show</button><button class="btn btn-default m-t-15" name="action" value="Pdf">PDF</button></div></div>
</form></div></div></div></section>
@stop
