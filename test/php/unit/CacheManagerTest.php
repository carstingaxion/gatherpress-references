<?php
/**
 * Unit tests for Cache_Manager class.
 *
 * @package GatherPress_References
 */

namespace GatherPress\References\Tests\Unit;

use GatherPress\References\Cache_Manager;
use WP_UnitTestCase;

/**
 * Class CacheManagerTest
 *
 * Tests the Cache_Manager class.
 *
 * @since 0.1.0
 */
class CacheManagerTest extends WP_UnitTestCase {

	/**
	 * Cache manager instance.
	 *
	 * @var Cache_Manager
	 */
	private Cache_Manager $cache_manager;

	/**
	 * Set up the test.
	 */
	public function set_up() {
		parent::set_up();
		$this->cache_manager = new Cache_Manager();
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down() {
		$this->cache_manager->clear_all();
		parent::tear_down();
	}

	/**
	 * Test that cache key is a non-empty string.
	 */
	public function test_get_cache_key_returns_string() {
		$key = $this->cache_manager->get_cache_key( 'gatherpress_event', 1, 2024, 'all' );

		$this->assertIsString( $key );
		$this->assertNotEmpty( $key );
	}

	/**
	 * Test that different parameters produce different cache keys.
	 */
	public function test_different_params_produce_different_keys() {
		$key1 = $this->cache_manager->get_cache_key( 'gatherpress_event', 1, 2024, 'all' );
		$key2 = $this->cache_manager->get_cache_key( 'gatherpress_event', 2, 2024, 'all' );
		$key3 = $this->cache_manager->get_cache_key( 'gatherpress_event', 1, 2023, 'all' );
		$key4 = $this->cache_manager->get_cache_key( 'post', 1, 2024, 'all' );

		$this->assertNotEquals( $key1, $key2 );
		$this->assertNotEquals( $key1, $key3 );
		$this->assertNotEquals( $key1, $key4 );
	}

	/**
	 * Test that same parameters produce the same cache key.
	 */
	public function test_same_params_produce_same_key() {
		$key1 = $this->cache_manager->get_cache_key( 'gatherpress_event', 1, 2024, 'all' );
		$key2 = $this->cache_manager->get_cache_key( 'gatherpress_event', 1, 2024, 'all' );

		$this->assertEquals( $key1, $key2 );
	}

	/**
	 * Test that get returns false when no cached data exists.
	 */
	public function test_get_returns_false_when_no_cache() {
		$result = $this->cache_manager->get( 'nonexistent_key_12345' );

		$this->assertFalse( $result );
	}

	/**
	 * Test that cache key starts with expected prefix.
	 */
	public function test_cache_key_has_expected_prefix() {
		$key = $this->cache_manager->get_cache_key( 'gatherpress_event', 1, 2024, 'all' );

		$this->assertStringStartsWith( 'gatherpress_refs_', $key );
	}

	/**
	 * Test setting and getting transient cache.
	 */
	public function test_set_and_get_cache() {
		$cache_key = $this->cache_manager->get_cache_key( 'gatherpress_event', 1, 2024, 'all' );
		$data      = array(
			'2024' => array(
				'gatherpress_client' => array( 'Client A', 'Client B' ),
			),
		);

		$this->cache_manager->set( $cache_key, $data );

		$result = $this->cache_manager->get( $cache_key );
		$this->assertEquals( $data, $result );
	}

	/**
	 * Test that clear_all removes cached data.
	 */
	public function test_clear_all_removes_cache() {
		$cache_key = $this->cache_manager->get_cache_key( 'gatherpress_event', 1, 2024, 'all' );
		$data      = array(
			'2024' => array(
				'gatherpress_client' => array( 'Client A' ),
			),
		);

		$this->cache_manager->set( $cache_key, $data );
		$this->assertNotFalse( $this->cache_manager->get( $cache_key ) );

		$this->cache_manager->clear_all();

		$this->assertFalse( $this->cache_manager->get( $cache_key ) );
	}
}
