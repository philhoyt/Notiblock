<?php
/**
 * PHPUnit bootstrap for the Notiblock test suite.
 *
 * Designed to run inside wp-env's tests container, where the WordPress test
 * library lives at /wordpress-phpunit.
 *
 * @package Notiblock
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

$notiblock_tests_dir = getenv( 'WP_TESTS_DIR' );

if ( ! $notiblock_tests_dir ) {
	$notiblock_tests_dir = '/wordpress-phpunit';
}

if ( ! file_exists( $notiblock_tests_dir . '/includes/functions.php' ) ) {
	echo 'Could not find the WordPress test library at ' . $notiblock_tests_dir . PHP_EOL;
	echo 'Run tests with: npm run test:php' . PHP_EOL;
	exit( 1 );
}

require_once $notiblock_tests_dir . '/includes/functions.php';

/**
 * Loads the plugin before WordPress finishes booting.
 */
function notiblock_manually_load_plugin() {
	require dirname( __DIR__ ) . '/notiblock.php';
}
tests_add_filter( 'muplugins_loaded', 'notiblock_manually_load_plugin' );

require $notiblock_tests_dir . '/includes/bootstrap.php';
