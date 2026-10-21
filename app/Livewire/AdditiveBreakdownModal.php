<?php

namespace App\Livewire;

use App\Models\AdditivePowder;
use App\Models\Material;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class AdditiveBreakdownModal extends Component
{
    public bool $isOpen = false;

    #[On('open-additive-modal')]
    public function open(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

    public function render(): View
    {
        $powders = AdditivePowder::orderBy('id')->get();
        $totalPowderStock = (float) $powders->sum('stock_kg');
        $masterAdditive = Material::where('name', 'Additive')->first();

        return view('livewire.additive-breakdown-modal', [
            'powders' => $powders,
            'totalPowderStock' => $totalPowderStock,
            'masterAdditive' => $masterAdditive,
        ]);
    }
}
