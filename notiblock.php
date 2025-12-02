<?php
/**
 * Plugin Name:       Notiblock
 * Description:       Conditional notification blocks with dashboard widget configuration.
 * Version:           0.1.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Phil Hoyt
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       notiblock
 *
 * @package Notiblock
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
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
 * WordPress also caches get_option() calls automatically, but this adds an extra layer
 * of efficiency for the processing/validation step.
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

	$settings = get_option( 'notiblock_global_notice', $defaults );

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
 * @param array $data Raw settings data to sanitize and save.
 * @return bool|WP_Error True on success, WP_Error on validation failure, false on save failure.
 */
function notiblock_save_settings( $data ) {
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
	// This ensures the option is loaded with other autoloaded options at request start.
	$result = update_option( 'notiblock_global_notice', $sanitized, true );

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
 * Registers the block using a `blocks-manifest.php` file, which improves the performance of block type registration.
 * Behind the scenes, it also registers all assets so they can be enqueued
 * through the block editor in the corresponding context.
 *
 * @see https://make.wordpress.org/core/2025/03/13/more-efficient-block-type-registration-in-6-8/
 * @see https://make.wordpress.org/core/2024/10/17/new-block-type-registration-apis-to-improve-performance-in-wordpress-6-7/
 */
function create_block_notiblock_block_init() {
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
add_action( 'init', 'create_block_notiblock_block_init' );

/**
 * Registers REST API endpoint for fetching Notiblock settings.
 */
function notiblock_register_rest_routes() {
	register_rest_route(
		'notiblock/v1',
		'/settings',
		array(
			'methods'             => 'GET',
			'callback'            => 'notiblock_rest_get_settings',
			'permission_callback' => function () {
				return current_user_can( 'edit_posts' );
			},
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
	$settings = notiblock_get_settings();
	return rest_ensure_response( $settings );
}

/**
 * Registers the Notiblock dashboard widget.
 */
function notiblock_register_dashboard_widget() {
	if ( current_user_can( 'manage_options' ) ) {
		wp_add_dashboard_widget(
			'notiblock_dashboard_widget',
			__( 'Notiblock Settings', 'notiblock' ),
			'notiblock_dashboard_widget_callback'
		);
	}
}
add_action( 'wp_dashboard_setup', 'notiblock_register_dashboard_widget' );

/**
 * Callback function for the Notiblock dashboard widget.
 * Displays the form and handles form submission.
 */
function notiblock_dashboard_widget_callback() {
	// Check capability.
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	$settings = notiblock_get_settings();
	$message  = '';

	// Handle form submission.
	if ( isset( $_POST['notiblock_save_settings'] ) && check_admin_referer( 'notiblock_save_settings', 'notiblock_nonce' ) ) {
		if ( current_user_can( 'manage_options' ) ) {
			// Note: Content is intentionally not sanitized here as it will be sanitized
			// with wp_kses_post() in notiblock_save_settings() to preserve rich text formatting.
			$data = array(
				'content'     => isset( $_POST['notiblock_content'] ) ? wp_unslash( $_POST['notiblock_content'] ) : '', // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				'start_date'  => isset( $_POST['notiblock_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['notiblock_start_date'] ) ) : '',
				'end_date'    => isset( $_POST['notiblock_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['notiblock_end_date'] ) ) : '',
				'always_show' => isset( $_POST['notiblock_always_show'] ),
			);

			$result = notiblock_save_settings( $data );

			if ( is_wp_error( $result ) ) {
				// Validation error.
				$message = '<div class="notice notice-error inline"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
			} elseif ( $result ) {
				// Success - force refresh to get updated settings (cache was cleared in save function).
				$settings = notiblock_get_settings( true );
				$message  = '<div class="notice notice-success inline"><p>' . esc_html__( 'Settings saved successfully.', 'notiblock' ) . '</p></div>';
			} else {
				// Save failure.
				$message = '<div class="notice notice-error inline"><p>' . esc_html__( 'Error saving settings.', 'notiblock' ) . '</p></div>';
			}
		}
	}

	// Display message if any.
	if ( $message ) {
		echo wp_kses_post( $message );
	}
	?>

	<form method="post" action="">
		<?php wp_nonce_field( 'notiblock_save_settings', 'notiblock_nonce' ); ?>

		<p>
			<label for="notiblock_content">
				<strong><?php esc_html_e( 'Notification Message:', 'notiblock' ); ?></strong>
			</label>
		</p>
		<?php
		wp_editor(
			$settings['content'],
			'notiblock_content',
			array(
				'textarea_name' => 'notiblock_content',
				'textarea_rows' => 5,
				'media_buttons' => false,
				'teeny'         => true,
			)
		);
		?>

		<p>
			<label for="notiblock_start_date">
				<strong><?php esc_html_e( 'Start Date:', 'notiblock' ); ?></strong>
			</label><br>
			<input type="date" id="notiblock_start_date" name="notiblock_start_date" value="<?php echo esc_attr( $settings['start_date'] ); ?>" class="regular-text" />
			<br>
			<span class="description"><?php esc_html_e( 'Leave empty for no start date restriction.', 'notiblock' ); ?></span>
		</p>

		<p>
			<label for="notiblock_end_date">
				<strong><?php esc_html_e( 'End Date:', 'notiblock' ); ?></strong>
			</label><br>
			<input type="date" id="notiblock_end_date" name="notiblock_end_date" value="<?php echo esc_attr( $settings['end_date'] ); ?>" class="regular-text" />
			<br>
			<span class="description"><?php esc_html_e( 'Leave empty for no end date restriction.', 'notiblock' ); ?></span>
		</p>

		<p>
			<label for="notiblock_always_show">
				<input type="checkbox" id="notiblock_always_show" name="notiblock_always_show" value="1" <?php checked( $settings['always_show'], true ); ?> />
				<strong><?php esc_html_e( 'Always show (ignore date range)', 'notiblock' ); ?></strong>
			</label>
		</p>

		<?php
		// Display current status.
		if ( ! empty( $settings['content'] ) ) {
			$is_active = notiblock_is_active( $settings );
			$status    = $is_active ? __( 'Active', 'notiblock' ) : __( 'Inactive', 'notiblock' );
			$class     = $is_active ? 'notice-success' : 'notice-warning';
			?>
			<div class="notice <?php echo esc_attr( $class ); ?> inline">
				<p>
					<strong><?php esc_html_e( 'Current Status:', 'notiblock' ); ?></strong> <?php echo esc_html( $status ); ?>
					<?php
					if ( ! $settings['always_show'] ) {
						if ( ! empty( $settings['start_date'] ) || ! empty( $settings['end_date'] ) ) {
							echo ' — ';
							if ( ! empty( $settings['start_date'] ) && ! empty( $settings['end_date'] ) ) {
								printf(
									/* translators: 1: start date, 2: end date */
									esc_html__( 'Display period: %1$s to %2$s', 'notiblock' ),
									esc_html( $settings['start_date'] ),
									esc_html( $settings['end_date'] )
								);
							} elseif ( ! empty( $settings['start_date'] ) ) {
								printf(
									/* translators: %s: start date */
									esc_html__( 'Display from: %s', 'notiblock' ),
									esc_html( $settings['start_date'] )
								);
							} elseif ( ! empty( $settings['end_date'] ) ) {
								printf(
									/* translators: %s: end date */
									esc_html__( 'Display until: %s', 'notiblock' ),
									esc_html( $settings['end_date'] )
								);
							}
						}
					} else {
						echo ' — ' . esc_html__( 'Always visible (date range ignored)', 'notiblock' );
					}
					?>
				</p>
			</div>
			<?php
		}
		?>

		<p>
			<?php submit_button( __( 'Save Settings', 'notiblock' ), 'primary', 'notiblock_save_settings', false ); ?>
		</p>
	</form>

	<?php
}
