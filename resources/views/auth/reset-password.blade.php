@extends('layouts.guest')

@section('title', 'Redefinir senha')

@section('content')
    <div class="card auth">
        <h1>Defina uma nova senha</h1>

        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <label for="email">E-mail</label>
            <input id="email" type="email" name="email" value="{{ old('email', request()->query('email')) }}" required autofocus>
            @error('email')<div class="field-error">{{ $message }}</div>@enderror

            <label for="password">Nova senha</label>
            <input id="password" type="password" name="password" required autocomplete="new-password">
            @error('password')<div class="field-error">{{ $message }}</div>@enderror

            <label for="password_confirmation">Confirme a senha</label>
            <input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password">

            <button type="submit" class="btn btn-primary">Redefinir senha</button>
        </form>
    </div>
@endsection
