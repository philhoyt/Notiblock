<?php
/**
 * Plugin Name:       Notiblock
 * Plugin URI:        https://github.com/philhoyt/Notiblock
 * Description:       Conditional notification blocks with dashboard widget configuration.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Phil Hoyt
 * Author URI:        https://philhoyt.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       notiblock
 *
 * @package Notiblock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Plugin Update Checker — GitHub release-based auto-updates.
$notiblock_puc = plugin_dir_path( __FILE__ ) . 'lib/plugin-update-checker/plugin-update-checker.php';
if ( file_exists( $notiblock_puc ) ) {
	require_once $notiblock_puc;

	$notiblock_update_checker = YahnisElsts\PluginUpdateChecker\v5\PucFactory::buildUpdateChecker(
		'https://github.com/philhoyt/Notiblock/',
		__FILE__,
		'notiblock'
	);
	$notiblock_update_checker->getVcsApi()->enableReleaseAssets();
}

/**
 * Determines if Notiblock should use network-wide settings in multisite.
 *
 * Can be overridden by defining NOTIBLOCK_NETWORK_WIDE constant:
 * define( 'NOTIBLOCK_NETWORK_WIDE', true ); // Use network-wide settings
 *
 * Or via filter:
 * add_filter( 'notiblock_use_network_settings', '__return_true' );
 *
 * @return bool True to use network-wide settings, false for per-site settings.
 */
function notiblock_use_network_settings() {
	// Check if multisite is active.
	if ( ! is_multisite() ) {
		return false;
	}

	// Allow constant override.
	if ( defined( 'NOTIBLOCK_NETWORK_WIDE' ) ) {
		return (bool) constant( 'NOTIBLOCK_NETWORK_WIDE' );
	}

	// Allow filter override (defaults to false for per-site settings).
	return apply_filters( 'notiblock_use_network_settings', false );
}

/**
 * Registers a custom block category for Notiblock blocks.
 *
 * @param array $categories Array of block categories.
 * @return array Modified array of block categories.
 */
function notiblock_register_block_category( $categories ) {
	return array_merge(
		array(
			array(
				'slug'  => 'notiblock',
				'title' => __( 'Notiblock', 'notiblock' ),
				'icon'  => 'megaphone',
			),
		),
		$categories
	);
}
add_filter( 'block_categories_all', 'notiblock_register_block_category', 10, 1 );

/**
 * Retrieves and validates the Notiblock global notice settings.
 *
 * Uses static caching to avoid multiple database queries within the same request.
 * WordPress also caches get_option()/get_site_option() calls automatically, but this adds
 * an extra layer of efficiency for the processing/validation step.
 *
 * In multisite installations, can use either:
 * - Per-site settings (default): Each site has its own notification settings
 * - Network-wide settings: All sites share the same notification settings
 *
 * To enable network-wide settings, define NOTIBLOCK_NETWORK_WIDE constant or use the
 * 'notiblock_use_network_settings' filter.
 *
 * @param bool $force_refresh Optional. If true, bypasses static cache. Default false.
 * @return array Settings array with 'content', 'start_date', 'end_date', and 'always_show' keys.
 */
function notiblock_get_settings( $force_refresh = false ) {
	static $cached_settings = null;

	// Return cached settings if available (same request) and not forcing refresh.
	if ( ! $force_refresh && null !== $cached_settings ) {
		return $cached_settings;
	}

	$defaults = array(
		'content'     => '',
		'start_date'  => '',
		'end_date'    => '',
		'always_show' => false,
	);

	// Use network-wide or per-site option based on configuration.
	if ( notiblock_use_network_settings() ) {
		$settings = get_site_option( 'notiblock_global_notice', $defaults );
	} elseif ( is_multisite() ) {
		$settings = get_option( 'notiblock_global_notice', $defaults );
	} else {
		$settings = get_option( 'notiblock_global_notice', $defaults );
	}

	// Ensure all keys exist and have correct types.
	$settings                = wp_parse_args( $settings, $defaults );
	$settings['always_show'] = (bool) $settings['always_show'];

	// Cache for this request.
	$cached_settings = $settings;

	return $settings;
}

/**
 * Sanitizes and saves Notiblock settings.
 *
 * In multisite with network-wide settings enabled, only network admins can save.
 * With per-site settings, site admins can save for their site.
 *
 * @param array $data Raw settings data to sanitize and save.
 * @return bool|WP_Error True on success, WP_Error on validation failure, false on save failure.
 */
function notiblock_save_settings( $data ) {
	// Check capability based on network-wide or per-site mode.
	if ( notiblock_use_network_settings() && ! current_user_can( 'manage_network_options' ) ) {
		// Network-wide: require network admin capability.
		return new WP_Error(
			'insufficient_permissions',
			__( 'You do not have permission to save network-wide settings.', 'notiblock' )
		);
	} elseif ( ! notiblock_use_network_settings() && ! current_user_can( 'manage_options' ) ) {
		// Per-site: require site admin capability.
		return new WP_Error(
			'insufficient_permissions',
			__( 'You do not have permission to save settings.', 'notiblock' )
		);
	}

	$sanitized = array(
		'content'     => wp_kses_post( isset( $data['content'] ) ? $data['content'] : '' ),
		'start_date'  => sanitize_text_field( isset( $data['start_date'] ) ? $data['start_date'] : '' ),
		'end_date'    => sanitize_text_field( isset( $data['end_date'] ) ? $data['end_date'] : '' ),
		'always_show' => isset( $data['always_show'] ) ? rest_sanitize_boolean( $data['always_show'] ) : false,
	);

	// Validate date format (YYYY-MM-DD) if provided.
	if ( ! empty( $sanitized['start_date'] ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $sanitized['start_date'] ) ) {
		$sanitized['start_date'] = '';
	}
	if ( ! empty( $sanitized['end_date'] ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $sanitized['end_date'] ) ) {
		$sanitized['end_date'] = '';
	}

	// Validate that end_date is after start_date (if both are provided and not using always_show).
	if ( ! $sanitized['always_show'] && ! empty( $sanitized['start_date'] ) && ! empty( $sanitized['end_date'] ) ) {
		if ( $sanitized['end_date'] < $sanitized['start_date'] ) {
			return new WP_Error(
				'invalid_date_range',
				__( 'End date must be after start date.', 'notiblock' )
			);
		}
	}

	// Save option with autoload enabled for optimal performance.
	// Use network-wide or per-site option based on configuration.
	if ( notiblock_use_network_settings() ) {
		$result = update_site_option( 'notiblock_global_notice', $sanitized );
	} else {
		$result = update_option( 'notiblock_global_notice', $sanitized, true );
	}

	// Clear static cache by forcing a refresh (if save was successful).
	// This ensures fresh data is available immediately after save in the same request.
	if ( $result ) {
		notiblock_get_settings( true );
	}

	return $result;
}

/**
 * Checks if the notification should be displayed based on current time and settings.
 *
 * @param array|null $settings Optional. Settings array. If not provided, will fetch from options.
 * @return bool True if notification should be displayed, false otherwise.
 */
function notiblock_is_active( $settings = null ) {
	if ( null === $settings ) {
		$settings = notiblock_get_settings();
	}

	// If "always show" is enabled, always display.
	if ( ! empty( $settings['always_show'] ) ) {
		return true;
	}

	// If no dates are set, don't display (unless always_show is true, which we already checked).
	if ( empty( $settings['start_date'] ) && empty( $settings['end_date'] ) ) {
		return false;
	}

	// Get current date in YYYY-MM-DD format using WordPress timezone.
	$current_date = current_time( 'Y-m-d' );

	// Check start date - if set, current date must be >= start date.
	if ( ! empty( $settings['start_date'] ) ) {
		if ( $current_date < $settings['start_date'] ) {
			return false;
		}
	}

	// Check end date - if set, current date must be <= end date.
	if ( ! empty( $settings['end_date'] ) ) {
		if ( $current_date > $settings['end_date'] ) {
			return false;
		}
	}

	return true;
}

/**
 * Registers the Notiblock blocks using a `blocks-manifest.php` file, which improves the performance of block type registration.
 * Behind the scenes, it also registers all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 */
function notiblock_register_blocks() {
	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` and registers the block type(s)
	 * based on the registered block metadata.
	 * Added in WordPress 6.8 to simplify the block metadata registration process added in WordPress 6.7.
	 *
	 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
	 */
	if ( function_exists( 'wp_register_block_types_from_metadata_collection' ) ) {
		wp_register_block_types_from_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
		return;
	}

	/**
	 * Registers the block(s) metadata from the `blocks-manifest.php` file.
	 * Added to WordPress 6.7 to improve the performance of block type registration.
	 *
	 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
	 */
	if ( function_exists( 'wp_register_block_metadata_collection' ) ) {
		wp_register_block_metadata_collection( __DIR__ . '/build', __DIR__ . '/build/blocks-manifest.php' );
	}
	/**
	 * Registers the block type(s) in the `blocks-manifest.php` file.
	 *
	 * @see https://developer.wordpress.org/reference/functions/register_block_type/
	 */
	$manifest_data = require __DIR__ . '/build/blocks-manifest.php';
	foreach ( array_keys( $manifest_data ) as $block_type ) {
		register_block_type( __DIR__ . "/build/{$block_type}" );
	}
}
add_action( 'init', 'notiblock_register_blocks' );

/**
 * Registers REST API endpoints for Notiblock settings.
 *
 * GET  /notiblock/v1/settings — Requires 'edit_posts'. Used by the block editor
 *                               to preview the notification message.
 * POST /notiblock/v1/settings — Requires 'manage_options' (or 'manage_network_options'
 *                               in network-wide mode). Used by the admin React app.
 */
function notiblock_register_rest_routes() {
	register_rest_route(
		'notiblock/v1',
		'/settings',
		array(
			array(
				'methods'             => 'GET',
				'callback'            => 'notiblock_rest_get_settings',
				'permission_callback' => function () {
					return current_user_can( 'edit_posts' );
				},
			),
			array(
				'methods'             => 'POST',
				'callback'            => 'notiblock_rest_save_settings',
				'permission_callback' => function () {
					return notiblock_use_network_settings()
						? current_user_can( 'manage_network_options' )
						: current_user_can( 'manage_options' );
				},
				'args'                => array(
					'content'     => array(
						'type'    => 'string',
						'default' => '',
					),
					'start_date'  => array(
						'type'    => 'string',
						'default' => '',
					),
					'end_date'    => array(
						'type'    => 'string',
						'default' => '',
					),
					'always_show' => array(
						'type'    => 'boolean',
						'default' => false,
					),
				),
			),
		)
	);
}
add_action( 'rest_api_init', 'notiblock_register_rest_routes' );

/**
 * REST API callback to get Notiblock settings.
 *
 * @return WP_REST_Response Settings data.
 */
function notiblock_rest_get_settings() {
	return rest_ensure_response( notiblock_get_settings() );
}

/**
 * REST API callback to save Notiblock settings.
 *
 * @param WP_REST_Request $request Request object.
 * @return WP_REST_Response|WP_Error Updated settings on success, WP_Error on failure.
 */
function notiblock_rest_save_settings( $request ) {
	$data = array(
		'content'     => $request->get_param( 'content' ),
		'start_date'  => $request->get_param( 'start_date' ),
		'end_date'    => $request->get_param( 'end_date' ),
		'always_show' => $request->get_param( 'always_show' ),
	);

	$result = notiblock_save_settings( $data );

	if ( is_wp_error( $result ) ) {
		return new WP_Error(
			$result->get_error_code(),
			$result->get_error_message(),
			array( 'status' => 400 )
		);
	}

	if ( false === $result ) {
		return new WP_Error(
			'save_failed',
			__( 'Failed to save settings.', 'notiblock' ),
			array( 'status' => 500 )
		);
	}

	return rest_ensure_response( notiblock_get_settings() );
}

/**
 * Registers the Notiblock dashboard widget.
 *
 * In network-wide mode, only shows on network admin dashboard.
 * In per-site mode, shows on individual site dashboards.
 */
function notiblock_register_dashboard_widget() {
	// Check if we're in network-wide mode.
	if ( notiblock_use_network_settings() && is_network_admin() && current_user_can( 'manage_network_options' ) ) {
		// Network-wide: only show on network admin dashboard.
		wp_add_dashboard_widget(
			'notiblock_dashboard_widget',
			__( 'Notiblock Settings (Network-wide)', 'notiblock' ),
			'notiblock_dashboard_widget_callback'
		);
	} elseif ( ! notiblock_use_network_settings() && current_user_can( 'manage_options' ) ) {
		// Per-site: show on individual site dashboards.
		wp_add_dashboard_widget(
			'notiblock_dashboard_widget',
			__( 'Notiblock Settings', 'notiblock' ),
			'notiblock_dashboard_widget_callback'
		);
	}
}
add_action( 'wp_dashboard_setup', 'notiblock_register_dashboard_widget' );
// Network admin dashboard widget.
add_action( 'wp_network_dashboard_setup', 'notiblock_register_dashboard_widget' );

/**
 * Registers the Notiblock settings page under Settings > Notiblock.
 * Only registered in per-site mode (network-wide mode uses the network admin dashboard widget).
 */
function notiblock_register_settings_page() {
	if ( notiblock_use_network_settings() ) {
		return;
	}
	add_options_page(
		__( 'Notiblock', 'notiblock' ),
		__( 'Notiblock', 'notiblock' ),
		'manage_options',
		'notiblock',
		'notiblock_settings_page_callback'
	);
}
add_action( 'admin_menu', 'notiblock_register_settings_page' );

/**
 * Registers the Notiblock settings page in the network admin (network-wide mode only).
 */
function notiblock_register_network_settings_page() {
	if ( ! notiblock_use_network_settings() ) {
		return;
	}
	add_submenu_page(
		'settings.php',
		__( 'Notiblock', 'notiblock' ),
		__( 'Notiblock', 'notiblock' ),
		'manage_network_options',
		'notiblock',
		'notiblock_settings_page_callback'
	);
}
add_action( 'network_admin_menu', 'notiblock_register_network_settings_page' );

/**
 * Renders the Notiblock settings page.
 */
function notiblock_settings_page_callback() {
	$can_manage = notiblock_use_network_settings()
		? current_user_can( 'manage_network_options' )
		: current_user_can( 'manage_options' );

	if ( ! $can_manage ) {
		return;
	}
	?>
	<div class="wrap">
		<h1><?php esc_html_e( 'Notiblock Settings', 'notiblock' ); ?></h1>
		<div id="notiblock-settings-root"></div>
	</div>
	<?php
}

/**
 * Enqueues the admin React app script and styles.
 * Shared between regular and network admin contexts.
 */
function notiblock_do_enqueue_admin_scripts() {
	$asset_file = plugin_dir_path( __FILE__ ) . 'build/admin/index.asset.php';
	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'notiblock-admin',
		plugin_dir_url( __FILE__ ) . 'build/admin/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);

	wp_enqueue_style(
		'notiblock-admin',
		plugin_dir_url( __FILE__ ) . 'build/admin/style-index.css',
		array(),
		$asset['version']
	);

	wp_localize_script(
		'notiblock-admin',
		'notiblockAdmin',
		array(
			'restUrl'       => rest_url( 'notiblock/v1/settings' ),
			'nonce'         => wp_create_nonce( 'wp_rest' ),
			'settings'      => notiblock_get_settings(),
			'currentDate'   => current_time( 'Y-m-d' ),
			'isNetworkWide' => notiblock_use_network_settings(),
		)
	);
}

/**
 * Enqueues admin scripts on the dashboard and settings page.
 *
 * @param string $hook Current admin page hook.
 */
function notiblock_enqueue_admin_scripts( $hook ) {
	$is_dashboard     = 'index.php' === $hook;
	$is_settings_page = 'settings_page_notiblock' === $hook;

	if ( ! $is_dashboard && ! $is_settings_page ) {
		return;
	}

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	notiblock_do_enqueue_admin_scripts();
}
add_action( 'admin_enqueue_scripts', 'notiblock_enqueue_admin_scripts' );

/**
 * Enqueues admin scripts on the network admin dashboard and settings page.
 *
 * @param string $hook Current admin page hook.
 */
function notiblock_enqueue_network_admin_scripts( $hook ) {
	$is_dashboard     = 'index.php' === $hook;
	$is_settings_page = 'settings_page_notiblock' === $hook;

	if ( ! $is_dashboard && ! $is_settings_page ) {
		return;
	}

	if ( ! notiblock_use_network_settings() || ! current_user_can( 'manage_network_options' ) ) {
		return;
	}

	notiblock_do_enqueue_admin_scripts();
}
add_action( 'network_admin_enqueue_scripts', 'notiblock_enqueue_network_admin_scripts' );

/**
 * Callback function for the Notiblock dashboard widget.
 * Renders a mount point for the React settings app.
 */
function notiblock_dashboard_widget_callback() {
	if ( notiblock_use_network_settings() && ! current_user_can( 'manage_network_options' ) ) {
		return;
	} elseif ( ! notiblock_use_network_settings() && ! current_user_can( 'manage_options' ) ) {
		return;
	}

	echo '<div id="notiblock-widget-root"></div>';
}
