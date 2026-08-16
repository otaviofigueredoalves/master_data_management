<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'MDM SaaS')) — {{ config('app.name', 'Laravel') }}</title>
    <style>
        :root {
            --bg: #f6f7fb;
            --card: #ffffff;
            --border: #e4e7ef;
            --text: #101828;
            --muted: #667085;
            --primary: #4f46e5;
            --primary-dark: #4338ca;
            --success: #12b76a;
            --danger: #f04438;
            --radius: 14px;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: "Inter", "Segoe UI", system-ui, -apple-system, sans-serif;
            background: var(--bg);
            color: var(--text);
            -webkit-font-smoothing: antialiased;
            min-height: 100vh;
        }
        a { color: var(--primary); text-decoration: none; }
        a:hover { text-decoration: underline; }
        .shell { max-width: 1120px; margin: 0 auto; padding: 0 24px; }
        header.site { padding: 22px 0; }
        .brand { font-weight: 700; font-size: 18px; letter-spacing: -.02em; display: flex; gap: 10px; align-items: center; }
        .brand .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--primary); display: inline-block; }
        main { display: flex; align-items: center; justify-content: center; padding: 40px 0 64px; }
        .card {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: var(--radius);
            box-shadow: 0 1px 3px rgba(16, 24, 40, .06);
            width: 100%;
        }
        .card.auth { max-width: 420px; padding: 32px; }
        .card.wide { max-width: 760px; padding: 40px; }
        h1 { font-size: 24px; margin: 0 0 6px; letter-spacing: -.02em; }
        .subtitle { color: var(--muted); margin: 0 0 24px; font-size: 15px; line-height: 1.5; }
        label { display: block; font-size: 14px; font-weight: 600; margin: 16px 0 6px; }
        input[type=text], input[type=email], input[type=password], input[type=url], input[type=number], select {
            width: 100%; padding: 10px 12px; border: 1px solid var(--border);
            border-radius: 10px; font-size: 15px; background: #fff; color: var(--text);
        }
        input:focus, select:focus { outline: 3px solid rgba(79, 70, 229, .18); border-color: var(--primary); }
        .btn {
            display: inline-flex; align-items: center; justify-content: center; gap: 8px;
            border-radius: 10px; border: 1px solid transparent; cursor: pointer;
            font-size: 15px; font-weight: 600; padding: 10px 18px; transition: all .15s ease;
        }
        .btn-primary { background: var(--primary); color: #fff; width: 100%; margin-top: 20px; }
        .btn-primary:hover { background: var(--primary-dark); text-decoration: none; }
        .btn-ghost { background: #fff; color: var(--text); border-color: var(--border); width: 100%; }
        .btn-ghost:hover { background: #f9fafb; text-decoration: none; }
        .btn-block { width: 100%; margin-top: 12px; }
        .divider { display: flex; align-items: center; gap: 12px; color: var(--muted); font-size: 13px; margin: 20px 0 4px; }
        .divider::before, .divider::after { content: ""; flex: 1; height: 1px; background: var(--border); }
        .alert { border-radius: 10px; padding: 12px 14px; font-size: 14px; margin-bottom: 16px; }
        .alert-error { background: #fef3f2; color: #b42318; border: 1px solid #fecdca; }
        .alert-success { background: #ecfdf3; color: #027a48; border: 1px solid #a6f4c5; }
        .field-error { color: var(--danger); font-size: 13px; margin-top: 4px; }
        .text-muted { color: var(--muted); font-size: 14px; }
        .row { display: flex; gap: 16px; align-items: center; }
        .footer-note { text-align: center; color: var(--muted); font-size: 14px; margin-top: 24px; }
        ul.menu { list-style: none; padding: 0; margin: 0; display: flex; gap: 22px; align-items: center; }
        .pill { background: #eef2ff; color: #4338ca; font-size: 13px; padding: 4px 10px; border-radius: 999px; font-weight: 600; }
        code, pre { font-family: "JetBrains Mono", ui-monospace, SFMono-Regular, monospace; }
        pre { background: #101828; color: #e5e7eb; padding: 18px; border-radius: 10px; overflow-x: auto; font-size: 13px; line-height: 1.6; }
        table { width: 100%; border-collapse: collapse; font-size: 14px; }
        th, td { text-align: left; padding: 10px 12px; border-bottom: 1px solid var(--border); }
        th { color: var(--muted); font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; }
    </style>
</head>
<body>
<header class="site shell">
    <div class="row" style="justify-content: space-between">
        <a href="/" class="brand" style="color:var(--text)"><span class="dot"></span>MDM SaaS</a>
        <nav>
            <ul class="menu">
                <li><a href="/docs">API Docs</a></li>
                @auth
                    <li><a href="{{ route('tenants.select') }}">Meus tenants</a></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-ghost" style="width:auto;padding:6px 14px">Sair</button>
                        </form>
                    </li>
                @else
                    <li><a href="{{ route('login') }}">Entrar</a></li>
                    <li><a href="{{ route('register') }}"><span class="pill">Criar conta</span></a></li>
                @endauth
            </ul>
        </nav>
    </div>
</header>

<main class="shell">
    @if (session('status'))
        <div class="alert alert-success" style="position:fixed;top:16px;right:16px;z-index:50">{{ session('status') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-error" style="max-width:760px">
            <strong>Verifique os campos abaixo:</strong>
            <ul style="margin:6px 0 0 18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    @yield('content')
</main>
</body>
</html>
