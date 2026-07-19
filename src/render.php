<?php
/**
 * Block render template.
 *
 * Referenced from src/block.json's "render" property. Invoked by
 * register_block_type() automatically.
 *
 * Detects whether this render is happening inside the block editor's
 * ServerSideRender REST endpoint. If yes, we tell the renderer to
 * skip its outer wrapper, because the editor's React component wraps
 * its own .wcsp-outer around us (with live, user-driven classes and
 * CSS variables). Wrapping twice would create style conflicts.
 *
 * @package AmitryProductCategorySlider
 *
 * @var array    $attributes Block attributes.
 * @var string   $content    Inner blocks (none for this block).
 * @var WP_Block $block      Block instance.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WCSP_Renderer' ) ) {
	return;
}

// Are we being called from the block editor's REST endpoint?
$wcsp_is_rest = defined( 'REST_REQUEST' ) && REST_REQUEST;

if ( $wcsp_is_rest ) {
	// Editor preview: just emit the inner content. React wraps us.
	echo WCSP_Renderer::render( $attributes, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside renderer.
	return;
}

// Frontend: standard block wrapper + full renderer output.
$wcsp_wrapper_attrs = get_block_wrapper_attributes(
	array(
		'class' => 'wcsp-block-wrapper',
	)
);

echo '<div ' . wp_kses_data( $wcsp_wrapper_attrs ) . '>';
echo WCSP_Renderer::render( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped inside renderer.
echo '</div>';
