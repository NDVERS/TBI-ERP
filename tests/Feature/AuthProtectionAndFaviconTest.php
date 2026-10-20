<?php

namespace Tests\Feature;

use App\Livewire\MaterialReceiveForm;
use App\Livewire\SalesOrderForm;
use App\Livewire\StockOpnameForm;
use App\Livewire\UserManagement;
use App\Livewire\VesselShipmentForm;
use App\Models\Customer;
use App\Models\JobMix;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AuthProtectionAndFaviconTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_favicon_file_exists_and_contains_industrial_batching_plant_elements(): void
    {
        $faviconPath = public_path('favicon.svg');
        $this->assertFileExists($faviconPath);

        $svgContent = file_get_contents($faviconPath);
        $this->assertStringContainsString('<svg', $svgContent);
        $this->assertStringContainsString('TBI', $svgContent);
        $this->assertStringContainsString('viewBox="0 0 64 64"', $svgContent);
    }

    public function test_favicon_link_is_rendered_in_app_layout_and_login_page(): void
    {
        $loginResponse = $this->get('/login');
        $loginResponse->assertStatus(200);
        $loginResponse->assertSee('rel="icon" type="image/svg+xml"', false);
        $loginResponse->assertSee('favicon.svg', false);

        $homeResponse = $this->get('/');
        $homeResponse->assertStatus(200);
        $homeResponse->assertSee('rel="icon" type="image/svg+xml"', false);
        $homeResponse->assertSee('favicon.svg', false);
    }

    public function test_guest_is_redirected_to_login_on_all_data_entry_routes(): void
    {
        $this->get('/sales-orders')->assertRedirect('/login');
        $this->get('/material-receives')->assertRedirect('/login');
        $this->get('/vessel-shipments')->assertRedirect('/login');
        $this->get('/stock-opnames')->assertRedirect('/login');
        $this->get('/users')->assertRedirect('/login');
        $this->get('/reconciliation')->assertRedirect('/login');
        $this->get('/audit-logs')->assertRedirect('/login');
    }

    public function test_unauthenticated_livewire_mutation_is_redirected_to_login(): void
    {
        Livewire::test(SalesOrderForm::class)
            ->assertRedirect(route('login'));

        Livewire::test(MaterialReceiveForm::class)
            ->assertRedirect(route('login'));

        Livewire::test(VesselShipmentForm::class)
            ->assertRedirect(route('login'));

        Livewire::test(StockOpnameForm::class)
            ->assertRedirect(route('login'));

        Livewire::test(UserManagement::class)
            ->assertRedirect(route('login'));
    }

    public function test_unauthorized_role_is_forbidden_from_submitting_data(): void
    {
        // Kasir logistik is not allowed to submit Stock Opname or User Management
        $kasir = User::where('role', 'kasir_logistik')->first();

        Livewire::actingAs($kasir)
            ->test(UserManagement::class)
            ->assertForbidden();

        Livewire::actingAs($kasir)
            ->test(StockOpnameForm::class)
            ->assertForbidden();

        // Petugas gudang is not allowed to submit Sales Orders
        $gudang = User::where('role', 'petugas_gudang')->first();

        Livewire::actingAs($gudang)
            ->test(SalesOrderForm::class)
            ->assertForbidden();
    }

    public function test_authorized_user_can_submit_data_successfully(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->first();
        $customer = Customer::first();
        $jobMix = JobMix::first();

        Livewire::actingAs($kasir)
            ->test(SalesOrderForm::class)
            ->set('order_date', '2026-10-20')
            ->set('customer_id', $customer->id)
            ->set('location', 'Proyek IKN Jembatan')
            ->set('job_mix_id', $jobMix->id)
            ->set('volume_m3', 10.0)
            ->set('distance_km', 15.0)
            ->call('save')
            ->assertHasNoErrors()
            ->assertSee('berhasil dibuat');

        $this->assertDatabaseHas('sales_orders', [
            'location' => 'Proyek IKN Jembatan',
        ]);
    }
}
