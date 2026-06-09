<?php

declare(strict_types=1);

use App\Core\Auth\Http\Controllers\RoleController;
use App\Core\Auth\Http\Controllers\TenantUserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| RBAC — Roles, Permissions, TenantUsers, Store Access
|--------------------------------------------------------------------------
*/

// ── Roles ─────────────────────────────────────────────────────────────────
Route::get('roles',                              [RoleController::class, 'index'])->name('roles.index');
Route::post('roles',                             [RoleController::class, 'store'])->name('roles.store');
Route::get('roles/{role}',                       [RoleController::class, 'show'])->name('roles.show');
Route::put('roles/{role}',                       [RoleController::class, 'update'])->name('roles.update');
Route::delete('roles/{role}',                    [RoleController::class, 'destroy'])->name('roles.destroy');
Route::post('roles/{role}/sync-permissions',     [RoleController::class, 'syncPermissions'])->name('roles.sync-permissions');

// ── Permissions (catálogo de todas as permissões disponíveis) ─────────────
Route::get('permissions',                        [RoleController::class, 'permissions'])->name('permissions.index');

// ── TenantUsers (membership) ──────────────────────────────────────────────
Route::get('users',                              [TenantUserController::class, 'index'])->name('users.index');
Route::post('users',                             [TenantUserController::class, 'createUser'])->name('users.create');
Route::get('users/{tenantUser}',                 [TenantUserController::class, 'show'])->name('users.show');
Route::post('users/assign',                      [TenantUserController::class, 'assign'])->name('users.assign');
Route::put('users/{tenantUser}/role',            [TenantUserController::class, 'changeRole'])->name('users.change-role');
Route::delete('users/{tenantUser}/revoke',       [TenantUserController::class, 'revoke'])->name('users.revoke');
Route::get('users/lookup/by-email',              [TenantUserController::class, 'lookupByEmail'])->name('users.lookup-by-email');

// ── Store access (allowlist por TenantUser) ───────────────────────────────
Route::post('users/{tenantUser}/stores',         [TenantUserController::class, 'grantStoreAccess'])->name('users.stores.grant');
Route::delete('users/{tenantUser}/stores/{storeId}', [TenantUserController::class, 'revokeStoreAccess'])->name('users.stores.revoke');
