<?php

namespace Database\Seeders;

use App\Models\Fleet;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class FleetSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = Carbon::parse('2026-10-01');

        $tms = [
            ['unit_code' => 'TM 01', 'name' => 'Truck Mixer 01', 'plate' => 'DN 8101 AB', 'current_hm' => 2480.0, 'last_service_hm' => 2100.0, 'current_km' => 25622.0],
            ['unit_code' => 'TM 02', 'name' => 'Truck Mixer 02', 'plate' => 'DN 8102 AB', 'current_hm' => 1950.0, 'last_service_hm' => 1500.0, 'current_km' => 26817.0],
            ['unit_code' => 'TM 03', 'name' => 'Truck Mixer 03', 'plate' => 'DN 8103 AB', 'current_hm' => 3120.0, 'last_service_hm' => 2900.0, 'current_km' => 26734.0],
            ['unit_code' => 'TM 05', 'name' => 'Truck Mixer 05', 'plate' => 'DN 8105 AB', 'current_hm' => 1840.0, 'last_service_hm' => 1400.0, 'current_km' => 25462.0],
            ['unit_code' => 'TM 06', 'name' => 'Truck Mixer 06', 'plate' => 'DN 8106 AB', 'current_hm' => 2750.0, 'last_service_hm' => 2300.0, 'current_km' => 23447.0],
            ['unit_code' => 'TM 07', 'name' => 'Truck Mixer 07', 'plate' => 'DN 8107 AB', 'current_hm' => 1620.0, 'last_service_hm' => 1500.0, 'current_km' => 20708.0],
            ['unit_code' => 'TM 08', 'name' => 'Truck Mixer 08', 'plate' => 'DN 8108 AB', 'current_hm' => 2940.0, 'last_service_hm' => 2500.0, 'current_km' => 19024.0],
            ['unit_code' => 'TM 09', 'name' => 'Truck Mixer 09', 'plate' => 'DN 8109 AB', 'current_hm' => 2210.0, 'last_service_hm' => 2000.0, 'current_km' => 22437.0],
            ['unit_code' => 'TM 10', 'name' => 'Truck Mixer 10', 'plate' => 'DN 8110 AB', 'current_hm' => 2680.0, 'last_service_hm' => 2200.0, 'current_km' => 25302.0],
            ['unit_code' => 'TM 11', 'name' => 'Truck Mixer 11', 'plate' => 'DN 8111 AB', 'current_hm' => 1430.0, 'last_service_hm' => 1000.0, 'current_km' => 14645.0],
            ['unit_code' => 'TM 12', 'name' => 'Truck Mixer 12', 'plate' => 'DN 8112 AB', 'current_hm' => 3480.0, 'last_service_hm' => 3000.0, 'current_km' => 17398.0],
        ];

        foreach ($tms as $tm) {
            Fleet::updateOrInsert(
                ['unit_code' => $tm['unit_code']],
                [
                    'name' => $tm['name'],
                    'type' => 'truck_mixer',
                    'license_plate' => $tm['plate'],
                    'model_specification' => 'Hino 500 FM 260 JM (Kapasitas Drum 7 m³)',
                    'status' => 'ready',
                    'current_hm' => $tm['current_hm'],
                    'current_km' => $tm['current_km'],
                    'last_service_hm' => $tm['last_service_hm'],
                    'last_service_date' => $now->copy()->subDays(rand(10, 45))->toDateString(),
                    'service_interval_hm' => 500.0,
                    'service_interval_days' => null,
                    'updated_at' => now(),
                    'created_at' => now(),
                ]
            );
        }

        // 1 Unit Wheel Loader (Bucket 1,8) - Data Riil Sheet OKT: HM 1.638,9 s/d 1.695,7
        Fleet::updateOrInsert(
            ['unit_code' => 'Wheel Loader'],
            [
                'name' => 'Wheel Loader',
                'type' => 'wheel_loader',
                'license_plate' => null,
                'model_specification' => 'Bucket 1,8',
                'status' => 'ready',
                'current_hm' => 1695.7,
                'current_km' => null,
                'last_service_hm' => 1500.0,
                'last_service_date' => Carbon::parse('2026-09-25')->toDateString(),
                'service_interval_hm' => 500.0,
                'service_interval_days' => 30, // 1 Bulan
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        // 1 Unit Lain-lain (Alat / Operasional Lainnya)
        Fleet::updateOrInsert(
            ['unit_code' => 'Lain-lain'],
            [
                'name' => 'Lain-lain',
                'type' => 'others',
                'license_plate' => null,
                'model_specification' => 'Alat / Kebutuhan Operasional Lainnya',
                'status' => 'ready',
                'current_hm' => 0.0,
                'current_km' => null,
                'last_service_hm' => 0.0,
                'last_service_date' => null,
                'service_interval_hm' => 500.0,
                'service_interval_days' => null,
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );
    }
}
