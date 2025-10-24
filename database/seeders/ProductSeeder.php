<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        \App\Models\Product::updateOrCreate(
            ['slug' => 'polo-basico'],
            ['name' => 'Polo Básico', 'price_base' => 39.90]
        );

        \App\Models\Product::updateOrCreate(
            ['slug' => 'polera-oversize'],
            ['name' => 'Polera Oversize', 'price_base' => 89.90]
        );
    }
}
