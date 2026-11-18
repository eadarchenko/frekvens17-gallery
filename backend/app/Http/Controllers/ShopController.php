<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Variant;
use App\Notifications\TestOrderRecorded;
use App\Services\ArtworkImages;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function config()
    {
        return response()->json(['mode' => config('shop.mode') === 'test' ? 'test' : 'disabled', 'currency' => config('shop.currency'),
            'shipping_cents' => (int) config('shop.shipping_cents'), 'tax_basis_points' => (int) config('shop.tax_basis_points'),
            'payments_connected' => false, 'demo' => config('shop.demo')]);
    }

    public function products()
    {
        return Product::where('active', true)->with(['variants' => fn ($q) => $q->where('active', true)])->orderBy('id')->get();
    }

    public function session(Request $r)
    {
        $r->session()->regenerateToken();

        return response()->json(['user' => $r->user()]);
    }

    public function register(Request $r)
    {
        $r->merge(['email' => strtolower((string) $r->input('email'))]);
        $d = $r->validate(['name' => 'required|string|max:120', 'email' => 'required|email:rfc|max:254|unique:users', 'password' => ['required', 'confirmed', Password::min(12)->letters()->numbers()]]);
        $u = User::create(['name' => $d['name'], 'email' => strtolower($d['email']), 'password' => $d['password']]);
        $u->sendEmailVerificationNotification();
        $u->refresh();
        Auth::login($u);
        $r->session()->regenerate();

        return response()->json(['user' => $u], 201);
    }

    public function login(Request $r)
    {
        $d = $r->validate(['email' => 'required|email', 'password' => 'required|string']);
        $d['email'] = strtolower($d['email']);
        if (! Auth::attempt($d)) {
            throw ValidationException::withMessages(['email' => 'Email or password is incorrect.']);
        }
        $r->session()->regenerate();

        return response()->json(['user' => $r->user()]);
    }

    public function logout(Request $r)
    {
        Auth::logout();
        $r->session()->invalidate();
        $r->session()->regenerateToken();

        return response()->json(['ok' => true]);
    }

    public function profile(Request $r)
    {
        $d = $r->validate(['name' => 'required|string|max:120']);
        $r->user()->update($d);

        return ['user' => $r->user()];
    }

    public function orders(Request $r)
    {
        return Order::where('user_id', $r->user()->id)->with('items')->latest()->get();
    }

    public function checkout(Request $r)
    {
        abort_unless(config('shop.mode') === 'test', 503, 'Checkout is disabled. Commercial terms and payments are not configured.');
        abort_unless(preg_match('/^[A-Z]{3}$/', config('shop.currency') ?? ''), 503, 'Set a test currency first.');
        if ($r->user()) {
            abort_unless($r->user()->hasVerifiedEmail(), 403, 'Verify your email before recording a signed-in test order.');
            $r->merge(['email' => $r->user()->email]);
        }
        $d = $r->validate([
            'name' => 'required|string|max:120', 'email' => 'required|email:rfc|max:254', 'address' => 'required|array',
            'address.line1' => 'required|string|max:180', 'address.line2' => 'nullable|string|max:180',
            'address.city' => 'required|string|max:120', 'address.postcode' => 'required|string|max:30', 'address.country' => 'required|string|size:2',
            'items' => 'required|array|min:1|max:40', 'items.*.variant_id' => 'required|integer|distinct',
            'items.*.quantity' => 'required|integer|min:1|max:25', 'idempotency_key' => 'required|uuid', 'test_acknowledged' => 'accepted',
        ]);

        // The browser never supplies an authoritative price, user ID or payment status.
        return DB::transaction(function () use ($r, $d) {
            $identity = $r->session()->get('checkout_owner');
            if (! $identity) {
                $identity = (string) Str::uuid();
                $r->session()->put('checkout_owner', $identity);
            }
            $owner = hash('sha256', $identity);
            $old = Order::where('idempotency_key', $d['idempotency_key'])->first();
            if ($old) {
                abort_unless(hash_equals($old->owner_session, $owner), 409, 'Checkout key already used.');

                return response()->json($old->load('items'));
            }
            $lines = [];
            $subtotal = 0;
            // Sort lock acquisition to reduce deadlocks on multi-item orders.
            $requested = collect($d['items'])->sortBy('variant_id');
            foreach ($requested as $line) {
                $v = Variant::with('product')->lockForUpdate()->find($line['variant_id']);
                if (! $v || ! $v->active || ! $v->product || ! $v->product->active || $v->price_cents === null || $v->stock < $line['quantity']) {
                    throw ValidationException::withMessages(['items' => 'A format is unavailable or its stock changed. Refresh your basket.']);
                }
                $subtotal += $v->price_cents * $line['quantity'];
                $lines[] = ['variant_id' => $v->id, 'title' => $v->product->title, 'variant' => $v->name, 'price_cents' => $v->price_cents, 'quantity' => $line['quantity']];
            }
            $shipping = max(0, (int) config('shop.shipping_cents'));
            $tax = (int) round($subtotal * max(0, (int) config('shop.tax_basis_points')) / 10000);
            $o = Order::create(['reference' => (string) Str::uuid(), 'idempotency_key' => $d['idempotency_key'], 'owner_session' => $owner,
                'user_id' => $r->user()?->id, 'name' => $d['name'], 'email' => strtolower($d['email']), 'address' => $d['address'],
                'currency' => config('shop.currency'), 'subtotal_cents' => $subtotal, 'shipping_cents' => $shipping, 'tax_cents' => $tax, 'total_cents' => $subtotal + $shipping + $tax]);
            foreach ($lines as $l) {
                $changed = Variant::whereKey($l['variant_id'])->where('stock', '>=', $l['quantity'])->decrement('stock', $l['quantity']);
                if (! $changed) {
                    throw ValidationException::withMessages(['items' => 'Stock changed. Please refresh.']);
                }
                $o->items()->create($l);
            }

            if (config('shop.demo') && $r->user()) {
                $r->user()->forceFill(['demo_cart' => null])->save();
            }
            $o->refresh()->load('items');
            DB::afterCommit(function () use ($o) {
                try {
                    Notification::route('mail', $o->email)->notify(new TestOrderRecorded($o));
                } catch (\Throwable $e) {
                    // An already committed order must never become a failed checkout or be charged twice.
                    report($e);
                }
            });

            return response()->json($o, 201);
        }, 3);
    }

    public function adminProducts()
    {
        return Product::with('variants')->orderByDesc('id')->get();
    }

    public function saveProduct(Request $r, ?Product $product = null)
    {
        $d = $r->validate(['title' => 'required|string|max:180', 'size' => 'required|string|max:180', 'description' => 'present|nullable|string|max:10000',
            'introduction' => 'present|nullable|string|max:2000', 'photo' => ['required', 'string', 'max:2048', 'regex:#^(assets/[a-zA-Z0-9 _-]+\.webp|/media/products/[a-f0-9-]+\.webp)$#'],
            'photo2' => ['nullable', 'string', 'max:2048', 'regex:#^(assets/[a-zA-Z0-9 _-]+\.webp|/media/products/[a-f0-9-]+\.webp)$#'], 'active' => 'required|boolean',
            'variants' => 'required|array|min:1|max:20', 'variants.*.id' => 'nullable|integer',
            'variants.*.name' => 'required|string|max:180', 'variants.*.price_cents' => 'nullable|integer|min:0|max:100000000',
            'variants.*.stock' => 'required|integer|min:0|max:10000', 'variants.*.active' => 'required|boolean']);

        return DB::transaction(function () use ($d, $product) {
            $d['description'] = $d['description'] ?? '';
            $d['introduction'] = $d['introduction'] ?? '';
            $vs = $d['variants'];
            unset($d['variants']);
            $p = $product ?? new Product;
            $p->fill($d);
            $p->save();
            $ids = [];
            foreach ($vs as $v) {
                $id = $v['id'] ?? null;
                unset($v['id']);
                $variant = $id ? $p->variants()->findOrFail($id) : $p->variants()->make();
                $variant->fill($v);
                $variant->save();
                $ids[] = $variant->id;
            }
            $p->variants()->whereNotIn('id', $ids)->update(['active' => false]);

            return $p->load('variants');
        });
    }

    public function deleteProduct(Product $product)
    {
        $product->delete();

        return ['ok' => true];
    }

    public function upload(Request $r)
    {
        $r->validate(['image' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:5120|dimensions:max_width=12000,max_height=12000']);

        return ['path' => app(ArtworkImages::class)->store($r->file('image'))];
    }

    public function adminOrders()
    {
        return Order::with('items')->latest()->limit(500)->get();
    }

    public function status(Request $r, Order $order)
    {
        $d = $r->validate(['status' => ['required', Rule::in(['received', 'preparing', 'shipped', 'cancelled'])]]);

        return DB::transaction(function () use ($d, $order) {
            $o = Order::lockForUpdate()->findOrFail($order->id);
            $allowed = ['received' => ['preparing', 'cancelled'], 'preparing' => ['shipped', 'cancelled'], 'shipped' => [], 'cancelled' => []];
            if ($o->status !== $d['status']) {
                abort_unless(in_array($d['status'], $allowed[$o->status]), 422, 'This status transition is not allowed.');
                if ($d['status'] === 'cancelled') {
                    foreach ($o->items as $l) {
                        Variant::whereKey($l->variant_id)->increment('stock', $l->quantity);
                    }
                }
                $o->update($d);
            }

            return $o->load('items');
        });
    }
}
