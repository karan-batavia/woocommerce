<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * A class to cache aggregates from orders.
 */
class OrderAggregateCache {

	/**
	 * Cache key.
	 *
	 * @var string
	 */
	private $cache_key;

	/**
	 * Order type.
	 *
	 * @var string
	 */
	private $order_type;

	/**
	 * Group name.
	 *
	 * @var string
	 */
	private $group = 'counts';

	/**
	 * Constructor.
	 *
	 * @param string $order_type Order type.
	 */
	public function __construct( $order_type = 'order' ) {
		$valid_types = wc_get_order_types( 'order-count' );
		if ( ! in_array( $order_type, $valid_types, true ) ) {
			throw new \Exception( sprintf( __( '%s is not a valid order type.  Order type must be one of: %s', 'woocommerce' ), $order_type, implode( ',', $valid_types ) ) );
		}

		$this->order_type = $order_type;
		$this->cache_key  = \WC_Cache_Helper::get_cache_prefix( 'orders' ) . 'order-count-' . $order_type;
	}

	/**
	 * Get the aggregate by identifier.
	 *
	 * @param string|string[] $status The order status to count.
	 * @return int
	 * @throws \Exception Exception thrown if status it not valid.
	 */
	public function get_count_by_status( $status ): int {
		$status         = (array) $status;
		$valid_statuses = $this->get_valid_statuses();

		if ( ! empty( array_diff( $status, $valid_statuses ) ) ) {
			throw new \Exception( sprintf( __( '%s is not a valid %s status.', 'woocommerce' ), implode( ', ', $status ), $this->order_type ) );
		}

		$count = $this->get_count();

		return array_sum( array_intersect_key( $count, array_flip( $status ) ) );
	}

	/**
	 * Counts number of orders of a given type.
	 *
	 * @since 8.7.0
	 *
	 * @return array<string,int> Array of order counts indexed by order type.
	 */
	public function get_count() {
		global $wpdb;

		$count_per_status = wp_cache_get( $this->cache_key, $this->group );

		if ( false === $count_per_status ) {
			if ( OrderUtil::custom_orders_table_usage_is_enabled() ) {
				// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
				$results = $wpdb->get_results(
					$wpdb->prepare(
						'SELECT `status`, COUNT(*) AS `count` FROM ' . OrderUtil::get_table_for_orders() . ' WHERE `type` = %s GROUP BY `status`',
						$this->order_type
					),
					ARRAY_A
				);
				// phpcs:enable

				$count_per_status = array_map( 'absint', array_column( $results, 'count', 'status' ) );
			} else {
				$count_per_status = (array) wp_count_posts( $order_type );
			}
			
			// Make sure all order statuses are included just in case.
			$count_per_status = array_merge(
				array_fill_keys( $this->get_valid_statuses(), 0 ),
				$count_per_status
			);

			wp_cache_set( $this->cache_key, $count_per_status, $this->group, DAY_IN_SECONDS );
		}

		return $count_per_status;
	}

	/**
	 * Get valid statuses.
	 *
	 * @return string[]
	 */
	private function get_valid_statuses() {
		$legacy_statuses = array(
			OrderStatus::AUTO_DRAFT,
			OrderStatus::DRAFT,
			OrderStatus::TRASH,
		);

		return array_merge(
			array_keys( wc_get_order_statuses() ),
			$legacy_statuses
		);
	}

	/**
	 * Update the visible order count cache.
	 *
	 * @param integer $count The count of which to update with.
	 * @param string  $status The identifier of the aggregate.
	 * @return void
	 */
	public function set_count_for_status( $count, $status ): void {
		$valid_statuses = $this->get_valid_statuses();

		if ( ! in_array( $status, $valid_statuses ) ) {
			throw new \Exception( sprintf( __( '%s is not a valid %s status.', 'woocommerce' ), implode( ', ', $status ), $this->order_type ) );
		}

		$count            = $this->get_count();
		$count[ $status ] = $count;

		wp_cache_set( $this->cache_key, $count, $this->group, DAY_IN_SECONDS );
	}

	/**
	 * Increment a count by 1 for a given status.
	 *
	 * @param string  $status The identifier of the aggregate.
	 * @return void
	 */
	public function increment_count_for_status( $status ) {
		$count = $this->get_count_by_status( $status );
		$this->set_count_for_status( $count + 1, $status );
	}

	/**
	 * Increment a count by 1 for a given status.
	 *
	 * @param string  $status The identifier of the aggregate.
	 * @return void
	 */
	public function decrement_count_for_status( $status ) {
		$count = $this->get_count_by_status( $status );
		$this->set_count_for_status( $count - 1, $status );
	}
}
