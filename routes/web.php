<?php

use App\Http\Controllers\AppearanceController;
use App\Http\Controllers\AppearanceSlotController;
use App\Http\Controllers\FavoriteController;
use App\Http\Controllers\LineLoginController;
use App\Http\Controllers\LineWebhookController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\TruckController;
use Illuminate\Support\Facades\Route;

Route::get('/', [AppearanceController::class, 'index'])->name('appearances.index');

Route::get('/trucks', [TruckController::class, 'index'])->name('trucks.index');
Route::get('/trucks/create', [TruckController::class, 'create'])->name('trucks.create');
Route::post('/trucks', [TruckController::class, 'store'])->name('trucks.store')->middleware('throttle:5,1');
Route::get('/trucks/{truck}', [TruckController::class, 'show'])->name('trucks.show');
Route::post('/trucks/{truck}/appearances', [AppearanceSlotController::class, 'store'])
    ->name('trucks.appearances.store')
    ->middleware('throttle:10,1');
Route::post('/trucks/{truck}/reviews', [ReviewController::class, 'store'])->name('trucks.reviews.store')->middleware('throttle:10,1');
Route::post('/trucks/{truck}/like', [TruckController::class, 'like'])->name('trucks.like')->middleware('throttle:30,1');
Route::view('/thanks', 'trucks.thanks')->name('trucks.thanks');

Route::view('/about', 'about')->name('about');
Route::get('/sitemap.xml', [TruckController::class, 'sitemap'])->name('sitemap');

// LINE連携（お気に入りトラックの新着出店情報通知）
Route::get('/line/login', [LineLoginController::class, 'redirect'])->name('line.login');
Route::get('/line/callback', [LineLoginController::class, 'callback'])->name('line.callback');
Route::post('/trucks/{truck}/favorite', [FavoriteController::class, 'toggle'])
    ->name('trucks.favorite.toggle')
    ->middleware('throttle:10,1');
Route::post('/line/webhook', [LineWebhookController::class, 'handle'])->name('line.webhook');
