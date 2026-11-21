<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

    <!-- Bootstrap Core Css -->
    <link href="{{ asset('asset/plugins/bootstrap/css/bootstrap.css') }}" rel="stylesheet">
    {{--pdf style--}}
    <link rel="stylesheet" href="{{ asset('asset/css/pdf.css') }}">


    <title>{{ $extra['module_name']  }}</title>
</head>
<body>


<section @if($is_rtl) dir="rtl" @endif class="content">
    <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
        <div class="mb-1 border-b-1">
            <div class="header">
                <p>{{ $extra['current_date_time']  }}</p>

                <h1 class="text-center">{{ Config('settings.company_name')  }}</h1>
                <p class="text-center">{{ Config('settings.address_1')  }}</p>

            </div>
        </div>
        <div class="module_name">
            <p>{{ __('Module Name') }} : {{ $extra['module_name']  }}</p>
        </div>
        <div class="card">
            <div class="header">
                <h3>
                    {{  $items->name  }}
                </h3>
                <div class="body">
                    <div class="row clearfix">
                        <div class="col-lg-12 col-md-12 col-sm-12 col-xs-12">
                            <table class="table  table-bordered table-hover">
                                <thead>
                                <tr>
                                    <th scope="col" class="text-center">S.L</th>
                                    <th scope="col" class="text-center">{{ __('ui.permissions.module_name') }}</th>
                                    <th scope="col" class="text-center">{{ __('ui.permissions.module_show') }}</th>
                                    <th scope="col" class="text-center">{{ __('ui.permissions.record_show') }}</th>
                                    <th scope="col" class="text-center">{{ __('ui.permissions.create') }}</th>
                                    <th scope="col" class="text-center">{{ __('ui.permissions.edit') }}</th>
                                    <th scope="col" class="text-center">{{ __('ui.permissions.delete') }}</th>
                                    <th scope="col" class="text-center">{{ __('ui.permissions.pdf') }}</th>
                                    <th scope="col" class="text-center">{{ __('ui.permissions.trash_show') }}</th>
                                    <th scope="col" class="text-center">{{ __('root.common.restore') }}</th>
                                    <th scope="col" class="text-center">{{ __('root.common.permanently_delete') }}</th>
                                </tr>
                                </thead>
                                <tbody>

                                <?php $sl = 1; ?>
                                @foreach( $items->content as $itemKey=>$item)
                                    <tr>
                                        <th scope="row" class="text-center">{{ $sl  }}</th>

                                        @foreach($item as $key=>$value)
                                            <td class="text-center">

                                                @if($key==0)
                                                    {{ $value }}
                                                @elseif($value==1)
                                                    {{ __('On') }}
                                                @else
                                                    {{ __('Off') }}
                                                @endif
                                            </td>
                                        @endforeach
                                    </tr>
                                    <?php $sl++; ?>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- #END# Inline Layout | With Floating Label -->
</section>

</body>
</html>

