<?php
/**
 * Resolve paths inside the active drslon-site-core plugin directory.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @return string[] Absolute directory paths, active plugin first.
 */
function drslon_site_core_dir_candidates(): array {
	$candidates = array();

	if ( function_exists( 'get_plugins' ) && function_exists( 'is_plugin_active' ) ) {
		foreach ( array_keys( get_plugins() ) as $plugin_basename ) {
			if ( false === strpos( $plugin_basename, 'drslon-site-core' ) ) {
				continue;
			}

			if ( ! is_plugin_active( $plugin_basename ) ) {
				continue;
			}

			$candidates[] = WP_PLUGIN_DIR . '/' . dirname( $plugin_basename );
		}
	}

	$candidates[] = WP_PLUGIN_DIR . '/drslon-site-core-main';
	$candidates[] = WP_PLUGIN_DIR . '/drslon-site-core';

	return array_values( array_unique( $candidates ) );
}

/**
 * Locate a file relative to the site-core plugin root.
 */
function drslon_locate_site_core_file( string $relative_path ): ?string {
	$relative_path = ltrim( $relative_path, '/' );

	foreach ( drslon_site_core_dir_candidates() as $dir ) {
		$path = trailingslashit( $dir ) . $relative_path;

		if ( is_readable( $path ) ) {
			return $path;
		}
	}

	return null;
}

/**
 * @return string Theme asset URI.
 */
function drslon_theme_asset_uri( string $relative_path ): string {
	return trailingslashit( get_template_directory_uri() ) . ltrim( $relative_path, '/' );
}