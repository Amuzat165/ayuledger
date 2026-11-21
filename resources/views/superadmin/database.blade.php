@extends('layouts.app')

@section('title', 'Database Browser')

@section('top-bar')
    @include('shared.header')
@stop

@section('left-sidebar')
    @include('shared.sidebar')
@stop

@section('content')
<section class="content">
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h2>Database Browser</h2>
            <p class="text-muted mb-0">Read-only platform data view across all tenants.</p>
        </div>
        <a href="{{ route('superadmin.dashboard') }}" class="btn btn-outline-secondary">Super Admin Console</a>
    </div>

    <form method="GET" class="form-inline mb-3">
        <label class="mr-2 font-weight-bold" for="table">Table</label>
        <select id="table" name="table" class="form-control mr-2" onchange="this.form.submit()">
            @foreach($tables as $availableTable)
                <option value="{{ $availableTable }}" {{ $availableTable === $table ? 'selected' : '' }}>{{ $availableTable }}</option>
            @endforeach
        </select>
        @if(in_array('business_id', $columns, true))
            <label class="mr-2 font-weight-bold" for="business_id">Business</label>
            <select id="business_id" name="business_id" class="form-control mr-2" onchange="this.form.submit()">
                <option value="">All businesses</option>
                @foreach($businesses as $business)
                    <option value="{{ $business->id }}" {{ (string) $business->id === (string) $businessId ? 'selected' : '' }}>
                        {{ $business->business_name ?: 'Business '.$business->id }} ({{ $business->id }})
                    </option>
                @endforeach
            </select>
        @endif
    </form>

    <div class="alert alert-warning">
        Showing up to 500 rows. Password and security-token fields are masked.
    </div>

    <div class="table-responsive" style="max-height:70vh">
        <table class="table table-bordered table-striped table-sm">
            <thead class="thead-light">
                <tr>@foreach($columns as $column)<th style="white-space:nowrap">{{ $column }}</th>@endforeach</tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr>
                        @foreach($columns as $column)
                            @php
                                $value = $row->{$column};
                                if (in_array($column, ['password', 'remember_token', 'secret', 'api_secret'], true) && filled($value)) {
                                    $value = '********';
                                } elseif (is_bool($value)) {
                                    $value = $value ? 'true' : 'false';
                                } elseif (is_array($value) || is_object($value)) {
                                    $value = json_encode($value);
                                }
                            @endphp
                            <td style="white-space:nowrap;max-width:320px;overflow:hidden;text-overflow:ellipsis">{{ $value === null ? 'NULL' : $value }}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr><td colspan="{{ max(count($columns), 1) }}" class="text-center text-muted p-4">No rows found.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="mt-2 text-muted">{{ $rows->count() }} rows displayed from <strong>{{ $table }}</strong>.</div>
</div>
</section>
@endsection
