<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = Carbon::now();

        $additionalJobMixes = [
            // K-Series Lanjutan (K375 s/d K500)
            [
                'mutu_code' => 'K375 / C',
                'semen_kg' => 375.00,
                'pasir_palu_kg' => 650.00,
                'pasir_lokal_kg' => 160.00,
                'split_1_2_kg' => 1100.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 6.38,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K375 / R',
                'semen_kg' => 375.00,
                'pasir_palu_kg' => 640.00,
                'pasir_lokal_kg' => 155.00,
                'split_1_2_kg' => 440.00,
                'split_2_3_kg' => 660.00,
                'additive_liter' => 6.38,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K400 / C',
                'semen_kg' => 400.00,
                'pasir_palu_kg' => 640.00,
                'pasir_lokal_kg' => 150.00,
                'split_1_2_kg' => 1100.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 6.80,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K400 / R',
                'semen_kg' => 400.00,
                'pasir_palu_kg' => 630.00,
                'pasir_lokal_kg' => 145.00,
                'split_1_2_kg' => 440.00,
                'split_2_3_kg' => 660.00,
                'additive_liter' => 6.80,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K425 / C',
                'semen_kg' => 425.00,
                'pasir_palu_kg' => 630.00,
                'pasir_lokal_kg' => 145.00,
                'split_1_2_kg' => 1100.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 7.23,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K425 / R',
                'semen_kg' => 425.00,
                'pasir_palu_kg' => 620.00,
                'pasir_lokal_kg' => 140.00,
                'split_1_2_kg' => 440.00,
                'split_2_3_kg' => 660.00,
                'additive_liter' => 7.23,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K450 / C',
                'semen_kg' => 450.00,
                'pasir_palu_kg' => 620.00,
                'pasir_lokal_kg' => 140.00,
                'split_1_2_kg' => 1100.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 7.65,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K450 / R',
                'semen_kg' => 450.00,
                'pasir_palu_kg' => 610.00,
                'pasir_lokal_kg' => 135.00,
                'split_1_2_kg' => 440.00,
                'split_2_3_kg' => 660.00,
                'additive_liter' => 7.65,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K475 / C',
                'semen_kg' => 475.00,
                'pasir_palu_kg' => 610.00,
                'pasir_lokal_kg' => 135.00,
                'split_1_2_kg' => 1100.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 8.08,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K475 / R',
                'semen_kg' => 475.00,
                'pasir_palu_kg' => 600.00,
                'pasir_lokal_kg' => 130.00,
                'split_1_2_kg' => 440.00,
                'split_2_3_kg' => 660.00,
                'additive_liter' => 8.08,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K500 / C',
                'semen_kg' => 500.00,
                'pasir_palu_kg' => 600.00,
                'pasir_lokal_kg' => 130.00,
                'split_1_2_kg' => 1100.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 8.50,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'K500 / R',
                'semen_kg' => 500.00,
                'pasir_palu_kg' => 590.00,
                'pasir_lokal_kg' => 125.00,
                'split_1_2_kg' => 440.00,
                'split_2_3_kg' => 660.00,
                'additive_liter' => 8.50,
                'waste_factor' => 1.0500,
            ],

            // Seri FC & FS (Megapascal & Flexural)
            [
                'mutu_code' => 'FC10',
                'semen_kg' => 200.00,
                'pasir_palu_kg' => 500.00,
                'pasir_lokal_kg' => 480.00,
                'split_1_2_kg' => 1060.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 3.40,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'FC15',
                'semen_kg' => 240.00,
                'pasir_palu_kg' => 560.00,
                'pasir_lokal_kg' => 370.00,
                'split_1_2_kg' => 1080.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 4.08,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'FC20',
                'semen_kg' => 280.00,
                'pasir_palu_kg' => 670.00,
                'pasir_lokal_kg' => 220.00,
                'split_1_2_kg' => 1090.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 4.76,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'FC25',
                'semen_kg' => 320.00,
                'pasir_palu_kg' => 650.00,
                'pasir_lokal_kg' => 210.00,
                'split_1_2_kg' => 1095.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 5.44,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'FC30',
                'semen_kg' => 360.00,
                'pasir_palu_kg' => 660.00,
                'pasir_lokal_kg' => 160.00,
                'split_1_2_kg' => 1100.00,
                'split_2_3_kg' => 0.00,
                'additive_liter' => 6.12,
                'waste_factor' => 1.0500,
            ],
            [
                'mutu_code' => 'FS45',
                'semen_kg' => 380.00,
                'pasir_palu_kg' => 640.00,
                'pasir_lokal_kg' => 150.00,
                'split_1_2_kg' => 440.00,
                'split_2_3_kg' => 660.00,
                'additive_liter' => 6.46,
                'waste_factor' => 1.0500,
            ],
        ];

        foreach ($additionalJobMixes as $jobMix) {
            DB::table('job_mixes')->updateOrInsert(
                ['mutu_code' => $jobMix['mutu_code']],
                array_merge($jobMix, [
                    'updated_at' => $now,
                    'created_at' => $now,
                ])
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('job_mixes')->whereIn('mutu_code', [
            'K375 / C', 'K375 / R',
            'K400 / C', 'K400 / R',
            'K425 / C', 'K425 / R',
            'K450 / C', 'K450 / R',
            'K475 / C', 'K475 / R',
            'K500 / C', 'K500 / R',
            'FC10', 'FC15', 'FC20', 'FC25', 'FC30', 'FS45',
        ])->delete();
    }
};
