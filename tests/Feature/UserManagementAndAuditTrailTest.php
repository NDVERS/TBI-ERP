<?php

namespace Tests\Feature;

use App\Livewire\ActivityLogViewer;
use App\Livewire\UserManagement;
use App\Models\Customer;
use App\Models\JobMix;
use App\Models\SalesOrder;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;
use Tests\TestCase;

class UserManagementAndAuditTrailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_user_management_page_is_restricted_to_admin(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kasir = User::where('role', 'kasir_logistik')->first();
        $surveyor = User::where('role', 'surveyor_opname')->first();

        // Guest redirected to login
        $this->get('/users')->assertRedirect('/login');

        // Admin allowed
        $this->actingAs($admin)->get('/users')->assertOk();

        // Kasir & Surveyor forbidden
        $this->actingAs($kasir)->get('/users')->assertForbidden();
        $this->actingAs($surveyor)->get('/users')->assertForbidden();
    }

    public function test_admin_can_create_new_user(): void
    {
        $admin = User::where('role', 'admin')->first();
        $this->actingAs($admin);

        Livewire::test(UserManagement::class)
            ->call('openCreateModal')
            ->set('name', 'Budi Operasional')
            ->set('email', 'budi.ops@tbi.com')
            ->set('password', 'password123')
            ->set('role', 'operator_produksi')
            ->set('is_active', true)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('Budi Operasional');

        $this->assertDatabaseHas('users', [
            'email' => 'budi.ops@tbi.com',
            'role' => 'operator_produksi',
            'is_active' => true,
        ]);
    }

    public function test_admin_can_edit_user_and_toggle_active_status(): void
    {
        $admin = User::where('role', 'admin')->first();
        $operator = User::where('role', 'operator_produksi')->first();
        $this->actingAs($admin);

        // Edit user role
        Livewire::test(UserManagement::class)
            ->call('openEditModal', $operator->id)
            ->set('name', 'Operator Senior')
            ->set('role', 'petugas_gudang')
            ->call('save')
            ->assertHasNoErrors();

        $operator->refresh();
        $this->assertEquals('Operator Senior', $operator->name);
        $this->assertEquals('petugas_gudang', $operator->role);

        // Toggle status
        Livewire::test(UserManagement::class)
            ->call('toggleStatus', $operator->id);

        $operator->refresh();
        $this->assertFalse((bool) $operator->is_active);
    }

    public function test_admin_can_reset_user_password(): void
    {
        $admin = User::where('role', 'admin')->first();
        $kasir = User::where('role', 'kasir_logistik')->first();
        $this->actingAs($admin);

        Livewire::test(UserManagement::class)
            ->call('openResetPasswordModal', $kasir->id)
            ->set('newPassword', 'rahasiaBaru123')
            ->call('resetPassword')
            ->assertHasNoErrors();

        $kasir->refresh();
        $this->assertTrue(Hash::check('rahasiaBaru123', $kasir->password));
    }

    public function test_audit_logs_page_is_restricted_to_admin(): void
    {
        $admin = User::where('role', 'admin')->first();
        $gudang = User::where('role', 'petugas_gudang')->first();

        $this->get('/audit-logs')->assertRedirect('/login');
        $this->actingAs($admin)->get('/audit-logs')->assertOk();
        $this->actingAs($gudang)->get('/audit-logs')->assertForbidden();
    }

    public function test_automatic_audit_log_records_sales_order_creation(): void
    {
        $admin = User::where('role', 'admin')->first();
        $customer = Customer::first();
        $jobMix = JobMix::first();
        $this->actingAs($admin);

        SalesOrder::create([
            'so_number' => 'SO/01/TBI/26/10/9999',
            'order_date' => now()->format('Y-m-d'),
            'customer_id' => $customer->id,
            'location' => 'Jembatan Palu V',
            'job_mix_id' => $jobMix->id,
            'volume_m3' => 15.00,
            'distance_km' => 10.00,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'created',
            'model_type' => SalesOrder::class,
        ]);

        Livewire::test(ActivityLogViewer::class)
            ->assertSee('SO/01/TBI/26/10/9999');
    }
}
