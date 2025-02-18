<?php

declare( strict_types=1 );

namespace Automattic\WooCommerce\Caches;

use Automattic\WooCommerce\Caching\CacheException;
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

	/**
	 * Add an object to the cache, or update an already cached object.
	 *
	 * @param object|array    $object The object to be cached.
	 * @param int|string|null $id Id of the object to be cached, if null, get_object_id will be used to get it.
	 * @param int             $expiration Expiration of the cached data in seconds from the current time, or DEFAULT_EXPIRATION to use the default value.
	 * @return bool True on success, false on error.
	 * @throws CacheException Invalid parameter, or null id was passed and get_object_id returns null too.
	 */
	public function set( $object, $id = null, int $expiration = self::DEFAULT_EXPIRATION ): bool {
		$this->validate_order_type( $id );
		return parent::set( $object, $id, $expiration );
	}

	/**
	 * Retrieve a cached object, and if no object is cached with the given id,
	 * try to get one via get_from_datastore method or by supplying a callback and then cache it.
	 *
	 * If you want to provide a callable but still use the default expiration value,
	 * pass "ObjectCache::DEFAULT_EXPIRATION" as the second parameter.
	 *
	 * @param int|string    $id The id of the object to retrieve.
	 * @param int           $expiration Expiration of the cached data in seconds from the current time, used if an object is retrieved from datastore and cached.
	 * @param callable|null $get_from_datastore_callback Optional callback to get the object if it's not cached, it must return an object/array or null.
	 * @return object|array|null Cached object, or null if it's not cached and can't be retrieved from datastore or via callback.
	 * @throws CacheException Invalid id parameter.
	 */
	public function get( $id, int $expiration = self::DEFAULT_EXPIRATION, ?callable $get_from_datastore_callback = null ) {
		$this->validate_order_type( $id );
		return parent::get( $id, $expiration );
	}

	/**
	 * Throws an exception if an invalid order type is given.
	 *
	 * @throws CacheException Invalid id parameter.
	 */
	public function validate_order_type( $id ) {
		$valid_types = wc_get_order_types( 'order-count' );
		if ( ! in_array( $id, $valid_types, true ) ) {
			throw new CacheException( sprintf( "%s is not a valid order type.", $id ), $this, $id );
		}
	}
}
