<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Product;
class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create a flash sale product
        Product::create([
            'name' => 'iPhone 15 Pro Max - Flash Sale',
            'description' => 'Limited stock! iPhone 15 Pro Max 256GB',
            'price' => 1199.99,
            'stock' => 50,
            'reserved' => 0,
        ]);

        Product::create([
            'name' => 'PlayStation 5 - Flash Sale',
            'description' => 'Limited stock! PlayStation 5 Console',
            'price' => 499.99,
            'stock' => 20,
            'reserved' => 0,
        ]);

        $this->command->info('Products seeded successfully!');
    }
}
