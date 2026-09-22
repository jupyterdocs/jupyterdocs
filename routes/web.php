<?php

use App\Http\Controllers\Admin\ContentController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ModerationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\DownloadController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ResourceInteractionController;
use App\Http\Controllers\SitemapController;
use Illuminate\Support\Facades\Route;

// Marketing landing page for guests; signed-in users go straight to their dashboard.
Route::get('/', function () {
    if (! auth()->check()) {
        return view('welcome');
    }

    return auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
        : redirect()->route('resources.index');
})->name('home');

Route::get('/browse', [ResourceController::class, 'index'])->name('resources.index');

Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');

Route::get('/terms', [LegalController::class, 'terms'])->name('terms');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('privacy');
Route::get('/copyright', [LegalController::class, 'copyright'])->name('copyright');
Route::get('/contact', [LegalController::class, 'contact'])->name('contact');

// Admins land on the moderation queue; everyone else lands on the archive.
Route::get('/dashboard', function () {
    return auth()->user()->isAdmin()
        ? redirect()->route('admin.dashboard')
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

    Route::get('/saved', [ResourceInteractionController::class, 'saved'])->name('resources.saved');
    Route::post('/resources/{resource}/save', [ResourceInteractionController::class, 'toggleSave'])->name('resources.save');
    Route::post('/resources/{resource}/vote', [ResourceInteractionController::class, 'vote'])->name('resources.vote');
    Route::post('/resources/{resource}/report', [ResourceInteractionController::class, 'report'])->name('resources.report');

    Route::middleware('admin')->prefix('admin')->name('admin.')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}', [UserController::class, 'show'])->name('users.show');
        Route::patch('/users/{user}/role', [UserController::class, 'updateRole'])->name('users.role');
        Route::patch('/users/{user}/toggle-active', [UserController::class, 'toggleActive'])->name('users.toggle-active');
        Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

        Route::get('/content', [ContentController::class, 'index'])->name('content.index');

        Route::delete('/resources/{resource}', [ResourceController::class, 'destroy'])->name('resources.destroy');

        Route::get('/moderation', [ModerationController::class, 'index'])->name('moderation.index');
        Route::post('/moderation/{resource}/approve', [ModerationController::class, 'approve'])->name('moderation.approve');
        Route::post('/moderation/{resource}/reject', [ModerationController::class, 'reject'])->name('moderation.reject');
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::patch('/reports/{report}', [ReportController::class, 'update'])->name('reports.update');
    });
});

require __DIR__.'/auth.php';
