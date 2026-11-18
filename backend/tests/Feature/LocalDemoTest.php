<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use App\Models\Variant;
use App\Services\LocalDemo;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class LocalDemoTest extends TestCase
{
    use RefreshDatabase;

    private function demo(): void
    {
        config(['shop.demo' => true, 'shop.mode' => 'test', 'shop.currency' => 'EUR', 'shop.shipping_cents' => 0, 'shop.tax_basis_points' => 0]);
        $this->seed(DatabaseSeeder::class);
        app(LocalDemo::class)->create(Str::random(32), Str::random(32));
    }

    public function test_demo_has_roles_fictional_prices_completed_test_order_and_unfinished_cart(): void
    {
        $this->demo();
        $this->assertSame(2, User::count());
        $admin = User::where('email', 'artist@demo.test')->first();
        $customer = User::where('email', 'collector@demo.test')->first();
        $this->assertTrue($admin->is_admin);
        $this->assertFalse($customer->is_admin);
        $this->assertNotNull($customer->email_verified_at);
        $this->assertGreaterThanOrEqual(30000, Variant::min('price_cents'));
        $this->assertSame(1, Order::count());
        $this->assertSame('shipped', Order::first()->status);
        $this->assertSame('not_collected', Order::first()->payment_status);
        $this->getJson('/api/pending-checkout')->assertUnauthorized();
        $this->actingAs($customer)->getJson('/api/pending-checkout')->assertOk()->assertJsonCount(1, 'items');
        $this->getJson('/api/session')->assertJsonMissingPath('user.demo_cart')->assertJsonMissingPath('user.password');
        $this->actingAs($admin)->getJson('/api/pending-checkout')->assertExactJson(['items' => []]);
        $this->actingAs($admin)->getJson('/api/admin/products')->assertOk();
    }

    public function test_pending_checkout_can_finish_once_with_server_prices_and_is_then_cleared(): void
    {
        $this->demo();
        $customer = User::where('email', 'collector@demo.test')->first();
        $cart = $customer->demo_cart;
        $payload = ['name' => 'Demo Collector', 'email' => 'collector@demo.test', 'address' => ['line1' => 'Demo street only', 'city' => 'Demo City', 'postcode' => '00000', 'country' => 'ES'], 'items' => $cart, 'idempotency_key' => (string) Str::uuid(), 'test_acknowledged' => true];
        $this->actingAs($customer)->postJson('/api/checkout', $payload)->assertCreated()->assertJsonPath('payment_status', 'not_collected');
        $this->getJson('/api/pending-checkout')->assertExactJson(['items' => []]);
        $this->postJson('/api/checkout', $payload)->assertOk();
        $this->assertSame(2, Order::count());
        $this->assertNull($customer->fresh()->demo_cart);
    }

    public function test_demo_cannot_modify_normal_database_and_will_not_overwrite_existing_demo(): void
    {
        config(['shop.demo' => false]);
        try {
            app(LocalDemo::class)->create(Str::random(32), Str::random(32));
            $this->fail('Normal mode must be rejected');
        } catch (HttpException $e) {
            $this->assertSame(403, $e->getStatusCode());
        }
        $this->assertSame(0, User::count());
        $this->demo();
        $before = User::first()->password;
        try {
            app(LocalDemo::class)->create(Str::random(32), Str::random(32));
            $this->fail('Existing demo must be preserved');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('Nothing was overwritten', $e->getMessage());
        }
        $this->assertSame($before, User::first()->password);
        $this->assertSame(1, Order::count());
    }
}
