<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name') . ' - Online Payment Platform')</title>
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon.ico') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/apple-touch-icon.png') }}">
    <meta name="description" content="@yield('meta_description', 'Buy airtime, pay bills, and access services securely on our payment platform.')">
    <meta name="keywords" content="@yield('meta_keywords', 'airtime, data, utility bills, VTU, Nigeria, payment platform, buy airtime, subscribe')">
    {{-- this is for social image --}}
    <meta property="og:title" content="@yield('og_title', config('app.name'))" />
    <meta property="og:description" content="@yield('og_description', 'Pay bills, buy airtime, and access digital services with ease.')" />
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.png'))" />
    <meta property="og:url" content="{{ url()->current() }}" />
    <meta property="og:type" content="website" />

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="@yield('twitter_title', config('app.name'))">
    <meta name="twitter:description" content="@yield('twitter_description', 'Your go-to platform for airtime and bill payments.')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('images/twitter-default.png'))">
    <meta name="google-site-verification" content="XaMryBLZrVMyCvIotXqA_bCYDGfXNF7E_3v72dibIGM" />
    {{-- social media image close --}}
    <link rel="canonical" href="{{ url()->current() }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Toastr CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.css" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/toast.css') }}">
    <link rel="stylesheet" href="{{ asset('css/styles.css') }}">
    @yield('styles')
    @stack('styles')
    <script type="application/ld+json">
        {
        "@context": "https://schema.org",
        "@type": "Service",
        "name": "Airtime Recharge",
        "provider": {
            "@type": "Organization",
            "name": "{{ config('app.name') }}"
        },
        "areaServed": "NG",
        "serviceType": "Mobile Recharge"
        }
    </script>
</head>
<body class="scroll-smooth">
    @include('partials.navbar')

    @yield('content')

    @include('partials.footer')

    <a href="#home" class="back-to-top">
        <i class="fas fa-arrow-up"></i>
    </a>
    <!-- jQuery first -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Toastr JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/toastr.min.js"></script>
    <script src="{{ asset('js/script.js') }}" defer></script>
    <script src="{{ asset('js/toast.js') }}" defer></script>
    @yield('scripts')
    @stack('scripts')
</body>
</html>