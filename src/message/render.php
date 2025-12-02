<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content.
 *     $block (WP_Block): The block instance.
 *
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

if ( ! function_exists( 'notiblock_get_settings' ) ) {
	return;
}

$settings = notiblock_get_settings();

if ( empty( $settings['content'] ) ) {
	return;
}

// Apply wpautop to ensure proper paragraph formatting.
// wpautop converts double line breaks to <p> tags and single line breaks to <br> tags.
$content = wpautop( $settings['content'] );
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo wp_kses_post( $content ); ?>
</div>

