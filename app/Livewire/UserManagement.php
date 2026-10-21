<?php

namespace App\Livewire;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('components.layouts.app')]
class UserManagement extends Component
{
    use WithPagination;

    public string $search = '';

    public bool $showModal = false;

    public bool $isEditing = false;

    public ?int $userId = null;

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $role = 'operator_produksi';

    public bool $is_active = true;

    // Reset password modal state
    public bool $showPasswordModal = false;

    public ?int $resetUserId = null;

    public string $newPassword = '';

    public function mount(): void
    {
        if (! Auth::check()) {
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak: Manajemen Pengguna hanya dapat diakses oleh Admin.');
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->isEditing = false;
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->role = 'operator_produksi';
        $this->is_active = true;
        $this->showModal = true;
    }

    public function openEditModal(int $id): void
    {
        $this->resetValidation();
        $user = User::findOrFail($id);
        $this->isEditing = true;
        $this->userId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->password = '';
        $this->role = $user->role;
        $this->is_active = (bool) $user->is_active;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetValidation();
    }

    public function save(): void
    {
        if (! Auth::check()) {
            $this->dispatch('open-auth-modal');
            $this->redirect(route('login'), navigate: true);

            return;
        }

        if (Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak: Khusus Admin.');
        }

        $availableRoles = [
            'admin',
            'kasir_logistik',
            'petugas_gudang',
            'operator_produksi',
            'surveyor_opname',
            'manajemen',
            'demo',
        ];

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                $this->isEditing ? Rule::unique('users', 'email')->ignore($this->userId) : Rule::unique('users', 'email'),
            ],
            'role' => ['required', Rule::in($availableRoles)],
            'is_active' => ['boolean'],
        ];

        if (! $this->isEditing) {
            $rules['password'] = ['required', 'string', 'min:6'];
        }

        $this->validate($rules);

        if ($this->isEditing) {
            $user = User::findOrFail($this->userId);
            $data = [
                'name' => trim($this->name),
                'email' => trim($this->email),
                'role' => $this->role,
                'is_active' => $this->is_active,
            ];

            if (! empty($this->password)) {
                $data['password'] = Hash::make($this->password);
            }

            $user->update($data);
            session()->flash('success', "Pengguna {$user->name} berhasil diperbarui.");
        } else {
            $user = User::create([
                'name' => trim($this->name),
                'email' => trim($this->email),
                'password' => Hash::make($this->password),
                'role' => $this->role,
                'is_active' => $this->is_active,
            ]);

            session()->flash('success', "Pengguna baru {$user->name} ({$user->role}) berhasil ditambahkan.");
        }

        $this->showModal = false;
    }

    public function toggleStatus(int $id): void
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak.');
        }

        if ($id === Auth::id()) {
            session()->flash('error', 'Anda tidak dapat menonaktifkan akun sendiri.');

            return;
        }

        $user = User::findOrFail($id);
        $user->is_active = ! $user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'diaktifkan' : 'dinonaktifkan';
        session()->flash('success', "Akun {$user->name} berhasil {$statusStr}.");
    }

    public function openResetPasswordModal(int $id): void
    {
        $this->resetUserId = $id;
        $this->newPassword = 'password';
        $this->showPasswordModal = true;
    }

    public function closePasswordModal(): void
    {
        $this->showPasswordModal = false;
        $this->resetUserId = null;
    }

    public function resetPassword(): void
    {
        if (! Auth::check() || Auth::user()->role !== 'admin') {
            abort(403, 'Akses Ditolak.');
        }

        $this->validate([
            'newPassword' => ['required', 'string', 'min:6'],
        ]);

        $user = User::findOrFail($this->resetUserId);
        $user->update([
            'password' => Hash::make($this->newPassword),
        ]);

        $this->showPasswordModal = false;
        session()->flash('success', "Password untuk {$user->name} berhasil direset.");
    }

    public function render(): View
    {
        $users = User::query()
            ->when($this->search, function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            })
            ->latest('id')
            ->paginate(10);

        return view('livewire.user-management', [
            'users' => $users,
        ]);
    }
}
