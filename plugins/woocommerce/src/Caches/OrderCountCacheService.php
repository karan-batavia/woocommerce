<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

use WC_Order;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * A service class to help with updates to the aggregate orders cache.
 */
class OrderCountCacheService {

	/**
	 * OrderCountCache instance.
	 *
	 * @var OrderCountCache
	 */
	private $order_count_cache;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 */
	final public function init() {
		$this->order_count_cache = new OrderCountCache();
		add_action( 'woocommerce_new_order', array( $this, 'update_on_new_order' ), 10, 2 );
		add_action( 'woocommerce_before_delete_order', array( $this, 'update_on_delete_order' ), 10, 2 );
		add_action( 'woocommerce_order_status_changed', array( $this, 'update_on_order_status_changed' ), 10, 4 );
	}

	/**
	 * Update the cache when a new order is made.
	 *
	 * @param int      $order_id Order id.
	 * @param WC_Order $order The order.
	 */
	public function update_on_new_order( $order_id, $order ) {
		if ( ! $this->order_count_cache->is_cached( $order->get_type() ) ) {
			return;
		}

		$counts                                 = OrderUtil::get_count_for_type( $order->get_type() );
		$counts[ 'wc-' . $order->get_status() ] += 1;

		$this->order_count_cache->set( $counts, $order->get_type() );
	}

	/**
	 * Update the cache when an order is deleted.
	 *
	 * @param int      $order_id Order id.
	 * @param WC_Order $order The order.
	 */
	public function update_on_delete_order( $order_id, $order ) {
		if ( ! $this->order_count_cache->is_cached( $order->get_type() ) ) {
			return;
		}

		$counts                                  = OrderUtil::get_count_for_type( $order->get_type() );
		$counts[ 'wc-' . $order->get_status() ] -= 1;
		$this->order_count_cache->set( $counts, $order->get_type() );
	}

	/**
	 * Update the cache whenver an order status changes.
	 *
	 * @param int      $order_id Order id.
	 * @param string   $previous_status the old WooCommerce order status.
	 * @param string   $next_status the new WooCommerce order status.
	 * @param WC_Order $order The order.
	 */
	public function update_on_order_status_changed( $order_id, $previous_status, $next_status, $order ) {
		if ( ! $this->order_count_cache->is_cached( $order->get_type() ) ) {
			return;
		}

		$counts                              = OrderUtil::get_count_for_type( $order->get_type() );
		$counts[ 'wc-' . $previous_status ] -= 1;
		$counts[ 'wc-' . $next_status ]     += 1;
		$this->order_count_cache->set( $counts, $order->get_type() );
	}
}
