@extends('layouts.guest')

@section('title', 'Recuperar senha')

@section('content')
    <div class="card auth">
        <h1>Recuperar senha</h1>
        <p class="subtitle">Informe seu e-mail e enviaremos um link de redefinição.</p>

        @if (session('status'))
            <div class="alert alert-success">{{ session('status') }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}">
            @csrf

            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email')<div class="field-error">{{ $message }}</div>@enderror

            <button type="submit" class="btn btn-primary">Enviar link</button>
        </form>

        <p class="footer-note"><a href="{{ route('login') }}">Voltar para o login</a></p>
    </div>
@endsection
