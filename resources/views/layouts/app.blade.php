<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }}</title>

    @vite(['resources/css/app.css'])
</head>
<body>
<div class="container">
    <div class="row justify-content-center mt-4 mt-md-0">
        <div class="col-md-4">
            @yield('content')
        </div>
    </div>
</div>

@vite(['resources/js/app.js'])
</body>
</html>
