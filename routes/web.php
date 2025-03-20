<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryRuleController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\CardController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return view('welcome');
    })->name('home');
});

// Guest routes for invitation acceptance
Route::get('/invitations/accept/{token}', [InvitationController::class, 'accept'])->name('invitations.accept');
Route::get('/join/{token}', [InvitationController::class, 'joinViaLink'])->name('invitations.join');

// Protected routes
Route::middleware(['auth'])->group(function () {
    Route::redirect('/dashboard', '/transactions')->name('dashboard');
    
    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // Transaction routes - custom actions first
    Route::get('/transactions/report', [TransactionController::class, 'report'])->name('transactions.report');
    Route::get('/transactions/export', [TransactionController::class, 'exportReport'])->name('transactions.export');
    Route::get('/transactions/suggestions', [TransactionController::class, 'getCategorySuggestions'])->name('transactions.suggestions');
    Route::post('/transactions/reset', [TransactionController::class, 'reset'])->name('transactions.reset');
    
    // Resource routes
    Route::resource('transactions', TransactionController::class)->except(['show'])->where(['transaction' => '[0-9]+']);
    Route::post('/transactions/bulk-update', [TransactionController::class, 'bulkUpdate'])->name('transactions.bulk-update');
    
    // Category routes - custom actions first
    Route::get('/categories/export', [CategoryController::class, 'export'])->name('categories.export');
    Route::post('/categories/import', [CategoryController::class, 'import'])->name('categories.import');
    Route::resource('categories', CategoryController::class);
    Route::resource('category-rules', CategoryRuleController::class);
    Route::resource('invitations', InvitationController::class);
    Route::post('/invitations/generate-link', [InvitationController::class, 'generateLink'])->name('invitations.generate-link');
    Route::resource('cards', CardController::class);
});

require __DIR__.'/auth.php';
