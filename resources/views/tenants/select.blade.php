@extends('layouts.guest')

@section('title', 'Selecionar tenant')

@section('content')
    <div class="card auth">
        <h1>Selecione um tenant</h1>
        <p class="subtitle">Escolha em qual organização você deseja trabalhar agora.</p>

        @if ($tenants->isEmpty())
            <p class="text-muted">Você ainda não pertence a nenhum tenant. Crie um tenant via <code>POST /api/v1/tenants</code> ou aceite um convite.</p>
        @else
            <div style="display:flex;flex-direction:column;gap:10px">
                @foreach ($tenants as $tenant)
                    <form method="POST" action="{{ route('tenants.switch') }}">
                        @csrf
                        <input type="hidden" name="tenant_id" value="{{ $tenant->id }}">
                        <button type="submit" class="btn btn-ghost" style="display:flex;justify-content:space-between;align-items:center">
                            <span>{{ $tenant->name }}</span>
                            @if (session('current_tenant_id') === $tenant->id)
                                <span class="pill">ativo</span>
                            @endif
                        </button>
                    </form>
                @endforeach
            </div>
        @endif

        <p class="footer-note"><a href="/">Voltar ao início</a></p>
    </div>
@endsection
