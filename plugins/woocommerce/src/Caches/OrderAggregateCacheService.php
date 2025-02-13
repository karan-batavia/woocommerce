<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

use WC_Order;

/**
 * A service class to help with updates to the aggregate orders cache.
 */
class OrderAggregateCacheService {

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param OrderAggregateCache $order_aggregate_cache The aggregate order cache engine to use.
	 */
	final public function init() {
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
		$order_aggregate_cache = new OrderAggregateCache( $order->get_type() );
		$order_aggregate_cache->increment_count_for_status( 'wc-' . $order->get_status() );
	}

	/**
	 * Update the cache when an order is deleted.
	 *
	 * @param int      $order_id Order id.
	 * @param WC_Order $order The order.
	 */
	public function update_on_delete_order( $order_id, $order ) {
		$order_aggregate_cache = new OrderAggregateCache( $order->get_type() );
		$order_aggregate_cache->decrement_count_for_status( $order->get_status() );
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
		$order_aggregate_cache = new OrderAggregateCache( $order->get_type() );
		$order_aggregate_cache->decrement_count_for_status( 'wc-' . $previous_status );
		$order_aggregate_cache->increment_count_for_status( 'wc-' . $next_status );
	}
}
