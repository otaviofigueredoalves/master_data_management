@extends('layouts.guest')

@section('title', 'Confirme sua senha')

@section('content')
    <div class="card auth">
        <h1>Confirme sua senha</h1>
        <p class="subtitle">Por segurança, confirme sua senha para continuar.</p>

        <form method="POST" action="{{ route('password.confirm') }}">
            @csrf

            <label for="password">Senha</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="field-error">{{ $message }}</div>@enderror

            <button type="submit" class="btn btn-primary">Confirmar</button>
        </form>
    </div>
@endsection
