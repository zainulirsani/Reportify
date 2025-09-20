<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\AIUtilityController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\WeeklyReportController;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;
Route::redirect('/', '/dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

Route::resource('tasks', TaskController::class)->middleware(['auth']);

Route::get('/systems', [SystemController::class, 'index'])
    ->middleware(['auth'])
    ->name('systems');
Route::post('/systems', [SystemController::class, 'store'])
    ->middleware(['auth'])
    ->name('systems.store');
Route::put('/systems/{system}', [SystemController::class, 'update'])
    ->middleware(['auth'])
    ->name('systems.update');
Route::delete('/systems/{system}', [SystemController::class, 'destroy'])
    ->middleware(['auth'])
    ->name('systems.destroy');

Route::get('/systems/{system}/sync/preview', [SystemController::class, 'syncPreview'])->name('systems.sync.preview');
Route::post('/systems/sync/process', [SystemController::class, 'processSync'])->name('systems.sync.process');

Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
Route::get('/reports', [ReportController::class, 'index'])
    ->middleware(['auth'])
    ->name('reports');
Route::get('/reports/{report}', [ReportController::class, 'show'])
    ->middleware(['auth'])
    ->name('reports.show');
Route::put('/reports/{report}', [ReportController::class, 'update'])
    ->middleware(['auth'])
    ->name('reports.update');

Route::post('/reports/manual', [ReportController::class, 'storeManual'])->name('reports.store.manual');

Route::post('/ai/rewrite-description', [AIUtilityController::class, 'rewriteDescription'])->name('ai.rewrite');


Route::get('/weekly-report', [WeeklyReportController::class, 'index'])->name('reports.weekly');
Route::post('/weekly-report/generate', [WeeklyReportController::class, 'generate'])->name('reports.weekly.generate');
require __DIR__ . '/auth.php';
