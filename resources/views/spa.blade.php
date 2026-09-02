<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'MDM SaaS') }}</title>
    <script>
        window.__MDM_CONFIG__ = {!! json_encode([
            'appName' => config('app.name', 'MDM SaaS'),
            'env' => app()->environment(),
            'socialProviders' => collect(['google', 'github', 'microsoft'])
                ->filter(fn (string $provider) => (bool) config("services.{$provider}.client_id"))
                ->values()
                ->all(),
        ]) !!};
    </script>
    <style>
        html { background: #f6f7fb; }
        .app-boot {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 14px;
            color: #475467;
            font-family: ui-sans-serif, system-ui, sans-serif;
            font-size: 14px;
        }
        .app-boot__mark {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            background: linear-gradient(135deg, #4f46e5, #7c3aed);
            display: grid;
            place-items: center;
            color: #fff;
            font-weight: 700;
            animation: boot-pulse 1.4s ease-in-out infinite;
        }
        @keyframes boot-pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.06); opacity: .85; }
        }
    </style>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <div id="app">
        <div class="app-boot">
            <div class="app-boot__mark">M</div>
            <div>Carregando {{ config('app.name', 'MDM SaaS') }}…</div>
        </div>
    </div>
</body>
</html>
