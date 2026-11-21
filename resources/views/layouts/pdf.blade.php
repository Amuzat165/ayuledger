<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\LocaleManager::direction(app()->getLocale()) }}">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <!-- Seo meta tag -->
    <meta name="author" content="mamun2074">
    <meta name="keywords"
        content="account, Accounting Software, balance sheet, cash flow, Cost Of Revenue, fixed asset schedule, ledger, multi branch, Profit Or Loss Account, receive payment, trial balance">
    <meta name="description"
        content="E-Account is a dynamic, open source, easy to use, user friendly web base application. Which is built in PHP – MySQL">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Company favicon-->
    @include('shared.favicon')
    <!-- End -->
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <!-- Bootstrap Core Css -->
    <link href="{{ asset('asset/plugins/bootstrap/v400/bootstrap.min.css') }}" rel="stylesheet">
    {{-- Report style --}}
    <link rel="stylesheet" href="{{ asset('asset/css/report.css') }}">
    <title>@yield('title', 'Report')</title>
    @stack('include-css')
    <link rel="stylesheet" type="text/css" href="{{ asset('asset/layout/css/util.css') }}">
    
    <!-- Dynamic Company Color Override for PDF -->
    <style>
        <?php 
            $companyColor = Config::get('settings.company_color', '#00897B');
        ?>
        .bg-cyan, .bg-teal, .header.bg-cyan, .card .header.bg-cyan {
            background-color: {{ $companyColor }} !important;
            color: #fff !important;
        }
    </style>
</head>

<body>
    @yield('content')
    <!-- Optional JavaScript -->
    <!-- Jquery Core Js -->
    <script src="{{ asset('asset/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap Core Js -->
    <script src="{{ asset('asset/plugins/bootstrap/js/bootstrap.js') }}"></script>
</body>

</html>
