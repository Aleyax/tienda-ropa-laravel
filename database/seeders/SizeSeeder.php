<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SizeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([['S', 'Small'], ['M', 'Medium'], ['L', 'Large'], ['XL', 'X-Large']] as [$c, $n]) {
            \App\Models\Size::updateOrCreate(['code' => $c], ['name' => $n, 'region' => 'PE']);
        }
    }
}
