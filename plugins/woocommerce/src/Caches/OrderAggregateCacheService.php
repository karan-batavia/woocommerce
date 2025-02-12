<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

/**
 * A service class to help with updates to the aggregate orders cache.
 */
class OrderAggregateCacheService {

	/**
	 * The orders cache to use.
	 *
	 * @var OrderCache
	 */
	private $order_aggregate_cache;

	/**
	 * Class initialization, invoked by the DI container.
	 *
	 * @internal
	 * @param OrderAggregateCache $order_aggregate_cache The aggregate order cache engine to use.
	 */
	final public function init( OrderAggregateCache $order_aggregate_cache ) {
		$this->order_aggregate_cache = $order_aggregate_cache;
		add_action( 'woocommerce_new_order', array( $this, 'update_on_new_order' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'update_on_order_status_changed' ), 10, 3 );
	}

	/**
	 * Update the cache when a new order is made.
	 */
	public function update_on_new_order() {
		// Always update if new orders.
	}

	/**
	 * Update the cache whenver an order status changes.
	 *
	 * @param int    $order_id Order id.
	 * @param string $previous_status the old WooCommerce order status.
	 * @param string $next_status the new WooCommerce order status.
	 */
	public function update_on_order_status_changed( $order_id, $previous_status, $next_status ) {
		$this->order_aggregate_cache->decrement_count_for_status( 'wc-' . $previous_status );
		$this->order_aggregate_cache->increment_count_for_status( 'wc-' . $next_status );
	}
}
