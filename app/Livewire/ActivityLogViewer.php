<?php

namespace App\Livewire;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class ActivityLogViewer extends Component
{
    use WithPagination;

    public string $search = '';

    public string $actionFilter = '';

    public ?int $userFilter = null;

    public ?string $dateFrom = '';

    public ?string $dateTo = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak: Log Aktivitas hanya dapat diakses oleh Admin.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingActionFilter(): void
    {
        $this->resetPage();
    }

    public function updatingUserFilter(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->actionFilter = '';
        $this->userFilter = null;
        $this->dateFrom = '';
        $this->dateTo = '';
        $this->resetPage();
    }

    public function render(): View
    {
        $users = User::orderBy('name')->get(['id', 'name', 'email']);

        $logsQuery = ActivityLog::query()
            ->with(['user'])
            ->when($this->search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('description', 'like', "%{$search}%")
                        ->orWhere('ip_address', 'like', "%{$search}%")
                        ->orWhereHas('user', function ($uq) use ($search) {
                            $uq->where('name', 'like', "%{$search}%")
                                ->orWhere('email', 'like', "%{$search}%");
                        });
                });
            })
            ->when($this->actionFilter, function ($query, $action) {
                $query->where('action', $action);
            })
            ->when($this->userFilter, function ($query, $userId) {
                $query->where('user_id', $userId);
            })
            ->when($this->dateFrom, function ($query, $date) {
                $query->whereDate('created_at', '>=', $date);
            })
            ->when($this->dateTo, function ($query, $date) {
                $query->whereDate('created_at', '<=', $date);
            });

        $totalLogs = ActivityLog::count();
        $loginLogs = ActivityLog::where('action', 'login')->count();
        $mutationLogs = ActivityLog::whereIn('action', ['created', 'updated', 'deleted'])->count();

        $logs = $logsQuery->latest('id')->paginate(15);

        return view('livewire.activity-log-viewer', [
            'logs' => $logs,
            'users' => $users,
            'totalLogs' => $totalLogs,
            'loginLogs' => $loginLogs,
            'mutationLogs' => $mutationLogs,
        ]);
    }
}
