<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\ObjectCache;
use Automattic\WooCommerce\Enums\OrderStatus;
use Automattic\WooCommerce\Internal\DataStores\Orders\OrdersTableDataStore;
use Automattic\WooCommerce\Utilities\OrderUtil;

/**
 * A class to cache counts for various order statuses.
 */
class OrderCountCache extends ObjectCache {

	/**
	 * Default value for the duration of the objects in the cache, in seconds
	 * (may not be used depending on the cache engine used WordPress cache implementation).
	 *
	 * @var int
	 */
	protected $default_expiration = DAY_IN_SECONDS;

	/**
	 * Cache key.
	 *
	 * @var string
	 */
	private $cache_key;

	/**
	 * Get the cache key and prefix to use for Orders.
	 *
	 * @return string
	 */
	public function get_object_type(): string {
		return 'order-count';
	}

	/**
	 * Get the id of an object to be cached.
	 *
	 * @param array|object $object The object to be cached.
	 * @return int|string|null The id of the object, or null if it can't be determined.
	 */
	protected function get_object_id( $object ) {
		return null;
	}

	/**
	 * Validate an object before caching it.
	 *
	 * @param array|object $object The object to validate.
	 * @return string[]|null An array of error messages, or null if the object is valid.
	 */
	protected function validate( $array ): ?array {
		$valid_statuses   = $this->get_valid_statuses();
		$invalid_statuses = array_diff( array_keys( $array ), $valid_statuses );

		if ( ! empty( $invalid_statuses ) ) {
			return array( sprintf( __( '%s is not one of: %s', 'woocommerce' ), implode( ',', $invalid_statuses ), implode( ', ', $valid_statuses ) ) );
		}

		return null;
	}

	/**
	 * Get valid statuses.
	 *
	 * @return string[]
	 */
	private function get_valid_statuses() {
		return array_merge(
			array_keys( wc_get_order_statuses() ),
			array_keys( get_post_stati() ),
		);
	}
}
