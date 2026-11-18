<?php
require __DIR__.'/../backend/vendor/autoload.php';
$app = require __DIR__.'/../backend/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
app(App\Services\LocalDemo::class)->create(bin2hex(random_bytes(32)), bin2hex(random_bytes(32)));
