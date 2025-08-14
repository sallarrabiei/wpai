<?php
/**
 * Frontend shortcode and assets.
 *
 * @package ras-wp-ai
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class raswpai_Frontend
 */
class raswpai_Frontend {
	/**
	 * Init hooks.
	 */
	public function raswpai_init() {
		add_shortcode( 'raswpai_chat', array( $this, 'raswpai_render_chat_shortcode' ) );
	}

	/**
	 * Render shortcode output and enqueue assets only when used.
	 *
	 * @param array $atts Shortcode attributes.
	 * @return string
	 */
	public function raswpai_render_chat_shortcode( $atts ) {
		$options = raswpai_Plugin::raswpai_get_options();
		$atts = shortcode_atts( array(
			'model'       => $options['model'],
			'temperature' => $options['temperature'],
			'max_tokens'  => $options['max_tokens'],
			'placeholder' => $options['ui_placeholder'],
			'theme'       => $options['theme'],
			'title'       => $options['ui_title'],
		), $atts, 'raswpai_chat' );

		$nonce = wp_create_nonce( 'raswpai_chat' );

		// Enqueue Tailwind (CDN) and our frontend script.
		wp_enqueue_script( 'raswpai_tailwind', 'https://cdn.tailwindcss.com', array(), RASWPAI_VERSION, true );
		// Tailwind dark mode config inline.
		wp_add_inline_script( 'raswpai_tailwind', 'tailwind.config = { darkMode: "class" };', 'before' );

		wp_enqueue_script( 'raswpai_frontend', RASWPAI_PLUGIN_URL . 'assets/js/frontend.js', array( 'wp-i18n' ), RASWPAI_VERSION, true );
		wp_localize_script( 'raswpai_frontend', 'raswpaiChat', array(
			'restUrl'     => esc_url_raw( rest_url( 'raswpai/v1/chat' ) ),
			'nonce'       => $nonce,
			'rtl'         => is_rtl(),
			'attrs'       => array(
				'model'       => (string) $atts['model'],
				'temperature' => (float) $atts['temperature'],
				'max_tokens'  => (int) $atts['max_tokens'],
			),
			'ui'          => array(
				'send'       => $options['ui_send_label'],
				'intro'      => $options['ui_intro_text'],
				'placeholder'=> (string) $atts['placeholder'],
				'theme'      => (string) $atts['theme'],
				'title'      => (string) $atts['title'],
			),
		) );

		ob_start();
		$container_classes = 'raswpai-container max-w-2xl mx-auto p-4';
		$theme             = esc_attr( $atts['theme'] );
		?>
		<div class="<?php echo esc_attr( $container_classes ); ?>" data-raswpai-chat data-theme="<?php echo $theme; ?>" role="region" aria-label="<?php echo esc_attr( $atts['title'] ); ?>">
			<div class="raswpai-card border rounded-xl shadow-sm bg-white dark:bg-gray-800 dark:text-gray-100">
				<div class="border-b px-4 py-3 flex items-center justify-between">
					<h2 class="text-lg font-semibold"><?php echo esc_html( $atts['title'] ); ?></h2>
				</div>
				<?php if ( ! empty( $options['ui_intro_text'] ) ) : ?>
					<div class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
						<?php echo wp_kses_post( wpautop( $options['ui_intro_text'] ) ); ?>
					</div>
				<?php endif; ?>
				<div class="raswpai-messages px-4 py-4 space-y-3 h-96 overflow-y-auto" aria-live="polite" aria-relevant="additions">
					<!-- Messages will be injected here -->
				</div>
				<form class="raswpai-form border-t px-4 py-3 flex gap-2" autocomplete="off">
					<label for="raswpai-input" class="screen-reader-text"><?php esc_html_e( 'Your message', 'ras-wp-ai' ); ?></label>
					<input id="raswpai-input" class="flex-1 border rounded-lg px-3 py-2 focus:outline-none focus:ring focus:ring-indigo-500 dark:bg-gray-900" type="text" placeholder="<?php echo esc_attr( $atts['placeholder'] ); ?>" aria-label="<?php esc_attr_e( 'Type your message', 'ras-wp-ai' ); ?>" />
					<button type="submit" class="min-w-[96px] bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg focus:outline-none focus:ring focus:ring-indigo-500"><?php echo esc_html( $options['ui_send_label'] ); ?></button>
				</form>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}
}