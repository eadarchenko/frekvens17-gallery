<?php

use App\Models\Product;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;

require dirname(__DIR__, 2).'/vendor/autoload.php';
$app = require dirname(__DIR__, 2).'/bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
if (! app()->environment('testing')) {
    throw new RuntimeException('Browser fixtures require APP_ENV=testing and a separate database.');
}
$u = User::create(['name' => 'Test Artist', 'email' => 'artist@example.test', 'password' => 'Local-test-only123']);
$u->email_verified_at = now();
$u->is_admin = true;
$u->save();
$p = Product::create(['title' => 'TEST WORK - not for sale', 'size' => 'Test item only', 'description' => 'A test product to check the checkout. No real purchase.', 'introduction' => 'Development fixture, not part of the artist catalogue.', 'photo' => 'assets/point of transition.webp', 'photo2' => 'assets/point of transitionn.webp']);
$p->variants()->create(['name' => 'Test format', 'price_cents' => 12500, 'stock' => 3]);
