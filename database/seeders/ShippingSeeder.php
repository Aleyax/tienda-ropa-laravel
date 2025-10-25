<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ShippingZone;
use App\Models\ShippingRate;

class ShippingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $lima = ShippingZone::updateOrCreate(
            ['name' => 'Lima Metropolitana'],
            ['districts' => ['Miraflores', 'San Isidro', 'Surco', 'Barranco', 'San Borja', 'La Molina', 'Magdalena', 'Jesús María', 'Pueblo Libre', 'Lince', 'San Miguel'], 'cod_enabled' => true]
        );
        ShippingRate::updateOrCreate(['shipping_zone_id' => $lima->id, 'name' => 'Envío estándar'], ['price' => 12.00, 'eta_days' => 2]);

        $callao = ShippingZone::updateOrCreate(
            ['name' => 'Callao'],
            ['districts' => ['Callao', 'Bellavista', 'La Perla', 'La Punta', 'Carmen de la Legua'], 'cod_enabled' => true]
        );
        ShippingRate::updateOrCreate(['shipping_zone_id' => $callao->id, 'name' => 'Envío estándar'], ['price' => 15.00, 'eta_days' => 3]);

        $prov = ShippingZone::updateOrCreate(
            ['name' => 'Provincia (courier)'],
            ['districts' => ['Huaral', 'Cañete', 'Chosica', 'Chaclacayo'], 'cod_enabled' => false]
        );
        ShippingRate::updateOrCreate(['shipping_zone_id' => $prov->id, 'name' => 'Courier terrestre'], ['price' => 25.00, 'eta_days' => 4]);
    }
}
