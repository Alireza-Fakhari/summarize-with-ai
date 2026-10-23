<?php
/**
 * Server render for the swai/summarize block.
 *
 * @package SummarizeWithAI
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

echo \SummarizeWithAI\Block::render( $attributes ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped in Renderer.
