<?php

use App\Livewire\Admin\BotResponsesManager;
use App\Livewire\Admin\CustomersList;
use App\Livewire\Admin\Dashboard;
use App\Livewire\Admin\DatabaseConnection;
use App\Livewire\Admin\Ledger;
use App\Livewire\Admin\LiveChat;
use App\Livewire\Admin\RatesManager;
use App\Livewire\Admin\ServiceManager;
use App\Livewire\Admin\Settings;
use App\Livewire\Admin\TradeDetail;
use App\Livewire\Admin\TradesList;
use App\Livewire\Admin\WhatsAppPanel;
use App\Livewire\Auth\Login;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => view('welcome'));

Route::get('/admin/login', Login::class)->name('admin.login');

Route::middleware('auth')->prefix('admin')->group(function () {
    Route::livewire('/dashboard', Dashboard::class)->name('admin.dashboard');
    Route::livewire('/ledger', Ledger::class)->name('admin.ledger');
    Route::livewire('/trades', TradesList::class)->name('admin.trades');
    Route::livewire('/customers', CustomersList::class)->name('admin.customers');
    Route::livewire('/rates', RatesManager::class)->name('admin.rates');
    Route::livewire('/responses', BotResponsesManager::class)->name('admin.responses');
    Route::livewire('/whatsapp', WhatsAppPanel::class)->name('admin.whatsapp');
    Route::livewire('/services', ServiceManager::class)->name('admin.services');
    Route::livewire('/settings', Settings::class)->name('admin.settings');
    Route::livewire('/chat/{userId}', LiveChat::class)->name('admin.chat');
    Route::get('/trades/{trade}', TradeDetail::class)->name('admin.trades.detail');
    Route::livewire('/connection', DatabaseConnection::class)->name('admin.connection');
    Route::post('/logout', function () {
        auth()->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    })->name('admin.logout');
});
