<?php
/**
 * Integration tests for caching functionality.
 *
 * @package GatherPress_References
 */

namespace GatherPress\References\Tests\Integration;

use GatherPress\References\Cache_Manager;
use GatherPress\References\Plugin;
use WP_UnitTestCase;

/**
 * Class CacheIntegrationTest
 *
 * Tests caching operations within the WordPress environment.
 *
 * @since 0.1.0
 */
class CacheIntegrationTest extends WP_UnitTestCase {

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
		$this->cache_manager = Plugin::get_instance()->get_cache_manager();
	}

	/**
	 * Clean up after each test.
	 */
	public function tear_down() {
		$this->cache_manager->clear_all();
		parent::tear_down();
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

	/**
	 * Test cache expiration filter.
	 */
	public function test_cache_expiration_filter() {
		add_filter(
			'gatherpress_references_cache_expiration',
			function () {
				return 7200;
			} 
		);

		$filtered_cache_manager = new Cache_Manager();

		$cache_key = $filtered_cache_manager->get_cache_key( 'gatherpress_event', 1, 2024, 'all' );
		$this->assertIsString( $cache_key );

		remove_all_filters( 'gatherpress_references_cache_expiration' );
	}

	/**
	 * Test that publishing a post clears cache (if post type supports references).
	 */
	public function test_cache_cleared_on_post_status_change() {
		if ( ! post_type_exists( 'gatherpress_event' ) ) {
			$this->markTestSkipped( 'GatherPress event post type not registered.' );
		}

		$cache_key = $this->cache_manager->get_cache_key( 'gatherpress_event', 0, 0, 'all' );
		$data      = array(
			'2024' => array(
				'gatherpress_client' => array( 'Test Client' ),
			),
		);

		$this->cache_manager->set( $cache_key, $data );
		$this->assertNotFalse( $this->cache_manager->get( $cache_key ) );

		$post_id = self::factory()->post->create(
			array(
				'post_type'   => 'gatherpress_event',
				'post_status' => 'draft',
			) 
		);

		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_status' => 'publish',
			) 
		);

		$this->assertFalse( $this->cache_manager->get( $cache_key ) );
	}

	/**
	 * Test multiple cache entries are all cleared.
	 */
	public function test_clear_all_removes_multiple_entries() {
		$key1 = $this->cache_manager->get_cache_key( 'gatherpress_event', 1, 2024, 'all' );
		$key2 = $this->cache_manager->get_cache_key( 'gatherpress_event', 2, 2023, 'all' );

		$this->cache_manager->set( $key1, array( '2024' => array() ) );
		$this->cache_manager->set( $key2, array( '2023' => array() ) );

		$this->assertNotFalse( $this->cache_manager->get( $key1 ) );
		$this->assertNotFalse( $this->cache_manager->get( $key2 ) );

		$this->cache_manager->clear_all();

		$this->assertFalse( $this->cache_manager->get( $key1 ) );
		$this->assertFalse( $this->cache_manager->get( $key2 ) );
	}
}
