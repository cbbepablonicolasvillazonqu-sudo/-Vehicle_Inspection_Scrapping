<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sin conexión · Forte Towing</title>
    {{-- Estilos en línea: esta página debe funcionar sin ningún asset externo --}}
    <style>
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
               background: #f3f4f6; color: #111827; display: flex; min-height: 100vh;
               align-items: center; justify-content: center; padding: 24px; }
        .tarjeta { background: #fff; border-radius: 16px; box-shadow: 0 4px 14px rgba(0,0,0,.08);
                   padding: 32px 28px; max-width: 380px; text-align: center; }
        .icono { font-size: 52px; }
        h1 { font-size: 22px; margin: 12px 0 8px; }
        p { color: #6b7280; font-size: 15px; line-height: 1.5; margin: 0 0 20px; }
        button { background: #1e40af; color: #fff; border: 0; border-radius: 12px;
                 padding: 14px 26px; font-size: 16px; font-weight: 600; cursor: pointer; width: 100%; }
        button:active { background: #1e3a8a; }
    </style>
</head>
<body>
    <div class="tarjeta">
        <div class="icono">📡</div>
        <h1>Sin conexión</h1>
        <p>No pudimos conectar con el servidor de Forte Towing. Revisa tu señal o tus datos móviles e inténtalo de nuevo.</p>
        <button onclick="location.reload()">Reintentar</button>
    </div>
</body>
</html>
