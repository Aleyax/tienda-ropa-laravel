<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ColorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ([['Rojo', '#FF0000'], ['Azul', '#0066FF'], ['Negro', '#000000']] as [$n, $h]) {
            \App\Models\Color::updateOrCreate(['name' => $n], ['hex' => $h]);
        }
    }
}
