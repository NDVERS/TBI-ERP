<?php

namespace App\Services;

class ProcurementCalculatorService
{
    public const DEFAULT_PPN_RATE = 0.11;

    /**
     * Calculate Purchase Order amounts:
     * Subtotal = Qty * UnitPrice
     * PPN = Subtotal * 11%
     * GrandTotal = Subtotal + PPN
     *
     * @return array{qty: float, unit_price: float, subtotal: float, ppn_amount: float, grand_total: float}
     */
    public function calculatePoAmounts(float|string $qty, float|string $unitPrice, float $ppnRate = self::DEFAULT_PPN_RATE): array
    {
        $quantity = (float) $qty;
        $price = (float) $unitPrice;

        $subtotal = round($quantity * $price, 2);
        $ppnAmount = round($subtotal * $ppnRate, 2);
        $grandTotal = round($subtotal + $ppnAmount, 2);

        return [
            'qty' => $quantity,
            'unit_price' => $price,
            'subtotal' => $subtotal,
            'ppn_amount' => $ppnAmount,
            'grand_total' => $grandTotal,
        ];
    }

    /**
     * Calculate vessel shipment shrinkage:
     * Volume Diff = Load Volume - Unload Volume
     * Shrinkage Percent = Volume Diff / Load Volume
     *
     * @return array{load_volume: float, unload_volume: float, volume_diff: float, shrinkage_percent: float}
     */
    public function calculateVesselShrinkage(float|string $loadVolume, float|string $unloadVolume): array
    {
        $load = (float) $loadVolume;
        $unload = (float) $unloadVolume;

        $diff = round($load - $unload, 2);
        $shrinkagePercent = $load > 0 ? round(($diff / $load) * 100, 4) : 0.0;

        return [
            'load_volume' => $load,
            'unload_volume' => $unload,
            'volume_diff' => $diff,
            'shrinkage_percent' => $shrinkagePercent,
        ];
    }

    /**
     * Calculate material receive total weight in kg:
     * Total Weight (Kg) = Volume (m3) * Density (kg/m3)
     */
    public function calculateReceiveWeight(float|string $volumeM3, float|string $densityKgM3): float
    {
        $vol = (float) $volumeM3;
        $density = (float) $densityKgM3;

        return round($vol * $density, 2);
    }
}
