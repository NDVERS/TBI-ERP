<?php

namespace Tests\Feature;

use App\Models\ProductionDocket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QolSessionThreeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_material_receive_report_pdf_download_allowed_for_warehouse_staff(): void
    {
        $staff = User::where('role', 'petugas_gudang')->first()
            ?? User::factory()->create(['role' => 'petugas_gudang']);

        $response = $this->actingAs($staff)->get(route('material-receives.report-pdf', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertStatus(200);
    }

    public function test_material_receive_report_excel_download_allowed_for_warehouse_staff(): void
    {
        $staff = User::where('role', 'petugas_gudang')->first()
            ?? User::factory()->create(['role' => 'petugas_gudang']);

        $response = $this->actingAs($staff)->get(route('material-receives.report-excel', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('Rekap_Penerimaan_Material_TBI', $response->headers->get('content-disposition') ?? '');
    }

    public function test_production_docket_single_pdf_and_report_downloads_allowed_for_operator(): void
    {
        $operator = User::where('role', 'operator_produksi')->first()
            ?? User::factory()->create(['role' => 'operator_produksi']);

        $docket = ProductionDocket::first();

        // 1. Single Ticket PDF
        if ($docket) {
            $singleResponse = $this->actingAs($operator)->get(route('production-dockets.pdf', $docket));
            $singleResponse->assertStatus(200);
        }

        // 2. Monthly Summary PDF
        $reportPdfResponse = $this->actingAs($operator)->get(route('production-dockets.report-pdf', [
            'year' => now()->year,
            'month' => now()->month,
        ]));
        $reportPdfResponse->assertStatus(200);

        // 3. Monthly Summary Excel
        $reportExcelResponse = $this->actingAs($operator)->get(route('production-dockets.report-excel', [
            'year' => now()->year,
            'month' => now()->month,
        ]));
        $reportExcelResponse->assertStatus(200);
        $this->assertStringContainsString('Rekap_Produksi_Docket_TBI', $reportExcelResponse->headers->get('content-disposition') ?? '');
    }

    public function test_unauthorized_roles_blocked_from_exporting(): void
    {
        $surveyor = User::where('role', 'surveyor_opname')->first()
            ?? User::factory()->create(['role' => 'surveyor_opname']);

        // Surveyor cannot export Material Receive reports (requires admin/petugas_gudang/demo)
        $response1 = $this->actingAs($surveyor)->get(route('material-receives.report-pdf'));
        $response1->assertStatus(403);

        // Surveyor cannot export Production Docket reports (requires admin/operator_produksi/demo)
        $response2 = $this->actingAs($surveyor)->get(route('production-dockets.report-pdf'));
        $response2->assertStatus(403);

        // Surveyor cannot export Executive reports (requires admin/manajemen/demo)
        $response3 = $this->actingAs($surveyor)->get(route('executive.report-excel'));
        $response3->assertStatus(403);

        $response4 = $this->actingAs($surveyor)->get(route('executive.report-pdf'));
        $response4->assertStatus(403);
    }

    public function test_executive_all_in_one_excel_download_allowed_for_management_and_admin(): void
    {
        $manajemen = User::where('role', 'manajemen')->first()
            ?? User::factory()->create(['role' => 'manajemen']);

        $response = $this->actingAs($manajemen)->get(route('executive.report-excel', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertStatus(200);
        $this->assertStringContainsString('Rekap_Bulanan_All_In_One_TBI', $response->headers->get('content-disposition') ?? '');
    }

    public function test_executive_summary_pdf_download_allowed_for_management_and_admin(): void
    {
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('executive.report-pdf', [
            'year' => now()->year,
            'month' => now()->month,
        ]));

        $response->assertStatus(200);
    }

    public function test_executive_dashboard_renders_export_buttons(): void
    {
        $admin = User::where('role', 'admin')->first()
            ?? User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertSee('Excel All-in-One');
        $response->assertSee('Ringkasan PDF');
    }
}
