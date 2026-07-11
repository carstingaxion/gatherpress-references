<?php
/**
 * Plugin Name:       GatherPress References
 * Description:       Display references such as clients, festivals and awards in a structured, chronological format.
 * Version:           0.2.3
 * Requires at least: 6.1
 * Requires PHP:      7.4
 * Requires Plugins:  gatherpress
 * Author:            Carsten Bach
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       gatherpress-references
 * Domain Path:       /languages
 *
 * @package GatherPress_References
 */

namespace GatherPress\References;

defined( 'ABSPATH' ) || exit;

require_once __DIR__ . '/includes/classes/class-cache-manager.php';
require_once __DIR__ . '/includes/classes/class-config-manager.php';
require_once __DIR__ . '/includes/classes/class-data-organizer.php';
require_once __DIR__ . '/includes/classes/class-query-builder.php';
require_once __DIR__ . '/includes/classes/class-taxonomy-manager.php';

/**
 * Main Plugin Class
 *
 * Orchestrates all plugin components and manages WordPress integration.
 *
 * @since 0.1.0
 */
class Plugin {
	/**
	 * Singleton instance
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Config manager
	 *
	 * @var Config_Manager
	 */
	private Config_Manager $config_manager;

	/**
	 * Cache manager
	 *
	 * @var Cache_Manager
	 */
	private Cache_Manager $cache_manager;

	/**
	 * Taxonomy manager
	 *
	 * @var Taxonomy_Manager
	 */
	private Taxonomy_Manager $taxonomy_manager;

	/**
	 * Query builder
	 *
	 * @var Query_Builder
	 */
	private Query_Builder $query_builder;

	/**
	 * Data organizer
	 *
	 * @var Data_Organizer
	 */
	private Data_Organizer $data_organizer;

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 */
	private function __construct() {
		$this->init_components();
		$this->init_hooks();
	}

	/**
	 * Get singleton instance
	 *
	 * @since 0.1.0
	 * @return Plugin Instance.
	 */
	public static function get_instance(): Plugin {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Initialize components
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function init_components(): void {
		$this->config_manager   = new Config_Manager();
		$this->cache_manager    = new Cache_Manager();
		$this->taxonomy_manager = new Taxonomy_Manager( $this->config_manager );
		$this->query_builder    = new Query_Builder( $this->config_manager );
		$this->data_organizer   = new Data_Organizer( $this->config_manager );
	}

	/**
	 * Initialize hooks
	 *
	 * @since 0.1.0
	 * @return void
	 */
	private function init_hooks(): void {
		add_action( 'registered_post_type_gatherpress_event', array( $this, 'register_taxonomies' ) );
		add_action( 'registered_post_type_gatherpress_event', array( $this, 'register_block' ) );
		add_action( 'init', array( $this, 'load_textdomain' ) );

		// Cache invalidation hooks.
		add_action( 'transition_post_status', array( $this, 'clear_cache_on_status_change' ), 10, 3 );
		add_action( 'delete_term', array( $this, 'clear_cache_on_term_change' ), 10, 3 );
		add_action( 'set_object_terms', array( $this, 'clear_cache_on_term_relationship' ), 10, 4 );
	}

	/**
	 * Loads the plugin text domain for translations.
	 *
	 * @since 0.1.0
	 *
	 * @return void
	 */
	public function load_textdomain(): void {
		load_plugin_textdomain(
			'gatherpress-references',
			false,
			dirname( plugin_basename( __FILE__ ) ) . '/languages'
		);
	}

	/**
	 * Register taxonomies
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_taxonomies(): void {
		$this->taxonomy_manager->register_taxonomies();
	}

	/**
	 * Register block
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_block(): void {
		if ( ! $this->config_manager->should_register_block() ) {
			return;
		}

		register_block_type( __DIR__ . '/build/' );
	}

	/**
	 * Clear cache on status change
	 *
	 * @since 0.1.0
	 * @param string   $new_status New status.
	 * @param string   $old_status Old status.
	 * @param \WP_Post $post       Post object.
	 * @return void
	 */
	public function clear_cache_on_status_change( string $new_status, string $old_status, \WP_Post $post ): void {

		if ( ! post_type_supports( $post->post_type, 'gatherpress-references' ) ) {
			return;
		}

		// @todo #48 if GatherPress event, only if past event
		// ...

		if ( ( 'publish' === $new_status || 'publish' === $old_status ) && $new_status !== $old_status ) {
			$this->cache_manager->clear_all();
		}
	}

	/**
	 * Clear cache on term change
	 *
	 * @since 0.1.0
	 * @param int    $term_id  Term ID.
	 * @param int    $tt_id    Term taxonomy ID.
	 * @param string $taxonomy Taxonomy.
	 * @return void
	 */
	public function clear_cache_on_term_change( int $term_id, int $tt_id, string $taxonomy ): void {
		$all_taxonomies = $this->config_manager->get_all_taxonomies();

		if ( in_array( $taxonomy, $all_taxonomies, true ) ) {
			$this->cache_manager->clear_all();
		}
	}

	/**
	 * Clear cache on term relationship
	 *
	 * @since 0.1.0
	 * @param int               $object_id Object ID.
	 * @param array<int|string> $terms     An array of object term IDs or slugs.
	 * @param int[]             $tt_ids    An array of term taxonomy IDs.
	 * @param string            $taxonomy  Taxonomy slug.
	 * @return void
	 */
	public function clear_cache_on_term_relationship( int $object_id, array $terms, array $tt_ids, string $taxonomy ): void {
		$post = get_post( $object_id );

		if ( ! $post || ! post_type_supports( $post->post_type, 'gatherpress-references' ) || $post->post_status !== 'publish' ) {
			return;
		}

		// if GatherPress event, only if past event
		// ...
		// @todo #48 Add safeguard to only delete cache, when past events are related.

		$all_taxonomies = $this->config_manager->get_all_taxonomies();

		if ( ! in_array( $taxonomy, $all_taxonomies, true ) ) {
			return;
		}

		$this->cache_manager->clear_all();
	}

	/**
	 * Get cache manager
	 *
	 * @since 0.1.0
	 * @return Cache_Manager Cache manager instance.
	 */
	public function get_cache_manager(): Cache_Manager {
		return $this->cache_manager;
	}

	/**
	 * Get query builder
	 *
	 * @since 0.1.0
	 * @return Query_Builder Query builder instance.
	 */
	public function get_query_builder(): Query_Builder {
		return $this->query_builder;
	}

	/**
	 * Get data organizer
	 *
	 * @since 0.1.0
	 * @return Data_Organizer Data organizer instance.
	 */
	public function get_data_organizer(): Data_Organizer {
		return $this->data_organizer;
	}
}

/**
 * Register post type support for GatherPress References
 *
 * @since 0.1.0
 * @return void
 */
function register_post_type_support(): void {
	$config = array(
		'ref_tax'   => '_gatherpress_play',
		'ref_types' => array( 'gatherpress_client', 'gatherpress_festival', 'gatherpress_award' ),
	);

	add_post_type_support( 'gatherpress_event', 'gatherpress-references', $config );
}
add_action( 'registered_post_type_gatherpress_event', __NAMESPACE__ . '\register_post_type_support', 9 );

/**
 * Plugin activation
 *
 * @since 0.1.0
 * @return void
 */
function gatherpress_references_activate(): void {
	// Future activation tasks.
}
register_activation_hook( __FILE__, __NAMESPACE__ . '\gatherpress_references_activate' );

/**
 * Plugin deactivation
 *
 * @since 0.1.0
 * @return void
 */
function gatherpress_references_deactivate(): void {
	Plugin::get_instance()->get_cache_manager()->clear_all();
}
register_deactivation_hook( __FILE__, __NAMESPACE__ . '\gatherpress_references_deactivate' );

/**
 * Plugin uninstall
 *
 * @since 0.1.0
 * @return void
 */
function gatherpress_references_uninstall(): void {
	global $wpdb;

	if ( ! $wpdb instanceof \wpdb ) {
		return;
	}

	$config_manager = new Config_Manager();
	$taxonomies     = $config_manager->get_all_taxonomies();

	foreach ( $taxonomies as $taxonomy ) {
		$terms = get_terms(
			array(
				'taxonomy'   => $taxonomy,
				'hide_empty' => false,
				'fields'     => 'ids',
			)
		);

		if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
			foreach ( $terms as $term_id ) {
				wp_delete_term( $term_id, $taxonomy );
			}
		}

		$wpdb->query( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			// @phpstan-ignore-next-line
			$wpdb->prepare(
				// @phpstan-ignore-next-line
				"DELETE FROM {$wpdb->term_taxonomy} WHERE taxonomy = %s",
				$taxonomy
			)
		);
	}

	Plugin::get_instance()->get_cache_manager()->clear_all();
}
register_uninstall_hook( __FILE__, __NAMESPACE__ . '\gatherpress_references_uninstall' );

// Initialize plugin.
Plugin::get_instance();
