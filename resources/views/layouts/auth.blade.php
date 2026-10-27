<!DOCTYPE html>
<html lang="ar" dir="rtl" direction="rtl" style="direction: rtl;">
<head>
    <base href=""/>
    <title>{{ config('app.name', 'نظام الدعم الفني والمهام') }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover" />
    @include('layouts._pwa_head')
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo:300,400,500,600,700" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="{{ asset('assets/plugins/global/plugins.bundle.rtl.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.rtl.css') }}" rel="stylesheet" type="text/css" />
    <link rel="shortcut" href="{{ asset('assets/media/logos/logos.png') }}" />
    <style>
        body { font-family: 'Cairo', 'Inter', sans-serif !important; }
    </style>
</head>
<body id="kt_body" class="bg-body">
    <div class="d-flex flex-column flex-root">
        <div class="d-flex flex-column flex-column-fluid bgi-position-y-bottom position-x-center bgi-no-repeat bgi-size-contain bgi-attachment-fixed">
            <div class="d-flex flex-center flex-column flex-column-fluid p-10 pb-lg-20">
                <a href="{{ url('/') }}" class="mb-12">
                    <h1 class="text-dark fw-bolder mb-3">نظام التذاكر</h1>
                </a>
                <div class="w-lg-500px bg-body rounded shadow-sm p-10 p-lg-15 mx-auto">
                    @yield('content')
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
    <script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>
</body>
</html>
