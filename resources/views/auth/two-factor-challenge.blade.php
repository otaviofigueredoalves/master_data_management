@extends('layouts.guest')

@section('title', 'Verificação em duas etapas')

@section('content')
    <div class="card auth">
        <h1>Verificação em duas etapas</h1>
        <p class="subtitle">Informe o código do seu aplicativo autenticador ou um código de recuperação.</p>

        <form method="POST" action="{{ route('two-factor.login') }}">
            @csrf

            <label for="code">Código</label>
            <input id="code" type="text" name="code" inputmode="numeric" autofocus autocomplete="one-time-code" placeholder="123456">
            @error('code')<div class="field-error">{{ $message }}</div>@enderror

            <button type="submit" class="btn btn-primary">Verificar</button>
        </form>

        <p class="footer-note">Não tem acesso ao aplicativo? Use um <a href="#" onclick="event.preventDefault();document.getElementById('recovery').style.display='block'">código de recuperação</a>.</p>

        <form id="recovery" method="POST" action="{{ route('two-factor.login') }}" style="display:none">
            @csrf
            <label for="recovery_code">Código de recuperação</label>
            <input id="recovery_code" type="text" name="recovery_code" autocomplete="one-time-code">
            @error('recovery_code')<div class="field-error">{{ $message }}</div>@enderror
            <button type="submit" class="btn btn-primary">Verificar</button>
        </form>
    </div>
@endsection
