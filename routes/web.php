<?php

use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\InvoicePdfController;
use App\Http\Controllers\PayController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\StripeWebhookController;
use App\Livewire\Auth\ForgotPassword;
use App\Livewire\Auth\Login;
use App\Livewire\Auth\ResetPassword;
use App\Livewire\Clients\Form as ClientForm;
use App\Livewire\Clients\Index as ClientIndex;
use App\Livewire\Clients\Show as ClientShow;
use App\Livewire\Dashboard;
use App\Livewire\Invoices\Form as InvoiceForm;
use App\Livewire\Invoices\Index as InvoiceIndex;
use App\Livewire\Invoices\Show as InvoiceShow;
use App\Livewire\Reports\Index as ReportIndex;
use App\Livewire\Services\Form as ServiceForm;
use App\Livewire\Services\Index as ServiceIndex;
use App\Livewire\Settings\Index as SettingsIndex;
use Illuminate\Support\Facades\Route;

Route::get('/pay/{token}', [PayController::class, 'show'])->middleware('throttle:pay')->name('pay.show');
Route::post('/pay/{token}/bank-reported', [PayController::class, 'reportBankPayment'])->middleware('throttle:pay')->name('pay.bank');
Route::get('/pay/{token}/complete', [PayController::class, 'complete'])->middleware('throttle:pay')->name('pay.complete');
Route::get('/pay/{token}/pdf', [PayController::class, 'pdf'])->middleware('throttle:pay')->name('pay.pdf');
Route::post('/stripe/webhook', StripeWebhookController::class)->name('stripe.webhook');

Route::middleware('guest')->group(function () {
    Route::get('/login', Login::class)->name('login');
    Route::get('/forgot-password', ForgotPassword::class)->name('password.request');
    Route::get('/reset-password/{token}', ResetPassword::class)->name('password.reset');
});

// Portal/invite routes intentionally omitted in v1 (files kept for a future portal).

Route::post('/logout', LogoutController::class)->middleware('auth')->name('logout');

Route::middleware(['auth', 'staff'])->group(function () {
    Route::get('/', Dashboard::class)->name('dashboard');
    Route::get('/invoices', InvoiceIndex::class)->name('invoices.index');
    Route::get('/invoices/create', InvoiceForm::class)->name('invoices.create');
    Route::get('/invoices/{invoice}', InvoiceShow::class)->name('invoices.show');
    Route::get('/invoices/{invoice}/edit', InvoiceForm::class)->name('invoices.edit');
    Route::get('/invoices/{invoice}/pdf', [InvoicePdfController::class, 'staff'])->name('invoices.pdf');

    Route::get('/clients', ClientIndex::class)->name('clients.index');
    Route::get('/clients/create', ClientForm::class)->name('clients.create');
    Route::get('/clients/{client}', ClientShow::class)->name('clients.show');
    Route::get('/clients/{client}/edit', ClientForm::class)->name('clients.edit');

    Route::get('/services', ServiceIndex::class)->name('services.index');
    Route::get('/services/create', ServiceForm::class)->name('services.create');
    Route::get('/services/{service}/edit', ServiceForm::class)->name('services.edit');

    Route::get('/reports', ReportIndex::class)->name('reports.index');
    Route::get('/reports/export', ReportExportController::class)->name('reports.export');

    Route::get('/settings', SettingsIndex::class)->name('settings.index');
});
