<?php

namespace Tests\Feature;

use App\Services\PartnerCommissionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PartnerProductionSafetyAuditTest extends TestCase
{
    public function test_partner_schema_contains_required_sales_snapshot_and_link_fields(): void
    {
        foreach ([
            'partners',
            'partner_product_commissions',
            'partner_sales',
            'partner_commission_payments',
        ] as $table) {
            $this->assertTrue(Schema::hasTable($table), "Missing table [{$table}].");
        }

        foreach ([
            'partner_id',
            'order_id',
            'order_item_id',
            'commission_type',
            'commission_rate',
            'commission_amount',
            'status',
        ] as $column) {
            $this->assertTrue(Schema::hasColumn('partner_sales', $column), "Missing partner_sales column [{$column}].");
        }
    }

    public function test_fixed_and_percentage_math_is_supported(): void
    {
        $service = new PartnerCommissionService();

        $this->assertSame(1000.0, $service->calculate('fixed', 500, 10000, 2));
        $this->assertSame(750.0, $service->calculate('percentage', 7.5, 10000, 2));
    }

    public function test_partner_sales_snapshot_columns_are_decimal_and_not_config_references(): void
    {
        $columns = collect(DB::select('SHOW COLUMNS FROM partner_sales'))->keyBy('Field');

        $this->assertStringContainsString('decimal', strtolower($columns['commission_rate']->Type));
        $this->assertStringContainsString('decimal', strtolower($columns['commission_amount']->Type));
        $this->assertArrayHasKey('commission_type', $columns->all());
    }

    public function test_order_item_id_has_a_database_unique_constraint(): void
    {
        $indexes = collect(Schema::getIndexes('partner_sales'));
        $uniqueOrderItemIndex = $indexes->first(function (array $index) {
            return $index['unique'] && $index['columns'] === ['order_item_id'];
        });

        $this->assertNotNull($uniqueOrderItemIndex, 'Concurrent retries can create duplicate partner_sales rows.');
    }

    public function test_order_status_completion_path_syncs_partner_sales(): void
    {
        $method = $this->controllerMethod('OrderController', 'changeStatus', 'makeOrderInvoice');

        $this->assertStringContainsString(
            'syncPartnerSales',
            $method,
            'Completing an order through the status action does not create PartnerSale records.'
        );
    }

    public function test_order_cancel_or_refund_path_reverses_partner_sales(): void
    {
        $method = $this->controllerMethod('OrderController', 'changeStatus', 'makeOrderInvoice');

        $this->assertTrue(
            str_contains($method, 'PartnerSale') || str_contains($method, 'partner_sales'),
            'Order cancellation/refund has no PartnerSale reversal or cancellation handling.'
        );
    }

    public function test_partner_delete_does_not_cascade_historical_sales(): void
    {
        $migration = file_get_contents(database_path('migrations/2026_09_08_000002_create_partner_module_tables.php'));

        $this->assertStringNotContainsString(
            "foreign('partner_id')->references('id')->on('partners')->cascadeOnDelete()",
            $migration,
            'Deleting a partner would delete historical sales, payments and commission configuration.'
        );
    }

    public function test_partner_reports_require_partner_view_permission(): void
    {
        $method = $this->controllerMethod('PartnerController', 'reports', 'formData');

        $this->assertStringContainsString(
            "canView('view_partner')",
            $method,
            'Partner reports can be accessed with report permission alone and may expose partner data.'
        );
    }

    private function controllerMethod(string $controller, string $start, string $end): string
    {
        $path = app_path('Http/Controllers/' . $controller . '.php');
        $source = file_get_contents($path);
        $startPosition = strpos($source, 'public function ' . $start);
        $endPosition = strpos($source, 'public function ' . $end, $startPosition ?: 0);

        return substr($source, $startPosition ?: 0, ($endPosition ?: strlen($source)) - ($startPosition ?: 0));
    }
}
