@extends('layouts.app')

@section('title')
@yield('subtitle')
@endsection

@section('top-bar')
@include('shared.header')
@endsection

@section('left-sidebar')
@include('shared.sidebar')
@endsection

@section('content')
<section @if ($is_rtl) dir="rtl" @endif class="content">
  <div class="container-fluid">
    @yield('container')
  </div>
</section>
@endsection

@push('include-css')
<!-- Bootstrap Select Css -->
<link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet" />
@endpush

@push('include-js')
<script>
  @if (Session::has('success'))
      toastr["success"]('{{ __(Session::get('success')) }}');
  @endif
  @if (Session::has('error'))
      toastr["error"]('{{ __(Session::get('error')) }}');
  @endif
  @if ($errors->any())
      @foreach ($errors->all() as $error)
          toastr["error"]('{{ $error }}');
      @endforeach
  @endif
</script>
{{-- All datagrid --}}
<script src="{{ asset('asset/js/dataTable.js') }}"></script>
<script>
  BaseController.init();
</script>
@endpush