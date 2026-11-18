<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ImageProtectionTest extends TestCase
{
    use RefreshDatabase;

    private function artist(): User
    {
        $u = User::factory()->create(['is_admin' => true]);

        return $u;
    }

    public function test_upload_only_exposes_a_small_reencoded_derivative(): void
    {
        Storage::fake('originals');
        Storage::fake('web_images');
        $file = UploadedFile::fake()->image('large.png', 2400, 1600);
        $source = file_get_contents($file->getRealPath());
        $reply = $this->actingAs($this->artist())->postJson('/api/admin/images', ['image' => $file])->assertOk();
        $path = $reply->json('path');
        $this->assertMatchesRegularExpression('#^/media/products/[a-f0-9-]+\.webp$#', $path);
        $name = basename($path);
        $web = Storage::disk('web_images')->get($name);
        $size = getimagesizefromstring($web);
        $this->assertSame([960, 640, IMAGETYPE_WEBP], array_slice($size, 0, 3));
        $this->assertNotSame($source, $web);
        $originals = Storage::disk('originals')->allFiles();
        $this->assertCount(1, $originals);
        $this->assertSame($source, Storage::disk('originals')->get($originals[0]));
        $this->get($path)->assertOk()->assertHeader('Content-Type', 'image/webp')->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/storage/products/'.basename($originals[0]))->assertNotFound();
        $this->get('/media/products/'.basename($originals[0]))->assertNotFound();
        $this->get('/media/products/missing.webp')->assertNotFound();
    }

    public function test_pixel_bomb_is_rejected_before_decode_and_original_is_not_published(): void
    {
        Storage::fake('originals');
        Storage::fake('web_images');
        $tiny = UploadedFile::fake()->image('too-big.png', 1, 1);
        $png = file_get_contents($tiny->getRealPath());
        $header = pack('NN', 4100, 4000).substr($png, 24, 5);
        $png = substr($png, 0, 16).$header.pack('N', crc32('IHDR'.$header)).substr($png, 33);
        file_put_contents($tiny->getRealPath(), $png);
        $this->actingAs($this->artist())->postJson('/api/admin/images', ['image' => $tiny])->assertUnprocessable();
        $this->assertCount(0, Storage::disk('originals')->allFiles());
        $this->assertCount(0, Storage::disk('web_images')->allFiles());
    }

    public function test_small_images_are_not_upscaled_and_hidden_source_paths_cannot_be_assigned(): void
    {
        Storage::fake('originals');
        Storage::fake('web_images');
        $artist = $this->artist();
        $reply = $this->actingAs($artist)->postJson('/api/admin/images', ['image' => UploadedFile::fake()->image('small.jpg', 120, 80)])->assertOk();
        $size = getimagesizefromstring(Storage::disk('web_images')->get(basename($reply->json('path'))));
        $this->assertSame([120, 80], array_slice($size, 0, 2));
        foreach (['/storage/products/raw.jpg', 'assets/../private-originals/raw.webp', '/media/products/../../raw.webp', 'assets/raw.png'] as $path) {
            $this->postJson('/api/admin/products', ['title' => 'Test', 'size' => 'Test', 'description' => '', 'introduction' => '', 'photo' => $path, 'active' => true, 'variants' => [['name' => 'Test', 'stock' => 0, 'active' => true, 'price_cents' => null]]])->assertUnprocessable();
        }
    }

    public function test_legacy_migration_preserves_source_and_updates_existing_products(): void
    {
        Storage::fake('public');
        Storage::fake('originals');
        Storage::fake('web_images');
        $image = UploadedFile::fake()->image('legacy.jpg', 1200, 800);
        $source = file_get_contents($image->getRealPath());
        Storage::disk('public')->put('products/legacy.jpg', $source);
        $p = Product::create(['title' => 'Keep this title', 'description' => '', 'introduction' => '', 'size' => 'Keep size', 'photo' => '/storage/products/legacy.jpg', 'photo2' => 'assets/point of transition.png']);
        $this->artisan('shop:protect-images')->assertSuccessful();
        $this->assertSame('Keep this title', $p->fresh()->title);
        $this->assertStringStartsWith('/media/products/', $p->fresh()->photo);
        $this->assertSame('assets/point of transition.webp', $p->fresh()->photo2);
        Storage::disk('public')->assertMissing('products/legacy.jpg');
        $originals = Storage::disk('originals')->allFiles();
        $this->assertCount(1, $originals);
        $this->assertSame($source, Storage::disk('originals')->get($originals[0]));
        $this->artisan('shop:protect-images')->assertSuccessful();
        $this->assertCount(1, Storage::disk('originals')->allFiles());
    }

    public function test_private_disks_cannot_generate_public_urls(): void
    {
        $this->assertFalse(config('filesystems.disks.originals.serve'));
        $this->assertFalse(config('filesystems.disks.local.serve'));
        $this->assertFalse(config('filesystems.disks.web_images.serve'));
        $this->assertStringNotContainsString(public_path(), config('filesystems.disks.originals.root'));
        $this->get('/assets/point%20of%20transition.png')->assertNotFound();
    }
}
