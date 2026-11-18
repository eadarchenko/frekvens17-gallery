<?php

use App\Http\Controllers\AccountController as Account;
use App\Http\Controllers\ShopController as Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;

Route::get('/verify-email/{id}/{hash}', [Account::class, 'verify'])->middleware(['auth', 'signed', 'throttle:6,1'])->name('verification.verify');
Route::prefix('api')->group(function () {
    Route::post('/demo/role', function (Request $r) {
        abort_unless(config('shop.hosted_demo'), 404);
        $role = $r->validate(['role' => 'required|in:artist,collector,guest'])['role'];
        \Illuminate\Support\Facades\Auth::logout();
        if ($role !== 'guest') {
            $user = \App\Models\User::where('email', $role === 'artist' ? 'artist@demo.test' : 'collector@demo.test')->firstOrFail();
            \Illuminate\Support\Facades\Auth::login($user);
        }
        $r->session()->regenerate();
        return ['ok' => true];
    })->middleware('throttle:30,1');
    Route::get('/session', [Shop::class, 'session']);
    Route::get('/config', [Shop::class, 'config']);
    Route::get('/products', [Shop::class, 'products']);
    Route::post('/forgot-password', [Account::class, 'forgot'])->middleware('throttle:5,1');
    Route::post('/reset-password', [Account::class, 'reset'])->middleware('throttle:5,1');
    Route::post('/register', [Shop::class, 'register'])->middleware('throttle:5,1');
    Route::post('/login', [Shop::class, 'login'])->middleware('throttle:5,1');
    Route::post('/logout', [Shop::class, 'logout']);
    Route::post('/checkout', [Shop::class, 'checkout'])->middleware('throttle:10,1');
    Route::middleware('auth')->group(function () {
        Route::post('/email/verification-notification', [Account::class, 'resend'])->middleware('throttle:3,1');
        Route::get('/orders', [Shop::class, 'orders']);
        Route::get('/pending-checkout', fn (Request $r) => ['items' => config('shop.demo') ? ($r->user()->demo_cart ?? []) : []]);
        Route::patch('/profile', [Shop::class, 'profile']);
        Route::middleware('can:manage-shop')->prefix('admin')->group(function () {
            Route::get('/products', [Shop::class, 'adminProducts']);
            Route::post('/products', [Shop::class, 'saveProduct']);
            Route::put('/products/{product}', [Shop::class, 'saveProduct']);
            Route::delete('/products/{product}', [Shop::class, 'deleteProduct']);
            Route::post('/images', [Shop::class, 'upload']);
            Route::post('/email/verification-notification', [Account::class, 'resend'])->middleware('throttle:3,1');
            Route::get('/orders', [Shop::class, 'adminOrders']);
            Route::patch('/orders/{order}', [Shop::class, 'status']);
        });
    });
    Route::fallback(fn () => response()->json(['message' => 'Endpoint not found.'], 404));
});
// Only regenerated web copies are served. No original-image endpoint exists.
Route::get('/media/products/{file}', function (string $file) {
    $path = Storage::disk('web_images')->path($file);
    abort_unless(is_file($path), 404);

    return response()->file($path, ['Content-Type' => 'image/webp', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'public, max-age=3600']);
})->where('file', '[a-f0-9-]+\.webp');
// Legacy public uploads must not be served by this version.
Route::get('/storage/{path?}', fn () => abort(404))->where('path', '.*');
Route::get('/media/{path?}', fn () => abort(404))->where('path', '.*');

Route::get('/assets/{path}', fn () => abort(404))->where('path', '.*');

Route::get('/{path?}', function () {
    $file = public_path('index.html');

    return file_exists($file) ? response()->file($file) : response('Frontend not built. Follow INICIO.md.', 503);
})->where('path', '^(?!api(?:/|$)|storage(?:/|$)).*');
