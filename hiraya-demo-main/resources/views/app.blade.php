<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Hiraya — Good Shepherd of Taguig Colleges, Inc.">
    <link rel="icon" type="image/jpeg" href="/images/gstc-seal.jpg">
    <title inertia>{{ config('app.name', 'Hiraya') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.ts'])
    @inertiaHead
</head>
<body class="h-full bg-cream-100 font-sans text-navy-900 antialiased">
    @inertia
</body>
</html>
