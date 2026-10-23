<?php
/**
 * Per-post controls: hide the buttons or use a custom prompt.
 * Meta is registered for REST, so it works in both the classic and block editors.
 *
 * @package SummarizeWithAI
 */

namespace SummarizeWithAI\Admin;

use SummarizeWithAI\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Per-post controls in the editor.
 */
final class MetaBox {

	const NONCE = 'swai_meta';

	/**
	 * Hook the component into WordPress.
	 */
	public static function register() {
		add_action( 'init', array( __CLASS__, 'register_meta' ) );
		add_action( 'add_meta_boxes', array( __CLASS__, 'add' ) );
		add_action( 'save_post', array( __CLASS__, 'save' ) );
	}

	/**
	 * Register post meta for REST and the block editor.
	 */
	public static function register_meta() {
		$auth = static function ( $allowed, $meta_key, $post_id ) {
			return current_user_can( 'edit_post', $post_id );
		};
		register_meta(
			'post',
			'_swai_disable',
			array(
				'type'          => 'boolean',
				'single'        => true,
				'show_in_rest'  => true,
				'auth_callback' => $auth,
			)
		);
		register_meta(
			'post',
			'_swai_prompt',
			array(
				'type'              => 'string',
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_textarea_field',
				'auth_callback'     => $auth,
			)
		);
	}

	/**
	 * Add the meta box to supported post types.
	 */
	public static function add() {
		foreach ( (array) Settings::get( 'post_types' ) as $type ) {
			add_meta_box( 'swai-meta', __( 'Summarize with AI', 'summarize-with-ai' ), array( __CLASS__, 'render' ), $type, 'side', 'low' );
		}
	}

	/**
	 * Output the meta box.
	 *
	 * @param \WP_Post $post Post being edited.
	 */
	public static function render( $post ) {
		wp_nonce_field( self::NONCE, self::NONCE . '_nonce' );
		?>
		<p>
			<label>
				<input type="checkbox" name="swai_disable" value="1" <?php checked( (bool) get_post_meta( $post->ID, '_swai_disable', true ) ); ?>>
				<?php esc_html_e( 'Hide AI buttons on this post', 'summarize-with-ai' ); ?>
			</label>
		</p>
		<p>
			<label for="swai_prompt"><strong><?php esc_html_e( 'Custom prompt (optional)', 'summarize-with-ai' ); ?></strong></label>
			<textarea id="swai_prompt" name="swai_prompt" rows="4" class="widefat" placeholder="<?php esc_attr_e( 'Overrides the default mode for this post. Supports {url} and {title}.', 'summarize-with-ai' ); ?>"><?php echo esc_textarea( get_post_meta( $post->ID, '_swai_prompt', true ) ); ?></textarea>
		</p>
		<?php
	}

	/**
	 * Save the meta box fields.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function save( $post_id ) {
		if ( ! isset( $_POST[ self::NONCE . '_nonce' ] ) || ! wp_verify_nonce( sanitize_key( $_POST[ self::NONCE . '_nonce' ] ), self::NONCE ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( empty( $_POST['swai_disable'] ) ) {
			delete_post_meta( $post_id, '_swai_disable' );
		} else {
			update_post_meta( $post_id, '_swai_disable', 1 );
		}

		$prompt = isset( $_POST['swai_prompt'] ) ? sanitize_textarea_field( wp_unslash( $_POST['swai_prompt'] ) ) : '';
		if ( '' === trim( $prompt ) ) {
			delete_post_meta( $post_id, '_swai_prompt' );
		} else {
			update_post_meta( $post_id, '_swai_prompt', $prompt );
		}
	}
}
