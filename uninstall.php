<?php
/**
 * Uninstall cleanup.
 *
 * Fires when the user deletes the plugin via the Plugins screen
 * (not on deactivate). Removes the plugin's own options and transients.
 *
 * @package AmitryProductCategorySlider
 */

// Bail if WordPress did not request uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Settings option.
delete_option( 'wcsp_settings' );

// Clear any query-cache transients we may have left behind. A direct
// query is appropriate here: there is no core API for bulk-deleting
// transients by prefix, and object caching is irrelevant during
// uninstall (the site is removing the plugin, not serving requests).
global $wpdb;

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- bulk transient cleanup on uninstall, no core API exists.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		$wpdb->esc_like( '_transient_wcsp_' ) . '%',
		$wpdb->esc_like( '_transient_timeout_wcsp_' ) . '%'
	)
);
