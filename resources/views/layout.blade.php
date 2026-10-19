<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('activityloggerui::history.title') }} · {{ $modelName }}</title>
    <style>body { margin: 0; background: #f5f7fa; }</style>
</head>
<body>
    <main>@yield('content')</main>
</body>
</html>
