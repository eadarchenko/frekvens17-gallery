<?php

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\ArtworkImages;
use App\Services\LocalDemo;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

Artisan::command('shop:admin {email}', function () {
    $u = User::where('email', strtolower($this->argument('email')))->first();
    if (! $u) {
        $this->error('Register this account first.');

        return 1;
    }
    $u->is_admin = true;
    $u->save();
    $this->info('Artist access enabled for this account.');
})->purpose('Promote an existing local account to artist administrator');

// Local upgrade only: preserve old uploads privately, replace catalogue URLs, then remove public source.
Artisan::command('shop:protect-images', function () {
    $manifest = json_decode(file_get_contents(database_path('seeders/image-paths.json')), true);
    foreach ($manifest as $item) {
        foreach (['photo', 'photo2'] as $field) {
            Product::withTrashed()->where($field, 'assets/'.$item['original'])->update([$field => $item['web']]);
        }
    }
    $disk = Storage::disk('public');
    foreach ($disk->files('products') as $old) {
        if (! in_array(strtolower(pathinfo($old, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg', 'webp'])) {
            $this->error('Unexpected legacy file: stop and review private backup before proceeding.');

            return 1;
        }
        $file = new UploadedFile($disk->path($old), basename($old), null, null, true);
        $web = app(ArtworkImages::class)->store($file);
        DB::transaction(function () use ($old, $web) {
            foreach (['photo', 'photo2'] as $field) {
                Product::withTrashed()->where($field, '/storage/'.$old)->update([$field => $web]);
            }
        });
        $disk->delete($old);
    }
    $this->info('Legacy catalogue paths updated. Upload originals preserved privately; only web copies are served.');
})->purpose('Upgrade local legacy image paths without losing originals');

Artisan::command('shop:demo', function () {
    if (! config('shop.demo') || config('shop.mode') !== 'test') {
        $this->error('Run node scripts/demo.cjs, not the normal shop database.');

        return 1;
    }
    if (User::count() || Order::count()) {
        $this->info('Existing demo kept. Sign in with the passwords you chose before.');

        return 0;
    }
    $this->info('DEMO ONLY. Choose different local passwords; nothing is sent or printed.');
    $passwords = [];
    foreach (['artist@demo.test', 'collector@demo.test'] as $email) {
        do {
            $password = $this->secret('New password for '.$email.' (12+ characters, letter and number)', false);
            $valid = is_string($password) && strlen($password) >= 12 && preg_match('/[a-zA-Z]/', $password) && preg_match('/[0-9]/', $password);
            if (! $valid) {
                $this->error('Use at least 12 characters, a letter and a number.');

                continue;
            }
            $valid = hash_equals($password, (string) $this->secret('Repeat password', false));
            if (! $valid) {
                $this->error('Passwords did not match.');
            }
        } while (! $valid);
        $passwords[] = $password;
    }
    if (hash_equals($passwords[0], $passwords[1])) {
        $this->error('Use a different password for each role. Nothing created.');

        return 1;
    }
    app(LocalDemo::class)->create(...$passwords);
    unset($passwords, $password);
    $this->info('Demo ready: artist@demo.test (admin), collector@demo.test (customer). No real emails or payments.');
})->purpose('Create isolated demonstration accounts with locally chosen hidden passwords');
