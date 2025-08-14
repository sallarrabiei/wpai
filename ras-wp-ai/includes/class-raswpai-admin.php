<?php
/**
 * Admin settings for RAS WP AI.
 *
 * @package ras-wp-ai
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class raswpai_Admin
 */
class raswpai_Admin {
	/**
	 * Init hooks.
	 */
	public function raswpai_init() {
		add_action( 'admin_menu', array( $this, 'raswpai_admin_menu' ) );
		add_action( 'admin_init', array( $this, 'raswpai_register_settings' ) );
	}

	/**
	 * Register settings.
	 */
	public function raswpai_register_settings() {
		register_setting( 'raswpai_settings', 'raswpai_options', array( $this, 'raswpai_sanitize' ) );

		add_settings_section( 'raswpai_main', __( 'General', 'ras-wp-ai' ), '__return_false', 'raswpai' );

		add_settings_field( 'api_key', __( 'OpenAI API Key', 'ras-wp-ai' ), array( $this, 'raswpai_field_api_key' ), 'raswpai', 'raswpai_main' );
		add_settings_field( 'system_prompt', __( 'System Prompt / Template', 'ras-wp-ai' ), array( $this, 'raswpai_field_system_prompt' ), 'raswpai', 'raswpai_main' );
		add_settings_field( 'model', __( 'Default Model', 'ras-wp-ai' ), array( $this, 'raswpai_field_model' ), 'raswpai', 'raswpai_main' );
		add_settings_field( 'temperature', __( 'Temperature', 'ras-wp-ai' ), array( $this, 'raswpai_field_temperature' ), 'raswpai', 'raswpai_main' );
		add_settings_field( 'max_tokens', __( 'Max Tokens', 'ras-wp-ai' ), array( $this, 'raswpai_field_max_tokens' ), 'raswpai', 'raswpai_main' );
		add_settings_field( 'multi_turn', __( 'Multi-turn Conversation', 'ras-wp-ai' ), array( $this, 'raswpai_field_multi_turn' ), 'raswpai', 'raswpai_main' );

		add_settings_section( 'raswpai_scope', __( 'Scope & Safety', 'ras-wp-ai' ), '__return_false', 'raswpai' );
		add_settings_field( 'out_of_scope_enabled', __( 'Out-of-scope Detection', 'ras-wp-ai' ), array( $this, 'raswpai_field_out_of_scope_enabled' ), 'raswpai', 'raswpai_scope' );
		add_settings_field( 'scope_topic', __( 'Defined Topic', 'ras-wp-ai' ), array( $this, 'raswpai_field_scope_topic' ), 'raswpai', 'raswpai_scope' );
		add_settings_field( 'scope_keywords', __( 'Scope Keywords', 'ras-wp-ai' ), array( $this, 'raswpai_field_scope_keywords' ), 'raswpai', 'raswpai_scope' );

		add_settings_section( 'raswpai_logging', __( 'Privacy & Logging', 'ras-wp-ai' ), '__return_false', 'raswpai' );
		add_settings_field( 'logging_mode', __( 'Conversation Logging', 'ras-wp-ai' ), array( $this, 'raswpai_field_logging_mode' ), 'raswpai', 'raswpai_logging' );
		add_settings_field( 'retention_days', __( 'Retention Days', 'ras-wp-ai' ), array( $this, 'raswpai_field_retention_days' ), 'raswpai', 'raswpai_logging' );
		add_settings_field( 'delete_data_on_uninstall', __( 'Delete Data on Uninstall', 'ras-wp-ai' ), array( $this, 'raswpai_field_delete_on_uninstall' ), 'raswpai', 'raswpai_logging' );

		add_settings_section( 'raswpai_ui', __( 'Interface Texts & Theme', 'ras-wp-ai' ), '__return_false', 'raswpai' );
		add_settings_field( 'theme', __( 'Theme', 'ras-wp-ai' ), array( $this, 'raswpai_field_theme' ), 'raswpai', 'raswpai_ui' );
		add_settings_field( 'ui_title', __( 'Title', 'ras-wp-ai' ), array( $this, 'raswpai_field_ui_title' ), 'raswpai', 'raswpai_ui' );
		add_settings_field( 'ui_placeholder', __( 'Placeholder', 'ras-wp-ai' ), array( $this, 'raswpai_field_ui_placeholder' ), 'raswpai', 'raswpai_ui' );
		add_settings_field( 'ui_send_label', __( 'Send Button Text', 'ras-wp-ai' ), array( $this, 'raswpai_field_ui_send' ), 'raswpai', 'raswpai_ui' );
		add_settings_field( 'ui_intro_text', __( 'Intro Text (optional)', 'ras-wp-ai' ), array( $this, 'raswpai_field_ui_intro' ), 'raswpai', 'raswpai_ui' );
		add_settings_field( 'ui_refusal_template', __( 'Refusal Message Template', 'ras-wp-ai' ), array( $this, 'raswpai_field_ui_refusal' ), 'raswpai', 'raswpai_ui' );
	}

	/**
	 * Sanitize callback for options.
	 *
	 * @param array $raw Raw values.
	 * @return array
	 */
	public function raswpai_sanitize( $raw ) {
		check_admin_referer( 'raswpai_options_save', 'raswpai_nonce' );
		return raswpai_Plugin::raswpai_sanitize_options( (array) $raw );
	}

	/**
	 * Add menu item.
	 */
	public function raswpai_admin_menu() {
		$page_hook = add_options_page(
			__( 'RAS WP AI', 'ras-wp-ai' ),
			__( 'RAS WP AI', 'ras-wp-ai' ),
			'manage_options',
			'raswpai',
			array( $this, 'raswpai_render_settings_page' )
		);
		add_action( 'load-' . $page_hook, array( $this, 'raswpai_add_help_tabs' ) );
	}

	/**
	 * Render settings page.
	 */
	public function raswpai_render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'RAS WP AI Settings', 'ras-wp-ai' ); ?></h1>
			<form method="post" action="options.php">
				<?php
				settings_fields( 'raswpai_settings' );
				do_settings_sections( 'raswpai' );
				wp_nonce_field( 'raswpai_options_save', 'raswpai_nonce' );
				submit_button( __( 'Save Settings', 'ras-wp-ai' ) );
				?>
			</form>
			<hr />
			<p><em><?php esc_html_e( 'Shortcode: [raswpai_chat]', 'ras-wp-ai' ); ?></em></p>
		</div>
		<?php
	}

	/**
	 * Help tabs.
	 */
	public function raswpai_add_help_tabs() {
		$screen = get_current_screen();
		if ( empty( $screen ) ) {
			return;
		}
		$screen->add_help_tab( array(
			'id'      => 'raswpai_overview',
			'title'   => __( 'Overview', 'ras-wp-ai' ),
			'content' => '<p>' . esc_html__( 'Configure your OpenAI key, defaults, scope control, privacy, and UI texts. Use the [raswpai_chat] shortcode to render the chat interface.', 'ras-wp-ai' ) . '</p>',
		) );
		$screen->add_help_tab( array(
			'id'      => 'raswpai_privacy',
			'title'   => __( 'Privacy', 'ras-wp-ai' ),
			'content' => '<p>' . esc_html__( 'Choose logging mode and retention to comply with privacy/GDPR. "Anonymized" stores only minimal metadata; "Full" stores complete messages.', 'ras-wp-ai' ) . '</p>',
		) );
	}

	/**
	 * Field renderers below.
	 */
	public function raswpai_field_api_key() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<input type="password" style="width: 400px" name="raswpai_options[api_key]" value="<?php echo esc_attr( $options['api_key'] ); ?>" autocomplete="off" />
		<p class="description"><?php esc_html_e( 'Your OpenAI API key. Stored server-side and never exposed to the frontend.', 'ras-wp-ai' ); ?></p>
		<?php
	}

	public function raswpai_field_system_prompt() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<textarea name="raswpai_options[system_prompt]" rows="5" cols="70"><?php echo esc_textarea( $options['system_prompt'] ); ?></textarea>
		<p class="description"><?php esc_html_e( 'This prompt is prepended to every GPT call and defines the assistant’s scope and tone.', 'ras-wp-ai' ); ?></p>
		<?php
	}

	public function raswpai_field_model() {
		$options = raswpai_Plugin::raswpai_get_options();
		$models  = apply_filters( 'raswpai_models', array( 'gpt-4o-mini', 'gpt-4o', 'gpt-4.1-mini', 'gpt-4.1' ) );
		?>
		<select name="raswpai_options[model]">
			<?php foreach ( $models as $m ) : ?>
				<option value="<?php echo esc_attr( $m ); ?>" <?php selected( $options['model'], $m ); ?>><?php echo esc_html( $m ); ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}

	public function raswpai_field_temperature() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<input type="number" step="0.1" min="0" max="2" name="raswpai_options[temperature]" value="<?php echo esc_attr( $options['temperature'] ); ?>" />
		<?php
	}

	public function raswpai_field_max_tokens() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<input type="number" min="1" max="4000" name="raswpai_options[max_tokens]" value="<?php echo esc_attr( $options['max_tokens'] ); ?>" />
		<?php
	}

	public function raswpai_field_multi_turn() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<label><input type="checkbox" name="raswpai_options[multi_turn]" value="1" <?php checked( ! empty( $options['multi_turn'] ) ); ?> /> <?php esc_html_e( 'Enable multi-turn conversations', 'ras-wp-ai' ); ?></label>
		<?php
	}

	public function raswpai_field_out_of_scope_enabled() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<label><input type="checkbox" name="raswpai_options[out_of_scope_enabled]" value="1" <?php checked( ! empty( $options['out_of_scope_enabled'] ) ); ?> /> <?php esc_html_e( 'Refuse unrelated questions based on topic/keywords', 'ras-wp-ai' ); ?></label>
		<?php
	}

	public function raswpai_field_scope_topic() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<input type="text" name="raswpai_options[scope_topic]" value="<?php echo esc_attr( $options['scope_topic'] ); ?>" size="50" />
		<p class="description"><?php esc_html_e( 'The defined topic used in the refusal message. Example: "our products and services".', 'ras-wp-ai' ); ?></p>
		<?php
	}

	public function raswpai_field_scope_keywords() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<input type="text" name="raswpai_options[scope_keywords]" value="<?php echo esc_attr( $options['scope_keywords'] ); ?>" size="50" />
		<p class="description"><?php esc_html_e( 'Comma-separated keywords that indicate in-scope questions.', 'ras-wp-ai' ); ?></p>
		<?php
	}

	public function raswpai_field_logging_mode() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<select name="raswpai_options[logging_mode]">
			<option value="none" <?php selected( $options['logging_mode'], 'none' ); ?>><?php esc_html_e( 'None', 'ras-wp-ai' ); ?></option>
			<option value="anonymized" <?php selected( $options['logging_mode'], 'anonymized' ); ?>><?php esc_html_e( 'Anonymized', 'ras-wp-ai' ); ?></option>
			<option value="full" <?php selected( $options['logging_mode'], 'full' ); ?>><?php esc_html_e( 'Full', 'ras-wp-ai' ); ?></option>
		</select>
		<?php
	}

	public function raswpai_field_retention_days() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<input type="number" min="1" max="3650" name="raswpai_options[retention_days]" value="<?php echo esc_attr( $options['retention_days'] ); ?>" />
		<?php
	}

	public function raswpai_field_delete_on_uninstall() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<label><input type="checkbox" name="raswpai_options[delete_data_on_uninstall]" value="1" <?php checked( ! empty( $options['delete_data_on_uninstall'] ) ); ?> /> <?php esc_html_e( 'Remove all plugin data on uninstall (options and logs)', 'ras-wp-ai' ); ?></label>
		<?php
	}

	public function raswpai_field_theme() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<select name="raswpai_options[theme]">
			<option value="auto" <?php selected( $options['theme'], 'auto' ); ?>><?php esc_html_e( 'Auto (match system)', 'ras-wp-ai' ); ?></option>
			<option value="light" <?php selected( $options['theme'], 'light' ); ?>><?php esc_html_e( 'Light', 'ras-wp-ai' ); ?></option>
			<option value="dark" <?php selected( $options['theme'], 'dark' ); ?>><?php esc_html_e( 'Dark', 'ras-wp-ai' ); ?></option>
		</select>
		<?php
	}

	public function raswpai_field_ui_title() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<input type="text" name="raswpai_options[ui_title]" value="<?php echo esc_attr( $options['ui_title'] ); ?>" size="50" />
		<?php
	}

	public function raswpai_field_ui_placeholder() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<input type="text" name="raswpai_options[ui_placeholder]" value="<?php echo esc_attr( $options['ui_placeholder'] ); ?>" size="50" />
		<?php
	}

	public function raswpai_field_ui_send() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<input type="text" name="raswpai_options[ui_send_label]" value="<?php echo esc_attr( $options['ui_send_label'] ); ?>" size="30" />
		<?php
	}

	public function raswpai_field_ui_intro() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<textarea name="raswpai_options[ui_intro_text]" rows="3" cols="70"><?php echo esc_textarea( $options['ui_intro_text'] ); ?></textarea>
		<?php
	}

	public function raswpai_field_ui_refusal() {
		$options = raswpai_Plugin::raswpai_get_options();
		?>
		<input type="text" name="raswpai_options[ui_refusal_template]" value="<?php echo esc_attr( $options['ui_refusal_template'] ); ?>" size="80" />
		<p class="description"><?php esc_html_e( 'Use {topic} placeholder to inject the defined topic.', 'ras-wp-ai' ); ?></p>
		<?php
	}
}