<?php
/**
 * Configuration Manager class
 *
 * Handles post type configuration retrieval and validation.
 * Centralized access to post type support configurations.
 *
 * @package GatherPress_References
 */

namespace GatherPress\References;

defined( 'ABSPATH' ) || exit;

/**
 * Configuration Manager
 *
 * Handles post type configuration retrieval and validation.
 * Centralized access to post type support configurations.
 *
 * @since 0.1.0
 */
class Config_Manager {
	/**
	 * Get configuration for a specific post type
	 *
	 * @since 0.1.0
	 * @param string $post_type Post type slug.
	 * @return ?array{ref_tax: string, ref_types: array<int, string>} Configuration array or null.
	 */
	public function get_config( string $post_type ): ?array {
		if ( ! post_type_supports( $post_type, 'gatherpress-references' ) ) {
			return null;
		}

		$support = get_all_post_type_supports( $post_type );

		if ( ! isset( $support['gatherpress-references'] ) ) {
			return null;
		}

		$raw = $support['gatherpress-references'];

		// Ensure it's a non-empty array with index 0.
		if ( ! is_array( $raw ) || ! isset( $raw[0] ) || ! is_array( $raw[0] ) ) {
			return null;
		}

		$config = $raw[0];

		if ( ! isset( $config['ref_tax'] ) || ! is_string( $config['ref_tax'] ) ) {
			return null;
		}

		if ( ! isset( $config['ref_types'] ) || ! is_array( $config['ref_types'] ) ) {
			return null;
		}

		// Force correct type: array<int, string>.
		$ref_types = array_values(
			array_filter(
				$config['ref_types'],
				static fn( $v ): bool => is_string( $v )
			)
		);

		return array(
			'ref_tax'   => $config['ref_tax'],
			'ref_types' => $ref_types,
		);
	}

	/**
	 * Get all configurations from post types with support
	 *
	 * @since 0.1.0
	 * @return array<string, array{ref_tax: string, ref_types: array<int, string>}> Array of post_type => config.
	 */
	public function get_all_configs(): array {
		$post_types = get_post_types_by_support( 'gatherpress-references' );
		$configs    = array();

		if ( empty( $post_types ) ) {
			return $configs;
		}

		foreach ( $post_types as $post_type ) {
			$config = $this->get_config( $post_type );
			if ( $config ) {
				$configs[ $post_type ] = $config;
			}
		}

		return $configs;
	}

	/**
	 * Get all unique taxonomies from all configurations
	 *
	 * @since 0.1.0
	 * @return array<int, string> Array of unique taxonomy slugs.
	 */
	public function get_all_taxonomies(): array {
		$configs    = $this->get_all_configs();
		$taxonomies = array();

		foreach ( $configs as $config ) {
			if ( ! empty( $config['ref_tax'] ) ) {
				$taxonomies[] = $config['ref_tax'];
			}
			if ( ! empty( $config['ref_types'] ) ) {
				$taxonomies = array_merge( $taxonomies, $config['ref_types'] );
			}
		}

		return array_unique( $taxonomies );
	}

	/**
	 * Check if block should be registered
	 *
	 * @since 0.1.0
	 * @return bool True if block should be registered.
	 */
	public function should_register_block(): bool {
		$configs = $this->get_all_configs();

		if ( empty( $configs ) ) {
			return false;
		}

		// Check if at least one config has both ref_tax and non-empty ref_types.
		foreach ( $configs as $config ) {
			if ( ! empty( $config['ref_tax'] ) &&
				! empty( $config['ref_types'] )
			) {
				return true;
			}
		}

		return false;
	}
}
