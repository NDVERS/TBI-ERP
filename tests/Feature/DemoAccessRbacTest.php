<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DemoAccessRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_quick_account_demo_logs_in_successfully(): void
    {
        // 1. Test quick_role demo login
        $response = $this->post('/login', [
            'quick_role' => 'demo',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();

        $user = auth()->user();
        $this->assertEquals('demo', $user->role);
        $this->assertEquals('demo@tbi.com', $user->email);
    }

    public function test_standard_credentials_demo_logs_in_successfully(): void
    {
        $response = $this->post('/login', [
            'email' => 'demo@tbi.com',
            'password' => 'password',
        ]);

        $response->assertRedirect('/');
        $this->assertAuthenticated();
        $this->assertEquals('demo', auth()->user()->role);
    }

    public function test_login_page_renders_demo_button(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Demo');
        $response->assertSee('Akun Demo / Akses Terbatas');
        $response->assertSee('value="demo"', false);
    }

    public function test_demo_user_can_access_all_operational_modules(): void
    {
        $demoUser = User::where('role', 'demo')->firstOrFail();

        $operationalRoutes = [
            '/dashboard',
            '/sales-orders',
            '/purchase-orders',
            '/material-receives',
            '/vessel-shipments',
            '/production-dockets',
            '/stock-opnames',
        ];

        foreach ($operationalRoutes as $route) {
            $response = $this->actingAs($demoUser)->get($route);
            $response->assertStatus(200);
        }
    }

    public function test_demo_user_is_forbidden_from_management_and_audit_modules(): void
    {
        $demoUser = User::where('role', 'demo')->firstOrFail();

        $restrictedRoutes = [
            '/reconciliation',
            '/users',
            '/audit-logs',
            '/master-job-mix',
        ];

        foreach ($restrictedRoutes as $route) {
            $response = $this->actingAs($demoUser)->get($route);
            $response->assertStatus(403);
        }
    }

    public function test_sidebar_shows_operational_menus_and_hides_audit_menus_for_demo_user(): void
    {
        $demoUser = User::where('role', 'demo')->firstOrFail();

        $response = $this->actingAs($demoUser)->get('/dashboard');
        $response->assertStatus(200);

        // Should see operational menu items
        $response->assertSee('Dashboard Eksekutif');
        $response->assertSee('Sales Order (SO)');
        $response->assertSee('Purchase Order (PO)');
        $response->assertSee('Penerimaan Truk');
        $response->assertSee('Pengiriman Kapal');
        $response->assertSee('Produksi Harian');
        $response->assertSee('Stock Opname Fisik');

        // Should NOT see management & audit header and links
        $response->assertDontSee('Manajemen & Audit');
        $response->assertDontSee('Rekonsiliasi Stok');
        $response->assertDontSee('Manajemen User');
        $response->assertDontSee('Audit Trail / Log');
        $response->assertDontSee('Master Job Mix (IP)');
    }

    public function test_admin_user_can_see_and_access_management_and_audit_modules(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();

        $response = $this->actingAs($admin)->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Manajemen & Audit');
        $response->assertSee('Rekonsiliasi Stok');
        $response->assertSee('Manajemen User');
        $response->assertSee('Audit Trail / Log');
        $response->assertSee('Master Job Mix (IP)');

        $this->actingAs($admin)->get('/reconciliation')->assertStatus(200);
        $this->actingAs($admin)->get('/users')->assertStatus(200);
        $this->actingAs($admin)->get('/audit-logs')->assertStatus(200);
        $this->actingAs($admin)->get('/master-job-mix')->assertStatus(200);
    }
}
