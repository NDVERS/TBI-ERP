<?php

namespace Database\Seeders;

use App\Models\Fleet;
use App\Models\FleetDailyLog;
use App\Models\FuelReceive;
use Illuminate\Database\Seeder;

class DexliteConsumptionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $operationalFleets = [
            ['unit_code' => 'TM Rental', 'name' => 'TM Rental (Hokkie Beton / Vendor)', 'type' => 'others'],
            ['unit_code' => 'Excavator', 'name' => 'Excavator Proyek', 'type' => 'others'],
            ['unit_code' => 'Concrete Pump', 'name' => 'Concrete Pump (CP Tugu / Plant)', 'type' => 'others'],
            ['unit_code' => 'Hilux', 'name' => 'Mobil Lapangan Hilux', 'type' => 'others'],
        ];

        foreach ($operationalFleets as $f) {
            Fleet::updateOrInsert(
                ['unit_code' => $f['unit_code']],
                [
                    'name' => $f['name'],
                    'type' => $f['type'],
                    'status' => 'ready',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        // 8 Penerimaan Solar Dexlite Resmi Oktober 2026 (Total 16.439,88 L)
        $receipts = [
            ['receive_date' => '2026-10-02', 'volume_liters' => 1935.90, 'ri_do_number' => 'DO-PRT/26/10/001', 'supplier_name' => 'PT Pertamina Patra Niaga'],
            ['receive_date' => '2026-10-06', 'volume_liters' => 1940.00, 'ri_do_number' => 'DO-PRT/26/10/002', 'supplier_name' => 'PT Pertamina Patra Niaga'],
            ['receive_date' => '2026-10-08', 'volume_liters' => 1950.26, 'ri_do_number' => 'DO-PRT/26/10/003', 'supplier_name' => 'PT Pertamina Patra Niaga'],
            ['receive_date' => '2026-10-09', 'volume_liters' => 1913.82, 'ri_do_number' => 'DO-PRT/26/10/004', 'supplier_name' => 'PT Pertamina Patra Niaga'],
            ['receive_date' => '2026-10-10', 'volume_liters' => 1962.95, 'ri_do_number' => 'DO-PRT/26/10/005', 'supplier_name' => 'PT Pertamina Patra Niaga'],
            ['receive_date' => '2026-10-11', 'volume_liters' => 1924.14, 'ri_do_number' => 'DO-PRT/26/10/006', 'supplier_name' => 'PT Pertamina Patra Niaga'],
            ['receive_date' => '2026-10-12', 'volume_liters' => 1946.83, 'ri_do_number' => 'DO-PRT/26/10/007', 'supplier_name' => 'PT Pertamina Patra Niaga'],
            ['receive_date' => '2026-10-13', 'volume_liters' => 2865.98, 'ri_do_number' => 'DO-PRT/26/10/008', 'supplier_name' => 'PT Pertamina Patra Niaga'],
        ];

        foreach ($receipts as $r) {
            FuelReceive::updateOrInsert(
                ['receive_date' => $r['receive_date'], 'ri_do_number' => $r['ri_do_number']],
                array_merge($r, ['created_at' => now(), 'updated_at' => now()])
            );
        }

        // Daily Fuel Consumption Matrix dari Sheet DEXLITE CONSUMPTION (Total 16.530 L)
        $daily = [
            '2026-10-01' => ['TM 08' => 149, 'TM 11' => 159, 'TM 12' => 191, 'Concrete Pump' => 100],
            '2026-10-03' => ['TM 01' => 126, 'TM 02' => 195, 'TM 03' => 158, 'TM 10' => 190, 'TM 05' => 197, 'TM 06' => 171, 'TM 09' => 189, 'TM 11' => 133, 'Wheel Loader' => 205],
            '2026-10-04' => ['TM 03' => 148, 'TM 06' => 98, 'TM 07' => 177, 'TM 08' => 216, 'TM 12' => 194],
            '2026-10-05' => ['TM 03' => 58, 'Excavator' => 40, 'Concrete Pump' => 78],
            '2026-10-06' => ['TM 01' => 138, 'TM 10' => 207, 'TM 05' => 200, 'TM 11' => 182],
            '2026-10-07' => ['TM 02' => 154, 'TM 06' => 155, 'TM 08' => 185, 'TM 09' => 190, 'TM 12' => 220, 'Hilux' => 65],
            '2026-10-08' => ['Concrete Pump' => 101],
            '2026-10-09' => ['TM 01' => 165, 'TM 02' => 208, 'TM 03' => 177, 'TM 10' => 183, 'TM 05' => 190, 'TM 06' => 142, 'TM 07' => 136, 'TM 08' => 189, 'TM 09' => 209, 'TM 11' => 133, 'Wheel Loader' => 230, 'TM Rental' => 500, 'Concrete Pump' => 35],
            '2026-10-10' => ['TM 03' => 166, 'TM 10' => 178, 'TM 05' => 198, 'TM 06' => 190, 'TM 07' => 181, 'TM 09' => 172, 'TM 11' => 152, 'TM 12' => 166, 'TM Rental' => 400, 'Concrete Pump' => 70],
            '2026-10-11' => ['TM 01' => 193, 'TM 02' => 244, 'TM 03' => 204, 'TM 05' => 214, 'TM 07' => 202, 'TM 08' => 184, 'TM 09' => 217, 'TM 11' => 207, 'TM 12' => 202, 'Wheel Loader' => 221, 'TM Rental' => 500, 'Concrete Pump' => 105],
            '2026-10-12' => ['TM 01' => 190, 'TM 03' => 171, 'TM 10' => 240, 'TM 06' => 192, 'TM 07' => 174, 'TM 08' => 200, 'TM 09' => 195, 'TM 11' => 180, 'TM 12' => 193, 'TM Rental' => 400, 'Concrete Pump' => 70],
            '2026-10-13' => ['TM 01' => 181, 'TM 02' => 235, 'TM 03' => 154, 'TM 10' => 163, 'TM 05' => 198, 'TM 06' => 135, 'TM 07' => 205, 'TM 08' => 198, 'TM 09' => 195, 'TM 11' => 176, 'TM Rental' => 350, 'Concrete Pump' => 103],
        ];

        $fleetsMap = Fleet::pluck('id', 'unit_code')->toArray();

        foreach ($daily as $date => $units) {
            foreach ($units as $unitCode => $liters) {
                $fleetId = $fleetsMap[$unitCode] ?? null;
                if (! $fleetId) {
                    continue;
                }

                FleetDailyLog::updateOrInsert(
                    ['fleet_id' => $fleetId, 'log_date' => $date],
                    [
                        'fuel_liters' => $liters,
                        'total_hm' => 0,
                        'start_hm' => 0,
                        'end_hm' => 0,
                        'updated_at' => now(),
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
