<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
     Setting::updateOrCreate(
            ['key' => 'wholesale_first_order_min'],
            ['value' => '160.00']
        );
    }
}
