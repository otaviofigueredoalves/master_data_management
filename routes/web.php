<?php

use App\Http\Controllers\TenantSelectController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// Landing page (blade) — será substituída pelo SPA em Vue no futuro.
Route::view('/', 'landing')->name('home');

// Conveniência para entrevistas/demos locais — nunca exposta fora de local.
if (app()->environment('local', 'testing')) {
    Route::get('/demo-login', function () {
        $user = User::where('email', 'demo@mdmsaas.test')->first();

        abort_unless($user, 404, 'Execute php artisan db:seed antes do demo login.');

        auth()->login($user);

        session(['current_tenant_id' => $user->current_tenant_id]);

        return redirect()->intended('/');
    })->name('demo.login');
}

// Seleção de tenant para sessões de browser.
Route::middleware('auth')->group(function () {
    Route::get('/tenants/select', [TenantSelectController::class, 'create'])->name('tenants.select');
    Route::post('/tenants/switch', [TenantSelectController::class, 'store'])->name('tenants.switch');
});
