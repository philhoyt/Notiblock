<?php
/**
 * Uninstall routine for Notiblock.
 *
 * Removes the plugin's stored settings. The option is written with autoload
 * enabled, so leaving it behind would keep loading on every page request for
 * the life of the site.
 *
 * @package Notiblock
 */

// Exit if WordPress did not invoke this during an uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

const NOTIBLOCK_UNINSTALL_OPTION = 'notiblock_global_notice';

if ( is_multisite() ) {
	// Network-wide mode stores a single shared option.
	delete_site_option( NOTIBLOCK_UNINSTALL_OPTION );

	/*
	 * Per-site mode stores one option per site, so every site in the network
	 * has to be visited. number => 0 removes the default 100-site limit.
	 */
	$notiblock_site_ids = get_sites(
		array(
			'fields'                 => 'ids',
			'number'                 => 0,
			'update_site_meta_cache' => false,
		)
	);

	foreach ( $notiblock_site_ids as $notiblock_site_id ) {
		switch_to_blog( $notiblock_site_id );
		delete_option( NOTIBLOCK_UNINSTALL_OPTION );
		restore_current_blog();
	}
} else {
	delete_option( NOTIBLOCK_UNINSTALL_OPTION );
}
