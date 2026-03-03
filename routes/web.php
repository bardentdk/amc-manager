<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\AppointmentController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\DocumentController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Ici se trouve la définition des routes de NEXA.
|
*/

// --- GUEST ROUTES ---
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);
});

// --- PROTECTED ROUTES ---
Route::middleware(['auth'])->group(function () {
    // Gestion des Utilisateurs (Admin)
    Route::resource('users', UserController::class)->only(['index', 'store', 'destroy']);
    // Déconnexion
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

    // Dashboard (Redirection intelligente selon le rôle plus tard)
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // Nous ajouterons ici les groupes de routes pour :
    // - Clients
    Route::resource('clients', ClientController::class);
    // - Dossiers
    Route::resource('dossiers', \App\Http\Controllers\DossierController::class);
    // - Règlements
    Route::resource('payments', PaymentController::class)->only(['store', 'update', 'destroy']);
    // - Rendez-vous
    Route::resource('appointments', AppointmentController::class)->except(['create', 'edit', 'show']);
    // - Comptes Rendus
    Route::resource('reports', ReportController::class)->only(['store', 'update', 'destroy']);
    Route::get('/reports/{report}/download', [ReportController::class, 'download'])->name('reports.download');
    Route::post('/reports/{report}/email', [ReportController::class, 'sendEmail'])->name('reports.email');

    // Gestion documentaire : 
    Route::post('/documents', [DocumentController::class, 'store'])->name('documents.store');
    Route::get('/documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('/documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::delete('/documents/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');
    
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');

    Route::post('/reports/generate-ai', [ReportController::class, 'generateAi'])->name('reports.generateAi');
});