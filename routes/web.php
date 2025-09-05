<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SystemController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use SebastianBergmann\CodeCoverage\Report\Html\Dashboard;

// Route::get('/', function () {
//     return view('welcome');
// });

// Route::get('/dashboard', function () {
//     return view('dashboard');
// })->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth'])
    ->name('dashboard');

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


Route::get('/reports', [ReportController::class, 'index'])
    ->middleware(['auth'])
    ->name('reports');
Route::get('/reports/{report}', [ReportController::class, 'show'])
    ->middleware(['auth'])
    ->name('reports.show');
Route::put('/reports/{report}', [ReportController::class, 'update'])
    ->middleware(['auth'])
    ->name('reports.update');
require __DIR__ . '/auth.php';
