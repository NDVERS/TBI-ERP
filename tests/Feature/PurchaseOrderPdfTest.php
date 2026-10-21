<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseOrderPdfTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(DatabaseSeeder::class);
    }

    public function test_authorized_user_can_stream_single_po_pdf(): void
    {
        $admin = User::where('role', 'admin')->firstOrFail();
        $po = PurchaseOrder::firstOrFail();

        $response = $this->actingAs($admin)->get(route('purchase-orders.pdf', $po->id));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_authorized_user_can_stream_monthly_po_report_pdf(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->firstOrFail();

        $response = $this->actingAs($kasir)->get(route('purchase-orders.report-pdf', [
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', (string) $response->getContent());
    }

    public function test_demo_user_can_access_both_po_pdf_documents(): void
    {
        $demoUser = User::where('role', 'demo')->firstOrFail();
        $po = PurchaseOrder::firstOrFail();

        // 1. Single PO PDF
        $responseSingle = $this->actingAs($demoUser)->get(route('purchase-orders.pdf', $po->id));
        $responseSingle->assertStatus(200);
        $responseSingle->assertHeader('Content-Type', 'application/pdf');

        // 2. Report PO PDF
        $responseReport = $this->actingAs($demoUser)->get(route('purchase-orders.report-pdf'));
        $responseReport->assertStatus(200);
        $responseReport->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_authorized_user_can_stream_monthly_po_report_excel(): void
    {
        $kasir = User::where('role', 'kasir_logistik')->firstOrFail();

        $response = $this->actingAs($kasir)->get(route('purchase-orders.report-excel', [
            'month' => 10,
            'year' => 2026,
        ]));

        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        $this->assertStringContainsString('Rekap_PO_TBI_2026_10.xlsx', (string) $response->headers->get('Content-Disposition'));

        $content = (string) $response->streamedContent();
        $this->assertNotEmpty($content);
        // Valid OpenXML (ZIP) signature starts with PK
        $this->assertSame('PK', substr($content, 0, 2));
    }

    public function test_unauthorized_role_is_forbidden_from_accessing_po_pdf(): void
    {
        $operator = User::where('role', 'operator_produksi')->firstOrFail();
        $po = PurchaseOrder::firstOrFail();

        $this->actingAs($operator)
            ->get(route('purchase-orders.pdf', $po->id))
            ->assertStatus(403);

        $this->actingAs($operator)
            ->get(route('purchase-orders.report-pdf'))
            ->assertStatus(403);

        $this->actingAs($operator)
            ->get(route('purchase-orders.report-excel'))
            ->assertStatus(403);
    }

    public function test_guest_cannot_access_po_pdf(): void
    {
        $po = PurchaseOrder::firstOrFail();

        $this->get(route('purchase-orders.pdf', $po->id))->assertRedirect(route('login'));
        $this->get(route('purchase-orders.report-pdf'))->assertRedirect(route('login'));
        $this->get(route('purchase-orders.report-excel'))->assertRedirect(route('login'));
    }
}
