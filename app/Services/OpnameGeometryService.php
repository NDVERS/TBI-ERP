<?php

namespace App\Services;

use InvalidArgumentException;

class OpnameGeometryService
{
    public const PI = 3.14;

    /**
     * Calculate volume for Limas Terpancung (Truncated Pyramid):
     * La = ((pa1 + pa2) / 2) * ((la1 + la2) / 2)
     * Lb = ((pb1 + pb2) / 2) * ((lb1 + lb2) / 2)
     * V = (1 / 3) * t * (La + Lb + sqrt(La * Lb))
     */
    public function calculateLimasTerpancung(
        float|string $pa1,
        float|string $pa2,
        float|string $la1,
        float|string $la2,
        float|string $pb1,
        float|string $pb2,
        float|string $lb1,
        float|string $lb2,
        float|string $t
    ): float {
        $paAvg = ((float) $pa1 + (float) $pa2) / 2;
        $laAvg = ((float) $la1 + (float) $la2) / 2;
        $areaTop = $paAvg * $laAvg;

        $pbAvg = ((float) $pb1 + (float) $pb2) / 2;
        $lbAvg = ((float) $lb1 + (float) $lb2) / 2;
        $areaBottom = $pbAvg * $lbAvg;

        $height = (float) $t;

        $sqrtProduct = sqrt($areaTop * $areaBottom);
        $volume = (1 / 3) * $height * ($areaTop + $areaBottom + $sqrtProduct);

        return round($volume, 4);
    }

    /**
     * Calculate volume for Kerucut Terpancung (Truncated Cone):
     * V = (1 / 3) * 3.14 * t * (Ra^2 + Rb^2 + (Ra * Rb))
     */
    public function calculateKerucutTerpancung(
        float|string $ra,
        float|string $rb,
        float|string $t
    ): float {
        $radiusTop = (float) $ra;
        $radiusBottom = (float) $rb;
        $height = (float) $t;

        $sumTerm = ($radiusTop ** 2) + ($radiusBottom ** 2) + ($radiusTop * $radiusBottom);
        $volume = (1 / 3) * self::PI * $height * $sumTerm;

        return round($volume, 4);
    }

    /**
     * Convert calculated volume to total weight in Kg:
     * Total Weight (Kg) = Stock Bin + (V * Density)
     */
    public function convertVolumeToWeight(
        float|string $volumeM3,
        float|string $densityKgM3,
        float|string $stockBinKg = 0
    ): float {
        $vol = (float) $volumeM3;
        $density = (float) $densityKgM3;
        $stockBin = (float) $stockBinKg;

        $totalKg = $stockBin + ($vol * $density);

        return round($totalKg, 2);
    }

    /**
     * Calculate volume for Limas Terpancung directly from top and bottom surface areas:
     * V = (1 / 3) * t * (La + Lb + sqrt(La * Lb))
     */
    public function calculateLimasTerpancungFromAreas(
        float|string $areaTop,
        float|string $areaBottom,
        float|string $t
    ): float {
        $la = (float) $areaTop;
        $lb = (float) $areaBottom;
        $height = (float) $t;

        $sqrtProduct = sqrt($la * $lb);
        $volume = (1 / 3) * $height * ($la + $lb + $sqrtProduct);

        return round($volume, 4);
    }

    /**
     * Generic opname calculation handler based on method and dimensions payload.
     *
     * @param  array<string, mixed>  $dimensions
     * @return array{calculated_volume_m3: float, calculated_weight_kg: float}
     */
    public function calculateOpname(string $method, array $dimensions, float|string $densityKgM3): array
    {
        $stockBin = (float) ($dimensions['stock_bin_kg'] ?? $dimensions['stock_bin'] ?? 0);
        $density = (float) $densityKgM3;

        switch ($method) {
            case 'limas_terpancung':
                if (isset($dimensions['la']) || isset($dimensions['area_top'])) {
                    $la = $dimensions['la'] ?? $dimensions['area_top'] ?? 0;
                    $lb = $dimensions['lb'] ?? $dimensions['area_bottom'] ?? 0;
                    $t = $dimensions['t'] ?? $dimensions['tinggi'] ?? 0;
                    $volume = $this->calculateLimasTerpancungFromAreas($la, $lb, $t);
                } else {
                    $volume = $this->calculateLimasTerpancung(
                        $dimensions['pa1'] ?? $dimensions['p_a1'] ?? 0,
                        $dimensions['pa2'] ?? $dimensions['p_a2'] ?? 0,
                        $dimensions['la1'] ?? $dimensions['l_a1'] ?? 0,
                        $dimensions['la2'] ?? $dimensions['l_a2'] ?? 0,
                        $dimensions['pb1'] ?? $dimensions['p_b1'] ?? 0,
                        $dimensions['pb2'] ?? $dimensions['p_b2'] ?? 0,
                        $dimensions['lb1'] ?? $dimensions['l_b1'] ?? 0,
                        $dimensions['lb2'] ?? $dimensions['l_b2'] ?? 0,
                        $dimensions['t'] ?? $dimensions['tinggi'] ?? 0
                    );
                }
                break;

            case 'kerucut_terpancung':
                $volume = $this->calculateKerucutTerpancung(
                    $dimensions['ra'] ?? $dimensions['r_a'] ?? 0,
                    $dimensions['rb'] ?? $dimensions['r_b'] ?? 0,
                    $dimensions['t'] ?? $dimensions['tinggi'] ?? 0
                );
                break;

            case 'drone':
                $volume = round((float) ($dimensions['volume_m3'] ?? $dimensions['volume'] ?? 0), 4);
                break;

            default:
                throw new InvalidArgumentException("Metode opname [{$method}] tidak didukung.");
        }

        $weight = $this->convertVolumeToWeight($volume, $density, $stockBin);

        return [
            'calculated_volume_m3' => $volume,
            'calculated_weight_kg' => $weight,
        ];
    }
}
