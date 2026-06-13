<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Simulazione Spedizione</title>
    <style>
        body { font-family: sans-serif; background-color: #f4f7f6; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .card { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); text-align: center; }
        h1 { color: #333; }
        .btn-orange { 
            display: inline-block; 
            padding: 1rem 2rem; 
            background-color: #ff8c00; 
            color: white; 
            text-decoration: none; 
            border-radius: 6px; 
            font-weight: bold; 
            margin-top: 20px;
        }
        .btn-orange:hover { background-color: #e67e00; }
    </style>
</head>
<body>

<div class="card">
    <h1>Pagina di Simulazione</h1>
    <p>Qui ci sarà il simulatore di spedizione.</p>
    <p>Per provare il reindirizzamento, clicca sul tasto sotto e accedi.</p>
    
    <a href="{{ route('login') }}" class="btn-orange">ACCEDI PER CONTINUARE</a>
</div>

</body>
</html>