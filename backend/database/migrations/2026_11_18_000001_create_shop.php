<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $t) {
            $t->boolean('is_admin')->default(false);
        });
        Schema::create('products', function (Blueprint $t) {
            $t->id();
            $t->string('title');
            $t->string('size');
            $t->text('description');
            $t->text('introduction');
            $t->string('photo', 2048);
            $t->string('photo2', 2048)->nullable();
            $t->boolean('active')->default(true);
            $t->timestamps();
            $t->softDeletes();
        });
        Schema::create('variants', function (Blueprint $t) {
            $t->id();
            $t->foreignId('product_id')->constrained();
            $t->string('name');
            $t->unsignedInteger('price_cents')->nullable();
            $t->unsignedInteger('stock')->default(0);
            $t->boolean('active')->default(true);
            $t->timestamps();
        });
        Schema::create('orders', function (Blueprint $t) {
            $t->id();
            $t->uuid('reference')->unique();
            $t->string('idempotency_key', 100)->unique();
            $t->string('owner_session', 64);
            $t->foreignId('user_id')->nullable()->constrained();
            $t->string('name');
            $t->string('email');
            $t->json('address');
            $t->string('currency', 3);
            $t->unsignedInteger('subtotal_cents');
            $t->unsignedInteger('shipping_cents');
            $t->unsignedInteger('tax_cents');
            $t->unsignedInteger('total_cents');
            $t->string('status')->default('received');
            $t->string('payment_status')->default('not_collected');
            $t->boolean('is_test')->default(true);
            $t->timestamps();
        });
        Schema::create('order_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('order_id')->constrained();
            $t->foreignId('variant_id')->constrained();
            $t->string('title');
            $t->string('variant');
            $t->unsignedInteger('price_cents');
            $t->unsignedInteger('quantity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
        Schema::dropIfExists('variants');
        Schema::dropIfExists('products');
        Schema::table('users', fn (Blueprint $t) => $t->dropColumn('is_admin'));
    }
};
