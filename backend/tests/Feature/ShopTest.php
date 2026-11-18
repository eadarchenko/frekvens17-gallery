<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

class ShopTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['shop.mode' => 'test', 'shop.currency' => 'EUR', 'shop.shipping_cents' => 700, 'shop.tax_basis_points' => 2100]);
    }

    private function product(int $stock = 2, ?int $price = 10000): Variant
    {
        $p = Product::create(['title' => 'Test painting', 'size' => 'Test dimensions', 'description' => 'A test poem', 'introduction' => 'Test only', 'photo' => 'assets/test.webp']);

        return $p->variants()->create(['name' => 'Original', 'price_cents' => $price, 'stock' => $stock]);
    }

    private function payload(Variant $v, array $over = []): array
    {
        return array_replace(['name' => 'Test Collector', 'email' => 'collector@example.test', 'address' => ['line1' => 'Test street', 'line2' => '', 'city' => 'Test City', 'postcode' => '00000', 'country' => 'ES'], 'items' => [['variant_id' => $v->id, 'quantity' => 1]], 'idempotency_key' => (string) Str::uuid(), 'test_acknowledged' => true], $over);
    }

    private function admin(): User
    {
        $u = User::factory()->create();
        $u->is_admin = true;
        $u->save();

        return $u;
    }

    public function test_catalogue_seed_preserves_all_eight_works_and_does_not_invent_prices_or_stock(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('products', 8);
        $this->assertDatabaseCount('variants', 24);
        $this->assertSame(0, Variant::whereNotNull('price_cents')->count());
        $this->assertSame(0, (int) Variant::sum('stock'));
        $this->getJson('/api/products')->assertOk()->assertJsonCount(8);
    }

    public function test_public_registration_never_grants_admin_and_password_is_hashed(): void
    {
        $r = $this->postJson('/api/register', ['name' => 'Test', 'email' => 'test@example.test', 'password' => 'Test-only-password123', 'password_confirmation' => 'Test-only-password123', 'is_admin' => true]);
        $r->assertCreated()->assertJsonPath('user.is_admin', false)->assertJsonMissingPath('user.password');
        $this->assertTrue(Hash::check('Test-only-password123', User::first()->password));
        $this->getJson('/api/admin/orders')->assertForbidden();
    }

    public function test_short_passwords_and_duplicate_emails_are_rejected(): void
    {
        $this->postJson('/api/register', ['name' => 'Test', 'email' => 'test@example.test', 'password' => 'short', 'password_confirmation' => 'short'])->assertUnprocessable();
        User::factory()->create(['email' => 'same@example.test']);
        $this->postJson('/api/register', ['name' => 'Test', 'email' => 'SAME@example.test', 'password' => 'Test-only-password123', 'password_confirmation' => 'Test-only-password123'])->assertUnprocessable();
    }

    public function test_login_logout_profile_and_server_side_authorization(): void
    {
        $u = User::factory()->create(['email' => 'login@example.test', 'password' => 'Test-only-password123']);
        $this->getJson('/api/orders')->assertUnauthorized();
        $this->getJson('/api/admin/products')->assertUnauthorized();
        $this->postJson('/api/login', ['email' => $u->email, 'password' => 'wrong'])->assertUnprocessable();
        $this->postJson('/api/login', ['email' => $u->email, 'password' => 'Test-only-password123'])->assertOk();
        $this->patchJson('/api/profile', ['name' => 'Changed', 'is_admin' => true])->assertOk()->assertJsonPath('user.name', 'Changed');
        $this->getJson('/api/admin/products')->assertForbidden();
        $this->postJson('/api/logout')->assertOk();
        $this->getJson('/api/orders')->assertUnauthorized();
    }

    public function test_checkout_disabled_by_default_and_cannot_be_enabled_by_request(): void
    {
        config(['shop.mode' => 'disabled']);
        $v = $this->product();
        $this->postJson('/api/checkout', $this->payload($v, ['mode' => 'test']))->assertStatus(503);
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_guest_checkout_recalculates_every_amount_and_does_not_mark_paid(): void
    {
        $v = $this->product();
        $r = $this->postJson('/api/checkout', $this->payload($v, ['total_cents' => 1, 'price_cents' => 1, 'user_id' => 1, 'payment_status' => 'paid', 'is_test' => false]));
        $r->assertCreated()->assertJsonPath('subtotal_cents', 10000)->assertJsonPath('shipping_cents', 700)->assertJsonPath('tax_cents', 2100)->assertJsonPath('total_cents', 12800)->assertJsonPath('user_id', null)->assertJsonPath('payment_status', 'not_collected')->assertJsonPath('is_test', true)->assertJsonMissingPath('owner_session');
        $this->assertEquals(1, $v->fresh()->stock);
    }

    public function test_idempotent_checkout_cannot_duplicate_orders_or_decrement_twice(): void
    {
        $v = $this->product();
        $d = $this->payload($v);
        $this->postJson('/api/checkout', $d)->assertCreated();
        $this->postJson('/api/checkout', $d)->assertOk();
        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(1, $v->fresh()->stock);
    }

    public function test_invalid_quantities_unknown_formats_null_prices_and_inactive_works_are_rejected(): void
    {
        $v = $this->product();
        foreach ([0, -1, 26] as $q) {
            $this->postJson('/api/checkout', $this->payload($v, ['items' => [['variant_id' => $v->id, 'quantity' => $q]]]))->assertUnprocessable();
        }
        $this->postJson('/api/checkout', $this->payload($v, ['items' => [['variant_id' => 999, 'quantity' => 1]]]))->assertUnprocessable();
        $v->update(['price_cents' => null]);
        $this->postJson('/api/checkout', $this->payload($v))->assertUnprocessable();
        $v->update(['price_cents' => 100]);
        $v->product->update(['active' => false]);
        $this->postJson('/api/checkout', $this->payload($v))->assertUnprocessable();
        $this->assertDatabaseCount('orders', 0);
    }

    public function test_final_original_cannot_be_oversold_and_transaction_rolls_back(): void
    {
        $v = $this->product(1);
        $this->postJson('/api/checkout', $this->payload($v))->assertCreated();
        $this->postJson('/api/checkout', $this->payload($v))->assertUnprocessable();
        $this->assertEquals(0, $v->fresh()->stock);
        $this->assertDatabaseCount('orders', 1);
        $v2 = $this->product(5);
        $this->postJson('/api/checkout', $this->payload($v2, ['items' => [['variant_id' => $v2->id, 'quantity' => 1], ['variant_id' => $v->id, 'quantity' => 1]]]))->assertUnprocessable();
        $this->assertEquals(5, $v2->fresh()->stock);
        $this->assertDatabaseCount('orders', 1);
    }

    public function test_customers_only_see_their_own_orders_and_guest_history_is_not_claimed_by_email(): void
    {
        $a = User::factory()->create();
        $b = User::factory()->create();
        $v = $this->product(5);
        $this->postJson('/api/checkout', $this->payload($v, ['email' => $a->email]))->assertCreated();
        $this->actingAs($a)->postJson('/api/checkout', $this->payload($v))->assertCreated();
        $this->getJson('/api/orders')->assertOk()->assertJsonCount(1);
        $this->actingAs($b)->getJson('/api/orders')->assertOk()->assertJsonCount(0);
    }

    public function test_admin_can_create_edit_hide_and_delete_but_order_snapshot_remains(): void
    {
        $a = $this->admin();
        $v = $this->product();
        $this->postJson('/api/checkout', $this->payload($v))->assertCreated();
        $this->actingAs($a);
        $p = $v->product->load('variants')->toArray();
        $p['title'] = 'Edited';
        $p['variants'][0]['price_cents'] = 25000;
        $this->putJson('/api/admin/products/'.$p['id'], $p)->assertOk()->assertJsonPath('title', 'Edited');
        $this->assertEquals(10000, Order::first()->items()->first()->price_cents);
        $this->assertEquals('Test painting', Order::first()->items()->first()->title);
        unset($p['id']);
        foreach ($p['variants'] as &$variant) {
            unset($variant['id']);
        }unset($variant);
        $this->postJson('/api/admin/products', $p)->assertCreated();
        $this->deleteJson('/api/admin/products/'.$v->product_id)->assertOk();
        $this->getJson('/api/products')->assertJsonCount(1);
        $this->assertDatabaseCount('orders', 1);
        $this->getJson('/api/admin/orders')->assertOk()->assertJsonCount(1);
    }

    public function test_variant_ids_cannot_be_moved_between_products(): void
    {
        $v = $this->product();
        $other = $this->product();
        $p = $v->product->load('variants')->toArray();
        $p['variants'][0]['id'] = $other->id;
        $this->actingAs($this->admin())->putJson('/api/admin/products/'.$p['id'], $p)->assertNotFound();
    }

    public function test_order_cancel_restores_stock_once_and_terminal_statuses_are_protected(): void
    {
        $v = $this->product();
        $this->postJson('/api/checkout', $this->payload($v))->assertCreated();
        $o = Order::first();
        $this->actingAs($this->admin());
        $this->patchJson('/api/admin/orders/'.$o->id, ['status' => 'cancelled'])->assertOk();
        $this->assertEquals(2, $v->fresh()->stock);
        $this->patchJson('/api/admin/orders/'.$o->id, ['status' => 'cancelled'])->assertOk();
        $this->assertEquals(2, $v->fresh()->stock);
        $this->patchJson('/api/admin/orders/'.$o->id, ['status' => 'preparing'])->assertUnprocessable();
        $this->patchJson('/api/admin/orders/'.$o->id, ['status' => 'paid'])->assertUnprocessable();
    }

    public function test_uploads_are_authorised_validated_and_not_executable(): void
    {
        Storage::fake('public');
        $this->postJson('/api/admin/images', ['image' => UploadedFile::fake()->image('work.png')])->assertUnauthorized();
        $this->actingAs($this->admin())->postJson('/api/admin/images', ['image' => UploadedFile::fake()->image('work.png')])->assertOk()->assertJsonStructure(['path']);
        $this->postJson('/api/admin/images', ['image' => UploadedFile::fake()->create('payload.php', 1, 'application/x-php')])->assertUnprocessable();
        $this->postJson('/api/admin/images', ['image' => UploadedFile::fake()->create('art.svg', 1, 'image/svg+xml')])->assertUnprocessable();
    }

    public function test_product_validation_rejects_remote_or_script_image_paths(): void
    {
        $v = $this->product();
        $p = $v->product->load('variants')->toArray();
        $p['photo'] = 'https://example.test/tracker.png';
        $this->actingAs($this->admin())->putJson('/api/admin/products/'.$p['id'], $p)->assertUnprocessable();
    }

    public function test_no_payment_or_live_switch_endpoint_exists(): void
    {
        $this->postJson('/api/payments', ['amount' => 1])->assertStatus(405);
        $this->getJson('/api/config')->assertJsonPath('payments_connected', false);
    }
}
