<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PriceListSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $minorista = \App\Models\CustomerGroup::firstOrCreate(['name' => 'minorista']);
        $mayorista = \App\Models\CustomerGroup::firstOrCreate(['name' => 'mayorista']);
        $especial  = \App\Models\CustomerGroup::firstOrCreate(['name' => 'especial']);

        $plMinor = \App\Models\PriceList::firstOrCreate([
            'group_id' => $minorista->id,
            'name'     => 'Lista Minorista',
            'currency' => 'PEN',
            'is_active' => true,
        ]);

        $plMayor = \App\Models\PriceList::firstOrCreate([
            'group_id' => $mayorista->id,
            'name'     => 'Lista Mayorista',
            'currency' => 'PEN',
            'is_active' => true,
        ]);

        $plEsp = \App\Models\PriceList::firstOrCreate([
            'group_id' => $especial->id,
            'name'     => 'Lista Especial',
            'currency' => 'PEN',
            'is_active' => true,
        ]);
    }
}
