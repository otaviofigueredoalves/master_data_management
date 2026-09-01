@extends('layouts.guest')

@section('title', 'API Docs')

@section('content')
    <div class="card wide" style="max-width:920px">
        <h1>Referência da API REST</h1>
        <p class="subtitle">Base URL: <code>{{ url('/api/v1') }}</code> · Autenticação: <code>Authorization: Bearer &lt;token&gt;</code></p>

        <h2 style="font-size:18px;margin-top:28px">Credenciais de demonstração (após <code>php artisan db:seed</code>)</h2>
        <table>
            <tr><th>E-mail</th><th>Role</th><th>Senha</th></tr>
            <tr><td>demo@mdmsaas.test</td><td>admin (tenant Acme)</td><td><code>password</code></td></tr>
            <tr><td>manager@mdmsaas.test</td><td>manager</td><td><code>password</code></td></tr>
            <tr><td>viewer@mdmsaas.test</td><td>user (viewer)</td><td><code>password</code></td></tr>
            <tr><td>platform@mdmsaas.test</td><td>super-admin (global)</td><td><code>password</code></td></tr>
        </table>

        <h2 style="font-size:18px;margin-top:28px">Exemplo — obter token</h2>
        <pre>curl -X POST {{ url('/api/v1/auth/login') }} \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"demo@mdmsaas.test","password":"password"}'</pre>

        <h2 style="font-size:18px;margin-top:28px">Exemplo — listar entidades MDM do tenant</h2>
        <pre>curl {{ url('/api/v1/mdm-entities') }} \
  -H "Accept: application/json" \
  -H "Authorization: Bearer TOKEN" \
  -H "X-Tenant-ID: 1"</pre>

        <h2 style="font-size:18px;margin-top:28px">Autenticação e conta</h2>
        <table>
            <tr><th>Método</th><th>Rota</th><th>Descrição</th></tr>
            <tr><td>POST</td><td>/auth/register</td><td>Cria conta e retorna token Sanctum</td></tr>
            <tr><td>POST</td><td>/auth/login</td><td>Login com e-mail/senha (suporta 2FA via <code>code</code>)</td></tr>
            <tr><td>POST</td><td>/auth/logout</td><td>Revoga o token atual</td></tr>
            <tr><td>GET/PUT</td><td>/me</td><td>Perfil do usuário autenticado</td></tr>
            <tr><td>GET/POST/DELETE</td><td>/tokens</td><td>Gerenciar tokens de acesso pessoal</td></tr>
        </table>

        <h2 style="font-size:18px;margin-top:28px">Tenancy</h2>
        <table>
            <tr><th>Método</th><th>Rota</th><th>Descrição</th></tr>
            <tr><td>GET</td><td>/tenants</td><td>Lista tenants do usuário</td></tr>
            <tr><td>POST</td><td>/tenants</td><td>Cria tenant (criador vira admin)</td></tr>
            <tr><td>POST</td><td>/tenants/switch</td><td>Troca o tenant ativo</td></tr>
        </table>

        <h2 style="font-size:18px;margin-top:28px">Dados e operações (requer tenant ativo)</h2>
        <table>
            <tr><th>Método</th><th>Rota</th><th>Descrição</th></tr>
            <tr><td>CRUD</td><td>/mdm-entities</td><td>Entidades mestres (filtros: type, source_system, is_master)</td></tr>
            <tr><td>POST</td><td>/mdm-entities/{id}/normalize</td><td>Aplica regras de normalização e promove a golden record</td></tr>
            <tr><td>GET</td><td>/mdm-entities/{id}/relations</td><td>Relacionamentos da entidade</td></tr>
            <tr><td>CRUD</td><td>/integrations</td><td>Integrações do tenant</td></tr>
            <tr><td>POST</td><td>/integrations/{id}/sync</td><td>Enfileira sincronização assíncrona</td></tr>
            <tr><td>GET</td><td>/sync-jobs</td><td>Histórico de sincronizações</td></tr>
            <tr><td>GET</td><td>/audit-logs</td><td>Trilha de auditoria (admin/manager)</td></tr>
            <tr><td>GET/POST</td><td>/users, /invitations</td><td>Gerenciar membros e convites</td></tr>
            <tr><td>GET</td><td>/roles</td><td>Catálogo de roles do tenant</td></tr>
            <tr><td>GET</td><td>/dashboard</td><td>Métricas consolidadas (cache por tenant)</td></tr>
        </table>

        <p class="footer-note"><a href="/">Voltar ao início</a></p>
    </div>
@endsection
