<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class Phase1DatabaseAndRbacTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_seeder_populates_master_data_correctly(): void
    {
        $this->seed(DatabaseSeeder::class);

        // Verify Users for all 5 roles
        $this->assertDatabaseCount('users', 5);
        $this->assertDatabaseHas('users', ['email' => 'admin@tbi.com', 'role' => 'admin']);
        $this->assertDatabaseHas('users', ['email' => 'kasir@tbi.com', 'role' => 'kasir_logistik']);
        $this->assertDatabaseHas('users', ['email' => 'gudang@tbi.com', 'role' => 'petugas_gudang']);
        $this->assertDatabaseHas('users', ['email' => 'operator@tbi.com', 'role' => 'operator_produksi']);
        $this->assertDatabaseHas('users', ['email' => 'surveyor@tbi.com', 'role' => 'surveyor_opname']);

        // Verify 10 Materials
        $this->assertDatabaseCount('materials', 10);
        $this->assertDatabaseHas('materials', ['name' => 'Semen', 'unit' => 'Kg']);
        $this->assertDatabaseHas('materials', ['name' => 'Pasir Palu', 'unit' => 'Kg']);
        $this->assertDatabaseHas('materials', ['name' => 'Batu Besar 2/3', 'unit' => 'Kg']);
        $this->assertDatabaseHas('materials', ['name' => 'Dexlite', 'unit' => 'Liter']);

        // Verify 28 Suppliers
        $this->assertDatabaseCount('suppliers', 28);
        $this->assertDatabaseHas('suppliers', ['name' => 'WAN SHANG( CONTAINER DARI CHINA)']);

        // Verify Customers
        $this->assertGreaterThanOrEqual(20, Customer::count());
        $this->assertDatabaseHas('customers', ['name' => 'PT Recta Construction']);

        // Verify 24 Master Job Mixes
        $this->assertDatabaseCount('job_mixes', 24);
        $this->assertDatabaseHas('job_mixes', [
            'mutu_code' => 'K300 / C',
            'waste_factor' => '1.0500',
        ]);
        $this->assertDatabaseHas('job_mixes', [
            'mutu_code' => 'K100 / C',
        ]);
    }

    public function test_role_middleware_authorizes_accessible_roles(): void
    {
        Route::get('/test-admin-only', function () {
            return response()->json(['status' => 'success']);
        })->middleware(['web', 'role:admin']);

        Route::get('/test-kasir-only', function () {
            return response()->json(['status' => 'success']);
        })->middleware(['web', 'role:kasir_logistik']);

        $admin = User::factory()->create(['role' => 'admin']);
        $kasir = User::factory()->create(['role' => 'kasir_logistik']);
        $operator = User::factory()->create(['role' => 'operator_produksi']);

        // Admin can access admin-only and kasir-only routes (superuser)
        $this->actingAs($admin)->get('/test-admin-only')->assertStatus(200);
        $this->actingAs($admin)->get('/test-kasir-only')->assertStatus(200);

        // Kasir can access kasir-only route
        $this->actingAs($kasir)->get('/test-kasir-only')->assertStatus(200);

        // Operator cannot access kasir-only route
        $this->actingAs($operator)->get('/test-kasir-only')->assertStatus(403);

        // Kasir cannot access admin-only route
        $this->actingAs($kasir)->get('/test-admin-only')->assertStatus(403);
    }

    public function test_user_model_role_helper_methods(): void
    {
        $admin = new User(['role' => 'admin']);
        $operator = new User(['role' => 'operator_produksi']);

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($operator->isAdmin());

        $this->assertTrue($operator->hasRole('operator_produksi'));
        $this->assertTrue($operator->hasRole(['operator_produksi', 'petugas_gudang']));
        $this->assertFalse($operator->hasRole(['admin', 'kasir_logistik']));
    }
}
