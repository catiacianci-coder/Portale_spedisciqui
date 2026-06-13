<!DOCTYPE html>
<html lang="it" class="sq-html">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Errore') — Spedisciqui</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="sq-body">
    <div class="sq-shell">
        <main class="sq-main sq-main--error-page">
            @yield('content')
        </main>
    </div>
</body>
</html>
