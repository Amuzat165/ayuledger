<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Support\LocaleManager::direction(app()->getLocale()) }}">

<head>
    <meta charset="UTF-8">
    <!-- Seo meta tag -->
    <meta name="author" content="mamun2074">
    <meta name="keywords"
        content="account, Accounting Software, balance sheet, cash flow, Cost Of Revenue, fixed asset schedule, ledger, multi branch, Profit Or Loss Account, receive payment, trial balance">
    <meta name="description"
        content="E-Account is a dynamic, open source, easy to use, user friendly web base application. Which is built in PHP – MySQL">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- End -->

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Welcome To | ' . Config::get('settings.company_name'))</title>
    @include('shared.favicon')
    <link rel="stylesheet" type="text/css"
        href="{{ asset('asset/layout/fonts/font-awesome-4.7.0/css/font-awesome.min.css') }}">
    <link rel="stylesheet" type="text/css"
        href="{{ asset('asset/layout/fonts/iconic/css/material-design-iconic-font.min.css') }}">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('asset/css/e-bootstrap.min.css') }}">
    {{-- Utility Css --}}
    <link rel="stylesheet" type="text/css" href="{{ asset('asset/layout/css/util.css') }}">
    <link rel="stylesheet" type="text/css" href="{{ asset('asset/layout/css/main.css') }}">
@if (\App\Support\LocaleManager::direction(app()->getLocale()) === 'rtl')
    <link rel="stylesheet" href="{{ asset('asset/css/style-rtl.css') }}">
    @endif

    <!-- Dynamic Company Color Override -->
    <style>
        <?php
            $companyColor = Config::get('settings.company_color', '#00897B');
        ?>
        .bg-cyan, .bg-teal {
            background-color: {{ $companyColor }} !important;
            color: #fff !important;
        }
    </style>
</head>

<body>
    <div>
        @yield('auth-body')
    </div>
    <!-- Jquery Core Js -->
    <script src="{{ asset('asset/layout/vendor/jquery/jquery-3.2.1.min.js') }}"></script>
    <script src="{{ asset('asset/layout/js/main.js') }}"></script>
</body>

</html>
