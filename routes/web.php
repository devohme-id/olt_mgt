<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\OltController;
use App\Http\Controllers\Web\OnuController;
use App\Http\Controllers\Web\AlarmController;
use App\Http\Controllers\Web\ReportController;
use App\Http\Controllers\Web\OidExplorerController;
use Illuminate\Support\Facades\Route;

// ── Auth Routes (Guest Only) ──
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
});

// ── Protected Routes ──
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── OLT Management ──
    Route::prefix('olt')->name('olt.')->group(function () {
        Route::get('/', [OltController::class, 'index'])->name('index');
        Route::get('/create', [OltController::class, 'create'])->name('create');
        Route::post('/', [OltController::class, 'store'])->name('store');
        Route::get('/{olt}', [OltController::class, 'show'])->name('show');
        Route::get('/{olt}/edit', [OltController::class, 'edit'])->name('edit');
        Route::put('/{olt}', [OltController::class, 'update'])->name('update');
        Route::delete('/{olt}', [OltController::class, 'destroy'])->name('destroy');

        // OLT Actions
        Route::post('/{olt}/sync', [OltController::class, 'sync'])->name('sync');
        Route::post('/{olt}/sync-onus', [OltController::class, 'syncOnus'])->name('sync-onus');
        Route::post('/{olt}/backup', [OltController::class, 'backup'])->name('backup');
    });

    // ── ONU Management ──
    Route::prefix('onu')->name('onu.')->group(function () {
        Route::get('/', [OnuController::class, 'index'])->name('index');
        Route::get('/{onu}', [OnuController::class, 'show'])->name('show');
        Route::put('/{onu}', [OnuController::class, 'update'])->name('update');
        Route::delete('/{onu}', [OnuController::class, 'destroy'])->name('destroy');

        // ONU Actions
        Route::post('/{onu}/reboot', [OnuController::class, 'reboot'])->name('reboot');
        Route::post('/{onu}/apply-profile', [OnuController::class, 'applyProfile'])->name('apply-profile');
        Route::get('/{onu}/optical', [OnuController::class, 'optical'])->name('optical');
    });

    // ── Alarms ──
    Route::prefix('alarms')->name('alarms.')->group(function () {
        Route::get('/', [AlarmController::class, 'index'])->name('index');
        Route::post('/{alarm}/ack', [AlarmController::class, 'acknowledge'])->name('ack');
        Route::post('/{alarm}/resolve', [AlarmController::class, 'resolve'])->name('resolve');
    });

    // ── Reports ──
    Route::prefix('reports')->name('reports.')->group(function () {
        Route::get('/', [ReportController::class, 'index'])->name('index');
        Route::get('/export-optical', [ReportController::class, 'exportOptical'])->name('export-optical');
        Route::get('/export-alarms', [ReportController::class, 'exportAlarms'])->name('export-alarms');
    });

    // ── Tools ──
    Route::prefix('tools')->name('tools.')->group(function () {
        Route::get('/explorer', [OidExplorerController::class, 'index'])->name('explorer');
        Route::post('/explorer', [OidExplorerController::class, 'explore'])->name('explorer.explore');
    });
});
