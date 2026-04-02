<?php

use App\Http\Controllers\IssueController;
use Illuminate\Support\Facades\Route;
use Laravel\Fortify\Features;

Route::inertia('/', 'welcome', [
    'canRegister' => Features::enabled(Features::registration()),
])->name('home');

Route::get('/issues', [IssueController::class, 'index'])->name('issues.index');
Route::post('/issues', [IssueController::class, 'store'])->name('issues.store');
Route::patch('/issues/{issue}', [IssueController::class, 'update'])->name('issues.update');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
