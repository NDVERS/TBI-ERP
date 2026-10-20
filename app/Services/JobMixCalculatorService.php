<?php

namespace App\Services;

use App\Models\JobMix;

class JobMixCalculatorService
{
    public const DEFAULT_WASTE_FACTOR = 1.0500;

    public const SOLAR_KM_PER_LITER = 1.2;

    /**
     * Calculate estimated material requirements for a Sales Order:
     * Material Need = Recipe per m3 * Volume SO * Waste Factor (default 1.05)
     *
     * @return array<string, float>
     */
    public function calculateMaterialRequirements(JobMix $jobMix, float|string $volumeM3, ?float $wasteFactor = null): array
    {
        $vol = (float) $volumeM3;
        $factor = $wasteFactor ?? (float) ($jobMix->waste_factor ?: self::DEFAULT_WASTE_FACTOR);

        // Safe fallback ratio if specific job mix formula is incomplete or zero
        $semenKg = (float) ($jobMix->semen_kg > 0 ? $jobMix->semen_kg : 300.00);
        $pasirPaluKg = (float) ($jobMix->pasir_palu_kg > 0 ? $jobMix->pasir_palu_kg : 650.00);
        $pasirLokalKg = (float) ($jobMix->pasir_lokal_kg > 0 ? $jobMix->pasir_lokal_kg : 200.00);
        $split12Kg = (float) ($jobMix->split_1_2_kg > 0 ? $jobMix->split_1_2_kg : 1080.00);
        $split23Kg = (float) ($jobMix->split_2_3_kg >= 0 ? $jobMix->split_2_3_kg : 0.00);
        $additiveLiter = (float) ($jobMix->additive_liter > 0 ? $jobMix->additive_liter : 4.50);

        return [
            'est_semen_kg' => round($semenKg * $vol * $factor, 2),
            'est_pasir_palu_kg' => round($pasirPaluKg * $vol * $factor, 2),
            'est_pasir_lokal_kg' => round($pasirLokalKg * $vol * $factor, 2),
            'est_split_1_2_kg' => round($split12Kg * $vol * $factor, 2),
            'est_split_2_3_kg' => round($split23Kg * $vol * $factor, 2),
            'est_additive_liter' => round($additiveLiter * $vol * $factor, 2),
        ];
    }

    /**
     * Calculate estimated fuel (dexlite/solar) for delivery trip:
     * Solar (Liter) = (1 / 1.2) * Jarak KM
     */
    public function calculateFuelRequirement(float|string $distanceKm): float
    {
        $km = (float) $distanceKm;

        return round((1 / self::SOLAR_KM_PER_LITER) * $km, 2);
    }

    /**
     * Calculate all estimated server-side values for Sales Order:
     *
     * @return array<string, float>
     */
    public function calculateAllSoEstimates(JobMix $jobMix, float|string $volumeM3, float|string $distanceKm, ?float $wasteFactor = null): array
    {
        $materialEst = $this->calculateMaterialRequirements($jobMix, $volumeM3, $wasteFactor);
        $materialEst['est_solar_liter'] = $this->calculateFuelRequirement($distanceKm);

        return $materialEst;
    }
}
