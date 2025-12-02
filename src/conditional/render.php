<?php
/**
 * PHP file to use when rendering the block type on the server to show on the front end.
 *
 * The following variables are exposed to the file:
 *     $attributes (array): The block attributes.
 *     $content (string): The block default content (InnerBlocks rendered content).
 *     $block (WP_Block): The block instance.
 *
 * @package Notiblock
 * @see https://github.com/WordPress/gutenberg/blob/trunk/docs/reference-guides/block-api/block-metadata.md#render
 */

// Check if the notification should be displayed.
if ( ! function_exists( 'notiblock_is_active' ) ) {
	// If helper function doesn't exist, don't render.
	return;
}

$settings  = notiblock_get_settings();
$is_active = notiblock_is_active( $settings );

// Only render if active.
if ( ! $is_active ) {
	return;
}

// For dynamic blocks with InnerBlocks, WordPress automatically renders them and passes as $content.
// If $content is empty, try to render InnerBlocks manually (fallback).
if ( empty( trim( $content ) ) && isset( $block ) ) {
	// Try using the block's inner_blocks property.
	if ( ! empty( $block->inner_blocks ) && is_array( $block->inner_blocks ) ) {
		$content = '';
		foreach ( $block->inner_blocks as $inner_block ) {
			if ( is_object( $inner_block ) && method_exists( $inner_block, 'render' ) ) {
				$content .= $inner_block->render();
			} elseif ( is_array( $inner_block ) ) {
				$content .= render_block( $inner_block );
			}
		}
	}

	// If that didn't work, try parsed_block.
	if ( empty( trim( $content ) ) && isset( $block->parsed_block['innerBlocks'] ) && is_array( $block->parsed_block['innerBlocks'] ) ) {
		$content = '';
		foreach ( $block->parsed_block['innerBlocks'] as $inner_block ) {
			if ( is_array( $inner_block ) ) {
				$content .= render_block( $inner_block );
			}
		}
	}
}

// If content is still empty, render nothing.
if ( empty( trim( $content ) ) ) {
	return;
}

// Render the InnerBlocks content.
?>
<div <?php echo get_block_wrapper_attributes(); ?>>
	<?php echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- InnerBlocks content is already escaped. ?>
</div>
