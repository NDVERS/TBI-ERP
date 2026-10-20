<?php

namespace App\Livewire;

use App\Models\JobMix;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.app')]
class MasterJobMixIndex extends Component
{
    public string $search = '';

    public string $selectedCategory = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak: Modul Master Job Mix hanya dapat diakses oleh Admin.');
        }
    }

    public function render(): View
    {
        $allowedCodes = SalesOrderForm::ALLOWED_MUTU_CODES;
        $allowedMap = array_flip($allowedCodes);

        $jobMixes = JobMix::whereIn('mutu_code', $allowedCodes)
            ->when($this->search, function ($query, $search) {
                $query->where('mutu_code', 'like', "%{$search}%");
            })
            ->get()
            ->sortBy(fn ($jm) => $allowedMap[$jm->mutu_code] ?? 999)
            ->values();

        if ($this->selectedCategory === 'k_series') {
            $jobMixes = $jobMixes->filter(fn ($jm) => str_starts_with($jm->mutu_code, 'K'))->values();
        } elseif ($this->selectedCategory === 'fc_fs') {
            $jobMixes = $jobMixes->filter(fn ($jm) => ! str_starts_with($jm->mutu_code, 'K'))->values();
        }

        $totalKSeries = JobMix::whereIn('mutu_code', $allowedCodes)
            ->where('mutu_code', 'like', 'K%')
            ->count();

        $totalFcFs = JobMix::whereIn('mutu_code', $allowedCodes)
            ->where(function ($q) {
                $q->where('mutu_code', 'like', 'FC%')
                    ->orWhere('mutu_code', 'like', 'FS%');
            })
            ->count();

        return view('livewire.master-job-mix-index', [
            'jobMixes' => $jobMixes,
            'totalKSeries' => $totalKSeries,
            'totalFcFs' => $totalFcFs,
            'totalAll' => count($allowedCodes),
        ]);
    }
}
