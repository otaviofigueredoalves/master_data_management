@extends('layouts.guest')

@section('title', 'Plataforma MDM')

@section('content')
    <div class="card wide">
        <span class="pill">SaaS B2B · Master Data Management</span>
        <h1 style="font-size:34px;margin-top:14px">Plataforma multi-tenant de governança de dados mestres</h1>
        <p class="subtitle" style="font-size:17px">
            Cadastro e autenticação com <strong>Laravel Fortify</strong> (incluindo 2FA),
            login social com <strong>Laravel Socialite</strong> (Google / GitHub / Microsoft)
            e API REST segura com <strong>Laravel Sanctum</strong>.
        </p>

        <div class="row" style="gap:12px;flex-wrap:wrap;margin:8px 0 28px">
            <a class="btn btn-primary" style="width:auto" href="/docs">Explorar a API</a>
            @auth
                <a class="btn btn-ghost" style="width:auto" href="{{ route('tenants.select') }}">Meus tenants</a>
            @else
                <a class="btn btn-ghost" style="width:auto" href="{{ route('login') }}">Entrar na plataforma</a>
            @endauth
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:16px">
            @php
                $features = [
                    ['Multi-tenancy', 'Isolamento por tenant com X-Tenant-ID, memberships e roles por contexto.'],
                    ['RBAC', 'Roles admin / manager / user por tenant e super-admin global via Spatie Permission.'],
                    ['MDM entities', 'Registros mestres versionados com normalização e promoção a golden record.'],
                    ['Integrações', 'Sincronização assíncrona via filas com SyncJob monitorável.'],
                    ['Auditoria', 'Trilha de auditoria por tenant com actor, IP e user-agent.'],
                    ['API tokens', 'Tokens Sanctum com abilities, revogáveis a qualquer momento.'],
                ];
            @endphp
            @foreach ($features as [$title, $body])
                <div style="border:1px solid var(--border);border-radius:12px;padding:16px">
                    <strong>{{ $title }}</strong>
                    <p class="text-muted" style="margin:6px 0 0">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </div>
@endsection
