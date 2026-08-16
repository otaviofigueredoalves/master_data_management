@extends('layouts.guest')

@section('title', 'Entrar')

@section('content')
    <div class="card auth">
        <h1>Bem-vindo de volta</h1>
        <p class="subtitle">Acesse sua conta para gerenciar seus dados mestres.</p>

        <form method="POST" action="{{ route('login') }}">
            @csrf

            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')<div class="field-error">{{ $message }}</div>@enderror

            <label for="password">Senha</label>
            <input id="password" type="password" name="password" required autocomplete="current-password">
            @error('password')<div class="field-error">{{ $message }}</div>@enderror

            <div class="row" style="justify-content: space-between; margin-top: 14px">
                <label style="margin:0;font-weight:500;display:flex;gap:8px;align-items:center">
                    <input type="checkbox" name="remember" style="width:auto"> Lembrar-me
                </label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}">Esqueci minha senha</a>
                @endif
            </div>

            <button type="submit" class="btn btn-primary">Entrar</button>
        </form>

        @if ($socialProviders = App\Http\Controllers\Auth\SocialAuthController::availableProviders())
            <div class="divider">ou continue com</div>
            <div style="display:flex;flex-direction:column;gap:10px">
                @foreach ($socialProviders as $provider)
                    <a href="{{ route('socialite.redirect', $provider) }}" class="btn btn-ghost">
                        {{ ucfirst($provider) }}
                    </a>
                @endforeach
            </div>
        @endif

        @if (app()->environment('local', 'testing'))
            <div class="divider">ambiente de demonstração</div>
            <a href="{{ route('demo.login') }}" class="btn btn-ghost">Entrar como demo (Ana — admin)</a>
        @endif

        <p class="footer-note">Ainda não tem conta? <a href="{{ route('register') }}">Cadastre-se</a></p>
    </div>
@endsection
