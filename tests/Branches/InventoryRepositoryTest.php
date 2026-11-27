<?php

use PHPUnit\Framework\TestCase;
use RMM\Branches\InventoryRepository;

class InventoryRepositoryTest extends TestCase {

    protected function setUp(): void {
        global $wpdb;
        $wpdb = new RMM_Test_Wpdb();
        $GLOBALS['rmm_test_actions'] = [];

        $cache = new ReflectionProperty(InventoryRepository::class, 'cache');
        $cache->setAccessible(true);
        $cache->setValue([]);
    }

    public function test_upsert_rejects_missing_product_or_branch_ids() {
        $this->assertFalse(InventoryRepository::upsert([
            'product_id' => 0,
            'branch_id' => 4,
            'regular_price' => 120,
        ]));

        $this->assertFalse(InventoryRepository::upsert([
            'product_id' => 12,
            'branch_id' => 0,
            'regular_price' => 120,
        ]));

        global $wpdb;
        $this->assertCount(0, $wpdb->rows);
    }

    public function test_upsert_inserts_branch_price_and_stock_values() {
        $this->assertTrue(InventoryRepository::upsert([
            'product_id' => 12,
            'branch_id' => 4,
            'regular_price' => '125000',
            'sale_price' => '110000',
            'stock_status' => 'instock',
            'is_available' => true,
        ]));

        global $wpdb;
        $row = $wpdb->rows['12:4'];

        $this->assertSame(125000.0, (float) $row->regular_price);
        $this->assertSame(110000.0, (float) $row->sale_price);
        $this->assertSame('instock', $row->stock_status);
        $this->assertSame(1, (int) $row->is_available);
    }

    public function test_partial_upsert_preserves_omitted_price_and_stock_fields() {
        InventoryRepository::upsert([
            'product_id' => 12,
            'branch_id' => 4,
            'regular_price' => 125000,
            'sale_price' => 110000,
            'stock_status' => 'outofstock',
            'is_available' => false,
        ]);

        $this->assertTrue(InventoryRepository::upsert([
            'product_id' => 12,
            'branch_id' => 4,
            'regular_price' => 130000,
        ]));

        global $wpdb;
        $row = $wpdb->rows['12:4'];

        $this->assertSame(130000.0, (float) $row->regular_price);
        $this->assertSame(110000.0, (float) $row->sale_price);
        $this->assertSame('outofstock', $row->stock_status);
        $this->assertSame(0, (int) $row->is_available);
    }

    public function test_stock_toggle_does_not_overwrite_branch_prices() {
        InventoryRepository::upsert([
            'product_id' => 12,
            'branch_id' => 4,
            'regular_price' => 125000,
            'sale_price' => 110000,
        ]);

        $this->assertTrue(InventoryRepository::toggle_stock(12, 4, 'outofstock'));

        global $wpdb;
        $row = $wpdb->rows['12:4'];

        $this->assertSame(125000.0, (float) $row->regular_price);
        $this->assertSame(110000.0, (float) $row->sale_price);
        $this->assertSame('outofstock', $row->stock_status);
        $this->assertSame(0, (int) $row->is_available);
    }

    public function test_price_update_does_not_overwrite_branch_stock() {
        InventoryRepository::upsert([
            'product_id' => 12,
            'branch_id' => 4,
            'regular_price' => 125000,
            'stock_status' => 'outofstock',
            'is_available' => false,
        ]);

        $this->assertTrue(InventoryRepository::update_price(12, 4, 140000, 0));

        global $wpdb;
        $row = $wpdb->rows['12:4'];

        $this->assertSame(140000.0, (float) $row->regular_price);
        $this->assertSame(0.0, (float) $row->sale_price);
        $this->assertSame('outofstock', $row->stock_status);
        $this->assertSame(0, (int) $row->is_available);
    }

    public function test_same_product_can_have_independent_records_per_branch() {
        InventoryRepository::upsert([
            'product_id' => 12,
            'branch_id' => 4,
            'regular_price' => 100000,
        ]);
        InventoryRepository::upsert([
            'product_id' => 12,
            'branch_id' => 9,
            'regular_price' => 150000,
        ]);

        global $wpdb;
        $this->assertSame(100000.0, (float) $wpdb->rows['12:4']->regular_price);
        $this->assertSame(150000.0, (float) $wpdb->rows['12:9']->regular_price);
    }

    public function test_successful_write_dispatches_inventory_updated_action() {
        $this->assertTrue(InventoryRepository::upsert([
            'product_id' => 12,
            'branch_id' => 4,
            'regular_price' => 100000,
        ]));

        $this->assertNotEmpty($GLOBALS['rmm_test_actions']);
        $this->assertSame('rmm_branch_inventory_updated', $GLOBALS['rmm_test_actions'][0][0]);
        $this->assertSame([12, 4], $GLOBALS['rmm_test_actions'][0][1]);
    }
}
