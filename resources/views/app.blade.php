<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    {{-- 基础元数据 --}}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    {{-- 网站标题 --}}
    <title inertia>{{ config('app.name', 'Laravel') }}</title>
    
    {{-- 网站图标 --}}
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    
    {{-- 字体加载 --}}
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet">
    
    {{-- TinyMCE 编辑器 --}}
    <script src="{{ asset('tinymce/tinymce.min.js') }}" referrerpolicy="origin" defer></script>
    <script>
        window.addEventListener('load', () => {
            // 配置 TinyMCE 编辑器
            window.tinymce?.overrideDefaults({
                base_url: '{{ asset("tinymce") }}',
                suffix: '.min'
            });
        });
    </script>
    
    {{-- Inertia & Vite 资源 --}}
    @routes
    @vite(['resources/js/app.js', "resources/js/Pages/{$page['component']}.vue"])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    {{-- Inertia 应用挂载点 --}}
    @inertia
</body>
</html>