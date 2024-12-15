<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title inertia>{{ config('app.name', 'Laravel') }}</title>

        <!-- Favicon -->
        <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- TinyMCE -->
        <script src="{{ asset('tinymce/tinymce.min.js') }}"></script>
        <script>
            // 设置 TinyMCE 的全局配置
            window.tinymce.overrideDefaults({
                base_url: '{{ asset("tinymce") }}',
                suffix: '.min',
                language: 'zh_CN',
                content_css: [
                    '{{ asset("css/content.css") }}'
                ]
            });
        </script>

        <!-- Scripts -->
        @routes
        @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
        @inertiaHead
    </head>
    <body class="font-sans antialiased">
        @inertia
    </body>
</html>