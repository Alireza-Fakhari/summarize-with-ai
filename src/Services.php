<?php
/**
 * AI services and prompt modes.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI;

defined( 'ABSPATH' ) || exit;

final class Services {

	/**
	 * Built-in AI services. `{q}` is replaced with the URL-encoded prompt.
	 * Services without `{q}` (Gemini, DeepSeek) get the prompt copied to the
	 * clipboard, then open so the visitor can paste it.
	 *
	 * @return array id => [ name, url, color, icon, copy ]
	 */
	public static function builtin() {
		$services = array(
			'chatgpt'    => array( 'ChatGPT', 'https://chatgpt.com/?q={q}', '#10a37f' ),
			'perplexity' => array( 'Perplexity', 'https://www.perplexity.ai/search?q={q}', '#20808d' ),
			'claude'     => array( 'Claude', 'https://claude.ai/new?q={q}', '#d97757' ),
			'gemini'     => array( 'Gemini', 'https://gemini.google.com/app', '#4f7cf7' ),
			'grok'       => array( 'Grok', 'https://grok.com/?q={q}', '#111111' ),
			'google'     => array( 'Google AI', 'https://www.google.com/search?udm=50&q={q}', '#4285f4' ),
			'copilot'    => array( 'Copilot', 'https://copilot.microsoft.com/?q={q}', '#0078d4' ),
			'deepseek'   => array( 'DeepSeek', 'https://chat.deepseek.com/', '#4d6bfe' ),
			'mistral'    => array( 'Le Chat', 'https://chat.mistral.ai/chat?q={q}', '#fa520f' ),
			'you'        => array( 'You.com', 'https://you.com/search?q={q}&tbm=youchat', '#7b61ff' ),
		);

		$out = array();
		foreach ( $services as $id => $s ) {
			$out[ $id ] = array(
				'name'  => $s[0],
				'url'   => $s[1],
				'color' => $s[2],
				'icon'  => $id,
				'copy'  => false === strpos( $s[1], '{q}' ),
			);
		}

		/**
		 * Filter the built-in AI services.
		 *
		 * @param array $out id => [ name, url, color, icon, copy ].
		 */
		return apply_filters( 'swai_builtin_services', $out );
	}

	/**
	 * Built-in + custom services in the saved order, with renamed labels applied.
	 */
	public static function all() {
		$s   = Settings::get();
		$all = self::builtin();

		foreach ( (array) $s['custom'] as $c ) {
			$all[ $c['id'] ] = array(
				'name'     => $c['name'] ? $c['name'] : $c['id'],
				'url'      => $c['url'],
				'color'    => '#64748b',
				'icon'     => '',
				'icon_url' => $c['icon_url'],
				'copy'     => false === strpos( $c['url'], '{q}' ),
				'custom'   => true,
			);
		}
		foreach ( (array) $s['names'] as $id => $name ) {
			if ( isset( $all[ $id ] ) ) {
				$all[ $id ]['name'] = $name;
			}
		}

		$ordered = array();
		foreach ( (array) $s['order'] as $id ) {
			if ( isset( $all[ $id ] ) ) {
				$ordered[ $id ] = $all[ $id ];
			}
		}
		return $ordered + $all;
	}

	public static function default_modes() {
		return array(
			'summary'   => array(
				'label'   => __( 'Summary', 'summarize-with-ai' ),
				'prompt'  => __( 'Read this page and give me a short, well-structured summary of its content. {url}', 'summarize-with-ai' ),
				'enabled' => 1,
			),
			'keypoints' => array(
				'label'   => __( 'Key points', 'summarize-with-ai' ),
				'prompt'  => __( 'Read this page and list its key takeaways as short bullet points. {url}', 'summarize-with-ai' ),
				'enabled' => 1,
			),
			'simplify'  => array(
				'label'   => __( 'Explain simply', 'summarize-with-ai' ),
				'prompt'  => __( 'Read this page and explain it in simple words, as if I were a complete beginner. {url}', 'summarize-with-ai' ),
				'enabled' => 1,
			),
			'translate' => array(
				'label'   => __( 'Translate', 'summarize-with-ai' ),
				'prompt'  => __( 'Read this page and translate its main content into English. {url}', 'summarize-with-ai' ),
				'enabled' => 0,
			),
			'ask'       => array(
				'label'   => __( 'Ask questions', 'summarize-with-ai' ),
				'prompt'  => __( 'Read this page. Give me a two-line overview, then wait for my questions about it. {url}', 'summarize-with-ai' ),
				'enabled' => 0,
			),
		);
	}

	/**
	 * Enabled modes (id => [ label, prompt ]). Always returns at least one.
	 */
	public static function active_modes() {
		$modes = array();
		foreach ( (array) Settings::get( 'modes' ) as $id => $m ) {
			if ( ! empty( $m['enabled'] ) && '' !== trim( (string) $m['prompt'] ) ) {
				$modes[ $id ] = $m;
			}
		}
		if ( ! $modes ) {
			$defaults = self::default_modes();
			$modes    = array( 'summary' => $defaults['summary'] );
		}

		/**
		 * Filter the prompt modes offered to visitors.
		 *
		 * @param array $modes id => [ label, prompt, enabled ].
		 */
		return apply_filters( 'swai_modes', $modes );
	}
}
