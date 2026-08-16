@extends('layouts.guest')

@section('title', 'Criar conta')

@section('content')
    <div class="card auth">
        <h1>Crie sua conta</h1>
        <p class="subtitle">Plataforma SaaS B2B de Master Data Management.</p>

        <form method="POST" action="{{ route('register') }}">
            @csrf

            <label for="name">Nome</label>
            <input id="name" type="text" name="name" value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name')<div class="field-error">{{ $message }}</div>@enderror

            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="username">
            @error('email')<div class="field-error">{{ $message }}</div>@enderror

            <label for="password">Senha</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password')<div class="field-error">{{ $message }}</div>@enderror

            <label for="password_confirmation">Confirme a senha</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

            <button type="submit" class="btn btn-primary">Criar conta</button>
        </form>

        <p class="footer-note">Já possui conta? <a href="{{ route('login') }}">Entrar</a></p>
    </div>
@endsection
