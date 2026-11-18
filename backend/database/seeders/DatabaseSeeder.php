<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        foreach (json_decode(file_get_contents(__DIR__.'/catalog.json'), true) as $row) {
            if (Product::withTrashed()->find($row['id'])) {
                continue;
            }
            $p = new Product;
            $p->forceFill($row);
            $p->save();
            foreach (['Original painting', 'Classic Print 50 x 70 cm', 'Premium Print 70 x 100 cm'] as $name) {
                $p->variants()->create(['name' => $name, 'price_cents' => null, 'stock' => 0]);
            }
        }
    }
}
