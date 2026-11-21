<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ ($is_rtl || app()->getLocale() === 'ar') ? 'rtl' : 'ltr' }}">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=Edge">
    <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <?php $ApplicationName = Config::get('settings.company_name'); ?>

    <title>@yield('title', 'Login') | {{ $ApplicationName }}</title>

    <!-- Company favicon-->
    @include('shared.favicon')

    <!-- Google Fonts -->

    <link href="{{ asset('asset/css/font-g/font-1.css') }}" rel="stylesheet" type="text/css">
    <link href="{{ asset('asset/css/font-g/icon-1.css') }}" rel="stylesheet" type="text/css">

    <!-- Bootstrap Core Css -->
    <link href="{{ asset('asset/plugins/bootstrap/css/bootstrap.css') }}" rel="stylesheet">

    <!-- Bootstrap Select is initialized globally by admin.js; load its stylesheet on every layout. -->
    <link href="{{ asset('asset/plugins/bootstrap-select/css/bootstrap-select.css') }}" rel="stylesheet">

    <!-- Waves Effect Css -->
    <link href="{{ asset('asset/plugins/node-waves/waves.css') }}" rel="stylesheet" />

    <!-- Animation Css -->
    <link href="{{ asset('asset/plugins/animate-css/animate.css') }}" rel="stylesheet" />

    <!-- Morris Chart Css -->
    <link href="{{ asset('asset/plugins/morrisjs/morris.css') }}" rel="stylesheet" />

    <!-- JQuery DataTable Css -->
    <link href="{{ asset('asset/plugins/jquery-datatable/skin/bootstrap/css/dataTables.bootstrap.css') }}"
        rel="stylesheet" />
    <link rel="stylesheet" href="{{ asset('asset/css/toastr/toastr.css') }}">
    @stack('include-css')
    <!-- Custom Css -->
    <link href="{{ asset('asset/css/style.css') }}" rel="stylesheet">
    @if ($is_rtl || app()->getLocale() === 'ar')
    <link href="{{ asset('asset/css/style-rtl.css') }}?v={{ filemtime(public_path('asset/css/style-rtl.css')) }}" rel="stylesheet">
    @endif
    <!-- Fonts Awesome -->
    <link rel="stylesheet" href="{{ asset('asset/css/fontawesome.css') }}">
    <!-- Utility Css -->
    <link rel="stylesheet" type="text/css" href="{{ asset('asset/layout/css/util.css') }}">
    <!-- AdminBSB Themes. You can choose a theme from css/themes instead of get all themes -->
    <link href="{{ asset('asset/css/themes/all-themes.css') }}" rel="stylesheet" />
    
    <!-- Dynamic Company Color Override -->
    <style>
        <?php 
            $companyColor = Config::get('settings.company_color', '#00897B');
            // Convert hex to RGB for rgba values if needed
            $hex = ltrim($companyColor, '#');
            $r = hexdec(substr($hex, 0, 2));
            $g = hexdec(substr($hex, 1, 2));
            $b = hexdec(substr($hex, 2, 2));
            
            // Calculate darker shade (darken by 10%)
            $darkerR = max(0, (int)($r * 0.9));
            $darkerG = max(0, (int)($g * 0.9));
            $darkerB = max(0, (int)($b * 0.9));
            $darkerColor = '#' . str_pad(dechex($darkerR), 2, '0', STR_PAD_LEFT) . 
                                 str_pad(dechex($darkerG), 2, '0', STR_PAD_LEFT) . 
                                 str_pad(dechex($darkerB), 2, '0', STR_PAD_LEFT);
        ?>
        :root {
            --company-color: {{ $companyColor }};
            --company-color-rgb: {{ $r }}, {{ $g }}, {{ $b }};
        }
        
        /* Override cyan colors with company color - ONLY for card headers and specific elements */
        
        /* Card headers ONLY - restrict to .card .header specifically */
        .card .header.bg-cyan,
        .card .header.bg-teal,
        .card-header.bg-cyan,
        .card-header.bg-teal {
            background-color: {{ $companyColor }} !important;
            color: #fff !important;
        }
        
        .card .header.bg-cyan::after {
            background-color: rgba({{ $r }}, {{ $g }}, {{ $b }}, 0.1) !important;
        }
        
        /* Material Design theme - sidebar and top header only */
        .theme-light-blue .sidebar,
        .theme-light-blue .header,
        .theme-light-blue .footer {
            background-color: {{ $companyColor }} !important;
        }
        
        /* Report section headers only */
        .report-header.bg-cyan,
        .report-header.bg-teal {
            background-color: {{ $companyColor }} !important;
            color: #fff !important;
        }
        
        /* Icon backgrounds in info boxes */
        .icon-big.bg-cyan,
        .icon-big.bg-teal {
            background-color: {{ $companyColor }} !important;
            color: #fff !important;
        }
        
        /* Buttons and interactive elements */
        .btn-cyan,
        .btn-teal {
            background-color: {{ $companyColor }} !important;
            border-color: {{ $companyColor }} !important;
            color: #fff !important;
        }
        
        .btn-cyan:hover,
        .btn-teal:hover {
            background-color: {{ $darkerColor }} !important;
            border-color: {{ $darkerColor }} !important;
            color: #fff !important;
        }
        
        /* Links - text cyan only */
        a.text-cyan {
            color: {{ $companyColor }} !important;
        }
        
        a.text-cyan:hover {
            color: {{ $darkerColor }} !important;
        }
        
        /* Text color only - NOT background */
        .text-cyan {
            color: {{ $companyColor }} !important;
        }
        
        .border-cyan {
            border-color: {{ $companyColor }} !important;
        }
        
        /* Enforce white background for body elements inside card headers */
        .card .header .body,
        .card .header + .body,
        .card-header .body,
        .card-header + .body {
            background-color: #fff !important;
            color: #333 !important;
        }

        section .content .header{
            background-color: white !important;
        }

        section .header h2 {
            color: black !important;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }
    </style>
</head>

<body class="theme-light-blue">
    <!-- Page Loader -->
    <div class="page-loader-wrapper">
        <div class="loader">
            <div class="preloader">
                <div class="spinner-layer pl-red">
                    <div class="circle-clipper left">
                        <div class="circle"></div>
                    </div>
                    <div class="circle-clipper right">
                        <div class="circle"></div>
                    </div>
                </div>
            </div>
            <p>{{ __('ui.common.please_wait') }}</p>
        </div>
    </div>
    <!-- #END# Page Loader -->
    <!-- Overlay For Sidebars -->
    <div class="overlay"></div>
    <!-- #END# Overlay For Sidebars -->
    <!-- Top Bar -->
    @yield('top-bar')
    <!-- #Top Bar -->
    <!-- Left Sidebar -->
    @yield('left-sidebar')
    <!-- #END# Left Sidebar -->
    @yield('content')
    <!-- base url generator -->
    <script>
        window.url = "<?php echo url('/'); ?>/";
        window.appDebug = parseInt("{{ env('APP_DEBUG') }}");
        window.ayuUiMessages = {
            confirmPermanentDelete: @json(__('ui.messages.confirm_permanent_delete')),
            confirmTrash: @json(__('ui.messages.confirm_trash')),
            selectAtLeastOneItem: @json(__('ui.messages.select_at_least_one_item')),
            selectAction: @json(__('ui.messages.select_action')),
            minimumSearchLetters: @json(__('ui.messages.minimum_search_letters')),
            networkError: @json(__('ui.messages.network_error')),
            missingPostType: @json(__('ui.messages.missing_post_type')),
            missingUrl: @json(__('ui.messages.missing_url'))
        };
    </script>

    <!-- Jquery Core Js -->
    <script src="{{ asset('asset/plugins/jquery/jquery.min.js') }}"></script>
    <!-- Bootstrap Core Js -->
    <script src="{{ asset('asset/plugins/bootstrap/js/bootstrap.js') }}"></script>
    <!-- Select Plugin Js -->
    <script src="{{ asset('asset/plugins/bootstrap-select/js/bootstrap-select.js') }}"></script>
    <!-- Slimscroll Plugin Js -->
    <script src="{{ asset('asset/plugins/jquery-slimscroll/jquery.slimscroll.js') }}"></script>
    <!-- Waves Effect Plugin Js -->
    <script src="{{ asset('asset/plugins/node-waves/waves.js') }}"></script>

    <!-- Custom Js -->
    @if ($is_rtl || app()->getLocale() === 'ar')
    <script src="{{ Helper::assetV('asset/js/admin-rtl.js') }}"></script>
    @else
    <script src="{{ Helper::assetV('asset/js/admin.js') }}"></script>
    @endif

    <script src="{{ asset('asset/js/pages/ui/tooltips-popovers.js') }}"></script>
    <script src="{{ asset('asset/js/toastr.min.js') }}"></script>
    <!-- Demo Js -->
    <script src="{{ asset('asset/js/demo.js') }}"></script>

    <!-- axios Js -->
    <script src="{{ asset('asset/js/axios.min.js') }}"></script>
    <script src="{{ Helper::assetV('asset/js/helpers.js') }}"></script>
    <!-- page ways script -->
    @stack('include-js')
</body>

</html>
