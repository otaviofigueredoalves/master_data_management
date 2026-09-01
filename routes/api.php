<?php

use App\Http\Controllers\Api\V1\AuditLogController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\IntegrationController;
use App\Http\Controllers\Api\V1\InvitationController;
use App\Http\Controllers\Api\V1\MdmEntityController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\SyncJobController;
use App\Http\Controllers\Api\V1\TenantController;
use App\Http\Controllers\Api\V1\TokenController;
use App\Http\Controllers\Api\V1\UserController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('api.v1.')->group(function () {
    // Public: authentication + invitation acceptance.
    Route::post('auth/login', [AuthController::class, 'login'])
        ->middleware('throttle:5,1')
        ->name('auth.login');
    Route::post('auth/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('auth.register');
    Route::post('invitations/{invitation:token}/accept', [InvitationController::class, 'accept'])
        ->middleware('throttle:5,1')
        ->name('invitations.accept');

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout'])->name('auth.logout');
        Route::get('me', [AuthController::class, 'me'])->name('me');
        Route::put('me', [AuthController::class, 'updateProfile'])->name('me.update');

        Route::get('tokens', [TokenController::class, 'index'])->name('tokens.index');
        Route::post('tokens', [TokenController::class, 'store'])->name('tokens.store');
        Route::delete('tokens/{token}', [TokenController::class, 'destroy'])->name('tokens.destroy');

        // Tenancy lifecycle — no active tenant required (self-service onboarding).
        Route::get('tenants', [TenantController::class, 'index'])->name('tenants.index');
        Route::post('tenants', [TenantController::class, 'store'])->name('tenants.store');
        Route::post('tenants/switch', [TenantController::class, 'switch'])->name('tenants.switch');

        // Tenant-scoped resources (resolve the active tenant first).
        Route::middleware('tenant.auth')->group(function () {
            Route::get('roles', [RoleController::class, 'index'])->name('roles.index');

            Route::get('tenants/{tenant}', [TenantController::class, 'show'])->name('tenants.show');
            Route::put('tenants/{tenant}', [TenantController::class, 'update'])->name('tenants.update');
            Route::delete('tenants/{tenant}', [TenantController::class, 'destroy'])->name('tenants.destroy');

            Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

            Route::get('users', [UserController::class, 'index'])->name('users.index');
            Route::post('users', [UserController::class, 'store'])->middleware('permission:create users')->name('users.store');
            Route::put('users/{user}', [UserController::class, 'updateRole'])->middleware('permission:update users')->name('users.update-role');
            Route::delete('users/{user}', [UserController::class, 'destroy'])->middleware('permission:delete users')->name('users.destroy');

            Route::get('invitations', [InvitationController::class, 'index'])->name('invitations.index');
            Route::post('invitations', [InvitationController::class, 'store'])->middleware('permission:invite users')->name('invitations.store');

            Route::get('mdm-entities', [MdmEntityController::class, 'index'])->name('mdm-entities.index');
            Route::post('mdm-entities', [MdmEntityController::class, 'store'])->middleware('permission:create mdm entities')->name('mdm-entities.store');
            Route::get('mdm-entities/{mdmEntity}', [MdmEntityController::class, 'show'])->name('mdm-entities.show');
            Route::put('mdm-entities/{mdmEntity}', [MdmEntityController::class, 'update'])->middleware('permission:update mdm entities')->name('mdm-entities.update');
            Route::delete('mdm-entities/{mdmEntity}', [MdmEntityController::class, 'destroy'])->middleware('permission:delete mdm entities')->name('mdm-entities.destroy');
            Route::post('mdm-entities/{mdmEntity}/normalize', [MdmEntityController::class, 'normalize'])->middleware('permission:normalize mdm entities')->name('mdm-entities.normalize');
            Route::get('mdm-entities/{mdmEntity}/relations', [MdmEntityController::class, 'relations'])->name('mdm-entities.relations');

            Route::get('integrations', [IntegrationController::class, 'index'])->name('integrations.index');
            Route::post('integrations', [IntegrationController::class, 'store'])->middleware('permission:manage integrations')->name('integrations.store');
            Route::get('integrations/{integration}', [IntegrationController::class, 'show'])->name('integrations.show');
            Route::put('integrations/{integration}', [IntegrationController::class, 'update'])->middleware('permission:manage integrations')->name('integrations.update');
            Route::delete('integrations/{integration}', [IntegrationController::class, 'destroy'])->middleware('permission:manage integrations')->name('integrations.destroy');
            Route::post('integrations/{integration}/sync', [IntegrationController::class, 'sync'])->middleware('permission:run sync')->name('integrations.sync');

            Route::get('sync-jobs', [SyncJobController::class, 'index'])->name('sync-jobs.index');
            Route::get('sync-jobs/{syncJob}', [SyncJobController::class, 'show'])->name('sync-jobs.show');

            Route::get('audit-logs', [AuditLogController::class, 'index'])->middleware('permission:view audit logs')->name('audit-logs.index');
        });
    });
});
