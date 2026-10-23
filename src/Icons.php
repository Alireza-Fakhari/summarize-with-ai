<?php
/**
 * Inline SVG icons: AI brand marks, UI glyphs and label icons.
 * Label icons are taken from the POPO and Smoothproject icon packs.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI;

defined( 'ABSPATH' ) || exit;

final class Icons {

	/**
	 * Icons selectable for the label ("Summarize with:").
	 *
	 * @return array key => [ name, svg ]
	 */
	public static function label_icons() {
		$p1 = 'M15.08 4.1c.35-.8 1.49-.8 1.84 0l.75 1.72c.1.23.29.41.52.51l1.71.75c.8.35.8 1.49 0 1.84l-1.71.75a1 1 0 0 0-.52.51l-.75 1.72c-.35.8-1.49.8-1.84 0l-.75-1.72a1 1 0 0 0-.51-.51l-1.72-.75c-.8-.35-.8-1.49 0-1.84l1.72-.75c.23-.1.41-.28.51-.51z';
		$p2 = 'M6.68 9.89c.31-.72 1.33-.72 1.64 0l.4.92c.1.2.26.37.47.46l.92.4c.72.32.72 1.34 0 1.65l-.92.4a.9.9 0 0 0-.47.47l-.4.92c-.31.72-1.33.72-1.64 0l-.4-.92a.9.9 0 0 0-.47-.47l-.92-.4c-.72-.31-.72-1.33 0-1.65l.92-.4c.2-.09.37-.26.47-.46z';
		$p3 = 'M13.58 15.66c.2-.3.65-.3.84 0l.69 1.08.15.15 1.08.69c.3.2.3.65 0 .84l-1.08.69-.15.15-.69 1.08c-.2.3-.65.3-.84 0l-.69-1.08-.15-.15-1.08-.69c-.3-.2-.3-.65 0-.84l1.08-.69.15-.15z';
		$svg = function ( $inner, $fill = true ) {
			return '<svg viewBox="0 0 24 24" ' . ( $fill ? 'fill="currentColor"' : 'fill="none" stroke="currentColor" stroke-width="1.5"' ) . '>' . $inner . '</svg>';
		};
		return array(
			'stars-bold'   => array( 'name' => __( 'Stars (solid)', 'summarize-with-ai' ), 'svg' => $svg( "<path d=\"$p1\"/><path d=\"$p2\"/><path d=\"$p3\"/>" ) ),
			'stars-bulk'   => array( 'name' => __( 'Stars (duotone)', 'summarize-with-ai' ), 'svg' => $svg( "<path d=\"$p1\"/><path d=\"$p2\" opacity=\".4\"/><path d=\"$p3\"/>" ) ),
			'stars-linear' => array( 'name' => __( 'Stars (line)', 'summarize-with-ai' ), 'svg' => $svg( "<path d=\"$p1\"/><path d=\"$p2\"/><path d=\"$p3\"/>", false ) ),
			'magic'        => array( 'name' => __( 'Magic wand', 'summarize-with-ai' ), 'svg' => $svg( '<path d="M6.4 14.76 12 9.02a2.1 2.1 0 0 1 2.99 2.97l-5.73 5.63a2.1 2.1 0 0 1-2.86-2.86z"/><path d="M11.82 2.77a.75.75 0 0 1 .91.55l.5 2a.75.75 0 1 1-1.46.36l-.5-2a.75.75 0 0 1 .55-.91zm5.19.49a.75.75 0 0 1 .59.88l-.41 2.02a.75.75 0 1 1-1.47-.3l.41-2.02a.75.75 0 0 1 .88-.58zm4.71 4.54a.75.75 0 0 1-.52.92l-1.99.55a.75.75 0 1 1-.4-1.44l1.99-.56a.75.75 0 0 1 .92.53zm-.01 4.38a.75.75 0 0 1-.92.53l-1.99-.55a.75.75 0 1 1 .4-1.45l1.99.55a.75.75 0 0 1 .52.92z"/>' ) ),
			'sparkle'      => array( 'name' => __( 'Sparkle', 'summarize-with-ai' ), 'svg' => $svg( '<path d="M13 2a1 1 0 1 0-2 0v9H2a1 1 0 1 0 0 2h9v9a1 1 0 1 0 2 0v-9h9a1 1 0 1 0 0-2h-9z"/><path d="M8.7 8.7a1 1 0 0 1-1.4 0l-3-3a1 1 0 0 1 1.4-1.4l3 3a1 1 0 0 1 0 1.4zm11-3a1 1 0 0 0-1.4-1.4l-3 3a1 1 0 1 0 1.4 1.4zM8.7 15.3a1 1 0 0 1 0 1.4l-3 3a1 1 0 0 1-1.4-1.4l3-3a1 1 0 0 1 1.4 0zm9.6 4.4a1 1 0 0 0 1.4-1.4l-3-3a1 1 0 0 0-1.4 1.4z"/>' ) ),
		);
	}

	/**
	 * Brand or UI icon by key; '' when unknown.
	 */
	public static function svg( $key ) {
		$icons = array(
			'chatgpt'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M22.28 9.82a5.98 5.98 0 0 0-.52-4.91 6.05 6.05 0 0 0-6.51-2.9A6.07 6.07 0 0 0 4.98 4.18a5.98 5.98 0 0 0-4 2.9 6.05 6.05 0 0 0 .74 7.1 5.98 5.98 0 0 0 .51 4.91 6.05 6.05 0 0 0 6.52 2.9A5.98 5.98 0 0 0 13.26 24a6.06 6.06 0 0 0 5.77-4.2 5.99 5.99 0 0 0 4-2.9 6.06 6.06 0 0 0-.75-7.08zM13.26 22.43a4.48 4.48 0 0 1-2.88-1.04l.14-.08 4.78-2.76a.8.8 0 0 0 .4-.68v-6.74l2.02 1.17a.07.07 0 0 1 .04.05v5.58a4.5 4.5 0 0 1-4.5 4.5zM3.6 18.3a4.47 4.47 0 0 1-.54-3.01l.14.09 4.78 2.76a.77.77 0 0 0 .78 0l5.84-3.37v2.33a.08.08 0 0 1-.03.06l-4.83 2.79a4.5 4.5 0 0 1-6.14-1.65zM2.34 7.9a4.49 4.49 0 0 1 2.37-1.97v5.68a.77.77 0 0 0 .39.68l5.81 3.35-2.02 1.17a.08.08 0 0 1-.07 0L4 14.02A4.5 4.5 0 0 1 2.34 7.87zm16.6 3.86-5.84-3.39 2.02-1.16a.08.08 0 0 1 .07 0l4.83 2.79a4.49 4.49 0 0 1-.68 8.1v-5.68a.79.79 0 0 0-.4-.66zm2.01-3.02-.14-.09-4.77-2.78a.78.78 0 0 0-.79 0L9.41 9.23V6.9a.07.07 0 0 1 .03-.06l4.83-2.79a4.5 4.5 0 0 1 6.68 4.66zM8.31 12.86 6.29 11.7a.08.08 0 0 1-.04-.06V6.07a4.5 4.5 0 0 1 7.38-3.45l-.14.08-4.78 2.76a.8.8 0 0 0-.4.68zm1.1-2.37 2.6-1.5 2.6 1.5v3l-2.6 1.5-2.6-1.5z"/></svg>',
			'perplexity' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"><path d="M12 1v22M4 4l8 6 8-6v6M4 4v6h16M4 10l8 6 8-6M4 10v8l8-6M20 10v8l-8-6M4 18h3M17 18h3"/></svg>',
			'claude'     => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l1.4 6.2L18 4l-3 5.6 6.2-.6-5.7 2.9 5.7 2.9-6.2-.6 3 5.6-4.6-4.2L12 22l-1.4-6.2L6 20l3-5.6-6.2.6 5.7-2.9L2.8 9.2 9 9.8 6 4.2l4.6 4.2z"/></svg>',
			'grok'       => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M4 3l7.5 10.5L4 21h2.2l6.4-6.6L17.4 21H21l-7.8-11L20.2 3H18l-5.8 6L8 3z"/></svg>',
			'google'     => '<svg viewBox="0 0 24 24"><path fill="#4285F4" d="M22.5 12.3c0-.8-.1-1.5-.2-2.3H12v4.3h5.9a5 5 0 0 1-2.2 3.3v2.7h3.6c2-1.9 3.2-4.7 3.2-8z"/><path fill="#34A853" d="M12 23c3 0 5.5-1 7.3-2.7l-3.6-2.7c-1 .7-2.2 1-3.7 1-2.9 0-5.3-1.9-6.2-4.5H2.1v2.8A11 11 0 0 0 12 23z"/><path fill="#FBBC05" d="M5.8 14.1a6.6 6.6 0 0 1 0-4.2V7.1H2.1a11 11 0 0 0 0 9.8z"/><path fill="#EA4335" d="M12 5.4c1.6 0 3.1.6 4.2 1.7l3.2-3.2A11 11 0 0 0 2.1 7.1l3.7 2.8C6.7 7.3 9.1 5.4 12 5.4z"/></svg>',
			'copilot'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M9 3h6a3 3 0 0 1 2.9 2.2L20 12h-4l-1.6-5.6A1 1 0 0 0 13.4 6H9zM15 21H9a3 3 0 0 1-2.9-2.2L4 12h4l1.6 5.6a1 1 0 0 0 1 .4H15z"/></svg>',
			'mistral'    => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M2 3h4v4H2zm16 0h4v4h-4zM2 7h8v4H2zm12 0h8v4h-8zM2 11h20v4H2zm0 4h4v4H2zm8 0h4v4h-4zm8 0h4v4h-4zM0 19h8v3H0zm16 0h8v3h-8z"/></svg>',
			'gemini'     => '<svg viewBox="0 0 24 24" fill="currentColor"><path d="M12 2c.6 5.3 4.7 9.4 10 10-5.3.6-9.4 4.7-10 10-.6-5.3-4.7-9.4-10-10 5.3-.6 9.4-4.7 10-10z"/></svg>',
			'copy'       => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="12" height="12" rx="2.5"/><path d="M5 15H4.5A1.5 1.5 0 0 1 3 13.5v-9A1.5 1.5 0 0 1 4.5 3h9A1.5 1.5 0 0 1 15 4.5V5"/></svg>',
			'check'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12.5l4.5 4.5L19 7.5"/></svg>',
			'close'      => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg>',
			'sparkle'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M4 20 15 9M14 4l.6 1.4L16 6l-1.4.6L14 8l-.6-1.4L12 6l1.4-.6zM19 11l.5 1 1 .5-1 .5-.5 1-.5-1-1-.5 1-.5zM7 3l.5 1 1 .5-1 .5L7 6l-.5-1-1-.5 1-.5z"/></svg>',
		);
		return isset( $icons[ $key ] ) ? $icons[ $key ] : '';
	}
}
