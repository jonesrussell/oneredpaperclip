<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">

        @php
            $pageMeta = $page['props']['meta'] ?? [];
            $sharedMeta = $page['props']['sharedMeta'] ?? [];
            $metaTitle = $pageMeta['title'] ?? config('app.name');
            $metaDescription = $pageMeta['description'] ?? config('seo.description');
            $metaImage = $pageMeta['image'] ?? config('seo.og_image') ?? (config('app.url') . '/favicon.svg');
            $canonicalUrl = $pageMeta['canonical'] ?? url()->current();
            $ogType = $pageMeta['og_type'] ?? 'website';
            $robots = $pageMeta['robots'] ?? $sharedMeta['robots'] ?? null;
            $schema = $pageMeta['schema'] ?? null;
        @endphp
        <meta name="description" content="{{ $metaDescription }}">
        <link rel="canonical" href="{{ $canonicalUrl }}">
        @if($robots)
        <meta name="robots" content="{{ $robots }}">
        @endif

        {{-- Open Graph --}}
        <meta property="og:type" content="{{ $ogType }}">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:title" content="{{ $metaTitle }}">
        <meta property="og:description" content="{{ $metaDescription }}">
        <meta property="og:image" content="{{ $metaImage }}">
        @if(config('seo.og_image') || ($pageMeta['image'] ?? null))
        <meta property="og:image:width" content="1200">
        <meta property="og:image:height" content="630">
        @endif
        <meta property="og:url" content="{{ $canonicalUrl }}">
        <meta property="og:locale" content="{{ str_replace('_', '-', app()->getLocale()) }}">

        {{-- Twitter Card --}}
        <meta name="twitter:card" content="summary_large_image">
        @if(config('seo.twitter_site'))
        <meta name="twitter:site" content="{{ config('seo.twitter_site') }}">
        @endif
        @if(config('seo.twitter_creator'))
        <meta name="twitter:creator" content="{{ config('seo.twitter_creator') }}">
        @endif
        <meta name="twitter:title" content="{{ $metaTitle }}">
        <meta name="twitter:description" content="{{ $metaDescription }}">
        <meta name="twitter:image" content="{{ $metaImage }}">

        {{-- Structured Data (JSON-LD) --}}
        @if($schema)
        <script type="application/ld+json">
            {!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
        @endif

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: hsl(28 60% 98%);
                transition: background-color 0.2s ease;
            }

            html.dark {
                background-color: #13111C;
            }
        </style>

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=dm-sans:400,500,600,700|fredoka:400,500,600,700|jetbrains-mono:400,500" rel="stylesheet" />

        @env('production')
        <!-- Google tag (gtag.js) -->
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-XMJMS3L9PG"></script>
        <script>
            window.dataLayer = window.dataLayer || [];
            function gtag(){dataLayer.push(arguments);}
            gtag('js', new Date());
            gtag('config', 'G-XMJMS3L9PG');
        </script>
        @endenv

        @vite(['resources/js/app.ts'])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>
