<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CategoryRuleController;
use App\Http\Controllers\InvitationController;
use App\Http\Controllers\CardController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\OrganizationController;
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
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    // Organization routes
    Route::get('/organizations/select', [OrganizationController::class, 'select'])->name('organizations.select');
    Route::post('/organizations/switch/{organization}', [OrganizationController::class, 'switch'])->name('organizations.switch');
    Route::get('/organizations/create', [OrganizationController::class, 'create'])->name('organizations.create');
    Route::post('/organizations', [OrganizationController::class, 'store'])->name('organizations.store');
    Route::get('/organizations/{organization}/edit', [OrganizationController::class, 'edit'])->name('organizations.edit');
    Route::put('/organizations/{organization}', [OrganizationController::class, 'update'])->name('organizations.update');
    Route::delete('/organizations/{organization}', [OrganizationController::class, 'destroy'])->name('organizations.destroy');
    
    // Routes that require organization selection
    Route::middleware(['organization'])->group(function () {
        // Invitation routes
        Route::get('/invitations', [InvitationController::class, 'index'])->name('invitations.index');
        Route::get('/invitations/create', [InvitationController::class, 'create'])->name('invitations.create');
        Route::post('/invitations', [InvitationController::class, 'store'])->name('invitations.store');
        Route::delete('/invitations/{invitation}', [InvitationController::class, 'destroy'])->name('invitations.destroy');
        Route::post('/invitations/generate-link', [InvitationController::class, 'generateLink'])->name('invitations.generate-link');
        
        // Organization member routes
        Route::get('/organization/members', [OrganizationMemberController::class, 'index'])->name('organization-members.index');
        Route::patch('/organizations/{organization}/members/{member}/role', [OrganizationMemberController::class, 'updateRole'])->name('organization-members.update-role');
        Route::delete('/organizations/{organization}/members/{member}', [OrganizationMemberController::class, 'destroy'])->name('organization-members.destroy');
        Route::post('/organizations/{organization}/leave', [OrganizationMemberController::class, 'leave'])->name('organization-members.leave');

        // Transaction routes - custom actions first
        Route::get('/transactions/report', [TransactionController::class, 'report'])->name('transactions.report');
        Route::get('/transactions/export', [TransactionController::class, 'exportReport'])->name('transactions.export');
        Route::get('/transactions/export-all', [TransactionController::class, 'exportAll'])->name('transactions.export-all');
        Route::get('/transactions/suggestions', [TransactionController::class, 'getCategorySuggestions'])->name('transactions.suggestions');
        Route::post('/transactions/reset', [TransactionController::class, 'reset'])->name('transactions.reset');
        Route::post('/transactions/import', [TransactionController::class, 'import'])->name('transactions.import');
        
        // Resource routes
        Route::resource('transactions', TransactionController::class)->except(['show'])->where(['transaction' => '[0-9]+']);
        Route::post('/transactions/bulk-update', [TransactionController::class, 'bulkUpdate'])->name('transactions.bulk-update');
        
        // Category routes - custom actions first
        Route::get('/categories/export', [CategoryController::class, 'export'])->name('categories.export');
        Route::post('/categories/import', [CategoryController::class, 'import'])->name('categories.import');
        Route::resource('categories', CategoryController::class);
        Route::resource('category-rules', CategoryRuleController::class);
        Route::resource('cards', CardController::class);
    });

    // Profile routes
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
