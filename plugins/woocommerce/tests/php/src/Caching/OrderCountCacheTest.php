<?php
declare( strict_types = 1);

namespace Automattic\WooCommerce\Tests\Caching;

use WC_Helper_Order;
use Automattic\WooCommerce\Caches\OrderCountCache;
use Automattic\WooCommerce\Caching\CacheException;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * Class OrderCountCacheTest.
 */
class OrderCountCacheTest extends \WC_Unit_Test_Case {

	/**
	 * OrderCache instance.
	 *
	 * @var OrderCache
	 */
	private $order_cache;

	/**
	 * Setup test.
	 */
	public function setUp(): void {
		parent::setUp();
		$this->order_cache = new OrderCountCache();
		$this->order_cache->flush();
	}

	/**
	 * Create initial orders for testing.
	 */
	public function create_initial_orders() {
		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::FAILED );
		$order->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::PENDING );
		$order->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();

		$order = WC_Helper_Order::create_order();
		$order->set_status( OrderStatus::COMPLETED );
		$order->save();
	}

	/**
	 * Test that order counts get cached and can be accessed.
	 */
	public function test_order_counts_cached() {
		$this->create_initial_orders();
		$counts = OrderUtil::get_count_for_type( 'shop_order' );

		$this->assertTrue( $this->order_cache->is_cached( 'shop_order' ) );
		$this->assertEquals( 1, $this->order_cache->get( 'shop_order' )['wc-pending'] );
		$this->assertEquals( 2, $this->order_cache->get( 'shop_order' )['wc-completed'] );
		$this->assertEquals( 1, $this->order_cache->get( 'shop_order' )['wc-failed'] );
	}

	/**
	 * Test that invalid statuses throw exceptions.
	 */
	public function test_invalid_order_status() {
		$counts = array(
			'bad-status' => 1,
		);
		$this->expectException( CacheException::class );
		$this->order_cache->set( $counts, 'shop_order' );
	}

	/**
	 * Test that valid statuses get set.
	 */
	public function test_valid_statuses() {
		$counts = array(
			'wc-pending' => 5,
		);
		$this->order_cache->set( $counts, 'shop_order' );
		$this->assertEquals( 5, $this->order_cache->get( 'shop_order' )['wc-pending'] );
	}

	/**
	 * Test that invalid order types cannot be used.
	 */
	public function test_invalid_order_type() {
		
	}

	/**
	 * Test that valid order types can be used.
	 */
	public function test_valid_order_types() {

	}


}
