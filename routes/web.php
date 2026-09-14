<?php

use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use Illuminate\Support\Facades\Route;

// Marketing landing page for guests; signed-in users go straight to their dashboard.
Route::get('/', function () {
    if (! auth()->check()) {
        return view('welcome');
    }

    return auth()->user()->isAdmin()
        ? redirect()->route('admin.moderation.index')
        : redirect()->route('resources.index');
})->name('home');

Route::get('/browse', [ResourceController::class, 'index'])->name('resources.index');

// Admins land on the moderation queue; everyone else lands on the archive.
Route::get('/dashboard', function () {
    return auth()->user()->isAdmin()
        ? redirect()->route('admin.moderation.index')
        : redirect()->route('resources.index');
})->middleware(['auth', 'verified'])->name('dashboard');

// Open to everyone, including guests, so uploading has as little friction as possible.
Route::get('/resources/create', [ResourceController::class, 'create'])->name('resources.create');
Route::post('/resources', [ResourceController::class, 'store'])->name('resources.store');
Route::get('/resources/{resource}', [ResourceController::class, 'show'])->name('resources.show');
Route::get('/resources/{resource}/read', [ResourceController::class, 'read'])->name('resources.read');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/my-uploads', [ResourceController::class, 'mine'])->name('resources.mine');
    Route::post('/resources/{resource}/download', [DownloadController::class, 'store'])->name('resources.download');
    Route::get('/resources/{resource}/preview', [ResourceController::class, 'preview'])->name('resources.preview');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
        Route::post('/moderation/{resource}/approve', [ModerationController::class, 'approve'])->name('moderation.approve');
        Route::post('/moderation/{resource}/reject', [ModerationController::class, 'reject'])->name('moderation.reject');
    });
});

require __DIR__.'/auth.php';
