<?php

namespace App\Services;

use App\Models\ProductionDocket;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class DocumentNumberService
{
    /**
     * Generate Production Docket number:
     * Format: SP01_xxxx, starting at SP01_7600
     */
    public function generateDocketNumber(): string
    {
        $prefix = 'SP01_';
        $baseSequence = 7600;

        $latestDocket = ProductionDocket::query()
            ->where('docket_no', 'like', "{$prefix}%")
            ->orderByDesc('id')
            ->first();

        if (! $latestDocket) {
            return "{$prefix}{$baseSequence}";
        }

        $rawNumber = str_replace($prefix, '', $latestDocket->docket_no);
        if (is_numeric($rawNumber)) {
            $nextSequence = max($baseSequence, ((int) $rawNumber) + 1);
        } else {
            $nextSequence = $baseSequence;
        }

        return "{$prefix}{$nextSequence}";
    }

    /**
     * Generate Sales Order number with monthly sequence reset:
     * Format: SO/01/TBI/{YY}/{MM}/{0001}
     */
    public function generateSoNumber(CarbonInterface|string|null $orderDate = null): string
    {
        $date = $this->resolveDate($orderDate);
        $yy = $date->format('y');
        $mm = $date->format('m');
        $prefix = "SO/01/TBI/{$yy}/{$mm}/";

        $latestOrder = SalesOrder::query()
            ->where('so_number', 'like', "{$prefix}%")
            ->orderByDesc('so_number')
            ->first();

        $nextSequence = 1;
        if ($latestOrder) {
            $parts = explode('/', $latestOrder->so_number);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $nextSequence = ((int) $lastPart) + 1;
            }
        }

        $formattedSequence = str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$formattedSequence}";
    }

    /**
     * Generate Purchase Order number with monthly sequence reset:
     * Format: PO-PCR/01/TBI/{YY}/{MM}/{0001}
     */
    public function generatePoNumber(CarbonInterface|string|null $poDate = null): string
    {
        $date = $this->resolveDate($poDate);
        $yy = $date->format('y');
        $mm = $date->format('m');
        $prefix = "PO-PCR/01/TBI/{$yy}/{$mm}/";

        $latestOrder = PurchaseOrder::query()
            ->where('po_number', 'like', "{$prefix}%")
            ->orderByDesc('po_number')
            ->first();

        $nextSequence = 1;
        if ($latestOrder) {
            $parts = explode('/', $latestOrder->po_number);
            $lastPart = end($parts);
            if (is_numeric($lastPart)) {
                $nextSequence = ((int) $lastPart) + 1;
            }
        }

        $formattedSequence = str_pad((string) $nextSequence, 4, '0', STR_PAD_LEFT);

        return "{$prefix}{$formattedSequence}";
    }

    private function resolveDate(CarbonInterface|string|null $date): Carbon
    {
        if ($date instanceof CarbonInterface) {
            return Carbon::instance($date);
        }

        if (is_string($date) && ! empty($date)) {
            return Carbon::parse($date);
        }

        return Carbon::now();
    }
}
