<?php
/**
 * Taxonomy Manager class
 *
 * Handles registration of custom taxonomies based on post type configurations.
 *
 * @package GatherPress_References
 */

namespace GatherPress\References;

defined( 'ABSPATH' ) || exit;

/**
 * Taxonomy Manager
 *
 * Handles registration of custom taxonomies based on post type configurations.
 *
 * @since 0.1.0
 */
class Taxonomy_Manager {
	/**
	 * Config manager instance
	 *
	 * @var Config_Manager
	 */
	private Config_Manager $config_manager;

	/**
	 * Constructor
	 *
	 * @since 0.1.0
	 * @param Config_Manager $config_manager Config manager instance.
	 */
	public function __construct( Config_Manager $config_manager ) {
		$this->config_manager = $config_manager;
	}

	/**
	 * Register all taxonomies
	 *
	 * @since 0.1.0
	 * @return void
	 */
	public function register_taxonomies(): void {
		$configs = $this->config_manager->get_all_configs();

		if ( empty( $configs ) ) {
			return;
		}

		foreach ( $configs as $post_type => $config ) {
			// Register reference taxonomy.
			if ( ! empty( $config['ref_tax'] ) &&
				$config['ref_tax'] === '_gatherpress_play' &&
				! taxonomy_exists( $config['ref_tax'] ) ) {
				$this->register_reference_taxonomy( $post_type );
			}

			// Register reference type taxonomies.
			if ( ! empty( $config['ref_types'] ) ) {
				foreach ( $config['ref_types'] as $ref_type ) {
					if ( taxonomy_exists( $ref_type ) ) {
						continue;
					}

					$this->register_type_taxonomy( $ref_type, $post_type );
				}
			}
		}
	}

	/**
	 * Register reference taxonomy
	 *
	 * @since 0.1.0
	 * @param string $post_type Post type to associate with.
	 * @return void
	 */
	private function register_reference_taxonomy( string $post_type ): void {
		$labels = array(
			'name'              => __( 'Productions', 'gatherpress-references' ),
			'singular_name'     => __( 'Production', 'gatherpress-references' ),
			'search_items'      => __( 'Search Productions', 'gatherpress-references' ),
			'all_items'         => __( 'All Productions', 'gatherpress-references' ),
			'parent_item'       => __( 'Parent Production', 'gatherpress-references' ),
			'parent_item_colon' => __( 'Parent Production:', 'gatherpress-references' ),
			'edit_item'         => __( 'Edit Production', 'gatherpress-references' ),
			'update_item'       => __( 'Update Production', 'gatherpress-references' ),
			'add_new_item'      => __( 'Add New Production', 'gatherpress-references' ),
			'new_item_name'     => __( 'New Production Name', 'gatherpress-references' ),
			'menu_name'         => __( 'Productions', 'gatherpress-references' ),
		);

		$args = array(
			'labels'             => $labels,
			'hierarchical'       => true,
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_admin_column'  => true,
			'query_var'          => true,
			'rewrite'            => false,
			'show_in_rest'       => true,
		);

		register_taxonomy( '_gatherpress_play', array( $post_type ), $args );
	}

	/**
	 * Register a reference type taxonomy
	 *
	 * @since 0.1.0
	 * @param string $taxonomy  Taxonomy slug.
	 * @param string $post_type Post type to associate with.
	 * @return void
	 */
	private function register_type_taxonomy( string $taxonomy, string $post_type ): void {
		$taxonomy_config = $this->get_taxonomy_config( $taxonomy );

		if ( ! $taxonomy_config ) {
			return;
		}

		$args = array(
			'labels'             => $taxonomy_config['labels'],
			'hierarchical'       => false,
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_admin_column'  => false,
			'query_var'          => true,
			'rewrite'            => false,
			'show_in_rest'       => true,
		);

		register_taxonomy( $taxonomy, array( $post_type ), $args );
	}

	/**
	 * Get taxonomy configuration
	 *
	 * @since 0.1.0
	 * @param string $taxonomy Taxonomy slug.
	 * @return ?array{labels: array<string, string>} Configuration or null.
	 */
	private function get_taxonomy_config( string $taxonomy ): ?array {
		$configs = array(
			'gatherpress_client'   => array(
				'labels' => array(
					'name'          => __( 'Clients', 'gatherpress-references' ),
					'singular_name' => __( 'Client', 'gatherpress-references' ),
					'search_items'  => __( 'Search Clients', 'gatherpress-references' ),
					'all_items'     => __( 'All Clients', 'gatherpress-references' ),
					'edit_item'     => __( 'Edit Client', 'gatherpress-references' ),
					'update_item'   => __( 'Update Client', 'gatherpress-references' ),
					'add_new_item'  => __( 'Add New Client', 'gatherpress-references' ),
					'new_item_name' => __( 'New Client Name', 'gatherpress-references' ),
					'menu_name'     => __( 'Clients', 'gatherpress-references' ),
				),
			),
			'gatherpress_festival' => array(
				'labels' => array(
					'name'          => __( 'Festivals', 'gatherpress-references' ),
					'singular_name' => __( 'Festival', 'gatherpress-references' ),
					'search_items'  => __( 'Search Festivals', 'gatherpress-references' ),
					'all_items'     => __( 'All Festivals', 'gatherpress-references' ),
					'edit_item'     => __( 'Edit Festival', 'gatherpress-references' ),
					'update_item'   => __( 'Update Festival', 'gatherpress-references' ),
					'add_new_item'  => __( 'Add New Festival', 'gatherpress-references' ),
					'new_item_name' => __( 'New Festival Name', 'gatherpress-references' ),
					'menu_name'     => __( 'Festivals', 'gatherpress-references' ),
				),
			),
			'gatherpress_award'    => array(
				'labels' => array(
					'name'          => __( 'Awards', 'gatherpress-references' ),
					'singular_name' => __( 'Award', 'gatherpress-references' ),
					'search_items'  => __( 'Search Awards', 'gatherpress-references' ),
					'all_items'     => __( 'All Awards', 'gatherpress-references' ),
					'edit_item'     => __( 'Edit Award', 'gatherpress-references' ),
					'update_item'   => __( 'Update Award', 'gatherpress-references' ),
					'add_new_item'  => __( 'Add New Award', 'gatherpress-references' ),
					'new_item_name' => __( 'New Award Name', 'gatherpress-references' ),
					'menu_name'     => __( 'Awards', 'gatherpress-references' ),
				),
			),
		);

		return $configs[ $taxonomy ] ?? null;
	}
}
