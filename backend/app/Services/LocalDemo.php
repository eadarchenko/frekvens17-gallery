<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class LocalDemo
{
    public function create(string $adminPassword, string $customerPassword): void
    {
        abort_unless(config('shop.demo') && config('shop.mode') === 'test' && config('shop.currency') === 'EUR', 403, 'Use the dedicated local demo launcher.');
        if (User::count() || Order::count()) {
            throw new \RuntimeException('Demo database already has accounts/orders. Nothing was overwritten. Use existing passwords to continue.');
        }
        DB::transaction(function () use ($adminPassword, $customerPassword) {
            $catalogue = Product::with('variants')->orderBy('id')->get();
            if ($catalogue->isEmpty()) {
                throw new \RuntimeException('Seed the catalogue first.');
            }
            foreach ($catalogue as $index => $p) {
                $p->update(['title' => 'DEMO · '.$p->title, 'introduction' => 'Fictional demo prices and availability. No real sale. '.$p->introduction]);
                foreach ($p->variants as $n => $v) {
                    $v->update(['price_cents' => 30000 + $index * 10000 + $n * 5000, 'stock' => 5, 'active' => true]);
                }
            }
            $admin = new User;
            $admin->forceFill(['name' => 'DEMO Artist', 'email' => 'artist@demo.test', 'password' => $adminPassword, 'is_admin' => true, 'email_verified_at' => now()])->save();
            $customer = new User;
            $customer->forceFill(['name' => 'DEMO Collector', 'email' => 'collector@demo.test', 'password' => $customerPassword, 'email_verified_at' => now()])->save();
            $first = $catalogue->first()->variants->first()->fresh();
            $o = Order::create(['reference' => (string) Str::uuid(), 'idempotency_key' => (string) Str::uuid(), 'owner_session' => hash('sha256', Str::random(40)), 'user_id' => $customer->id, 'name' => $customer->name, 'email' => $customer->email, 'address' => ['line1' => 'DEMO address - do not deliver', 'city' => 'Demo City', 'postcode' => '00000', 'country' => 'ES'], 'currency' => 'EUR', 'subtotal_cents' => $first->price_cents, 'shipping_cents' => 0, 'tax_cents' => 0, 'total_cents' => $first->price_cents, 'status' => 'shipped', 'payment_status' => 'not_collected', 'is_test' => true]);
            $o->items()->create(['variant_id' => $first->id, 'title' => $catalogue->first()->fresh()->title, 'variant' => $first->name, 'price_cents' => $first->price_cents, 'quantity' => 1]);
            $first->decrement('stock');
            $pending = $catalogue->last()->variants->first();
            $customer->forceFill(['demo_cart' => [['variant_id' => $pending->id, 'quantity' => 1]]])->save();
        });
    }
}
