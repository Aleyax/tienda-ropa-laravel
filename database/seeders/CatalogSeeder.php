<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $polo = \App\Models\Product::updateOrCreate(
            ['slug' => 'polo-basico'],
            ['name' => 'Polo Básico', 'description' => 'Polo 100% algodón', 'status' => 'active']
        );


        $colors = \App\Models\Color::whereIn('name', ['Rojo', 'Azul', 'Negro'])->get();
        $sizes = \App\Models\Size::whereIn('code', ['S', 'M', 'L', 'XL'])->get();


        foreach ($colors as $c) {
            foreach ($sizes as $s) {
                \App\Models\ProductVariant::updateOrCreate(
                    ['product_id' => $polo->id, 'color_id' => $c->id, 'size_id' => $s->id],
                    [
                        'sku' => strtoupper(substr($c->name, 0, 1)) . $s->code . '-POLOBAS',
                        'stock' => 10,
                        'price_base' => null, // usar price base del producto/listas
                    ]
                );
            }


            // imagen por color (placeholder)
            \App\Models\Media::updateOrCreate(
                ['product_id' => $polo->id, 'color_id' => $c->id, 'url' => "/images/demo/polo-{$c->name}.jpg"],
                ['is_primary' => $c->name === 'Negro']
            );
        }
    }
}
