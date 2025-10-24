<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CustomerGroupSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        //
        \App\Models\CustomerGroup::firstOrCreate(['name' => 'minorista']);
        \App\Models\CustomerGroup::firstOrCreate(['name' => 'mayorista']);
        \App\Models\CustomerGroup::firstOrCreate(['name' => 'especial']);
    }
}
