<?php
/**
 * Core plugin utilities and shared helpers.
 *
 * @package ras-wp-ai
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class raswpai_Plugin
 */
class raswpai_Plugin {
	/**
	 * Initialize plugin components.
	 *
	 * @param raswpai_Admin    $admin Admin component.
	 * @param raswpai_Frontend $frontend Frontend component.
	 * @param raswpai_Rest     $rest REST component.
	 * @param raswpai_Logger   $logger Logger component.
	 */
	public function raswpai_init( $admin, $frontend, $rest, $logger ) {
		$admin->raswpai_init();
		$frontend->raswpai_init();
		$rest->raswpai_init();
		$logger->raswpai_init();
	}

	/**
	 * Get plugin default options.
	 *
	 * @return array
	 */
	public static function raswpai_get_default_options() {
		return array(
			'api_key'                  => '',
			'system_prompt'            => __( 'You are a helpful assistant.', 'ras-wp-ai' ),
			'model'                    => 'gpt-4o-mini',
			'temperature'              => 0.7,
			'max_tokens'               => 512,
			'multi_turn'               => 1,
			'logging_mode'             => 'anonymized', // none|anonymized|full.
			'retention_days'           => 30,
			'out_of_scope_enabled'     => 1,
			'scope_topic'              => __( 'your defined topic', 'ras-wp-ai' ),
			'scope_keywords'           => '',
			'theme'                    => 'auto', // light|dark|auto.
			'ui_title'                 => __( 'Ask AI', 'ras-wp-ai' ),
			'ui_placeholder'           => __( 'Type your question…', 'ras-wp-ai' ),
			'ui_send_label'            => __( 'Send', 'ras-wp-ai' ),
			'ui_intro_text'            => '',
			'ui_refusal_template'      => __( 'Sorry, I can only answer questions related to {topic}.', 'ras-wp-ai' ),
			'delete_data_on_uninstall' => 0,
		);
	}

	/**
	 * Get merged options with defaults.
	 *
	 * @return array
	 */
	public static function raswpai_get_options() {
		$defaults = self::raswpai_get_default_options();
		$options  = get_option( 'raswpai_options', array() );
		if ( ! is_array( $options ) ) {
			$options = array();
		}
		return wp_parse_args( $options, $defaults );
	}

	/**
	 * Sanitize and persist options array.
	 *
	 * @param array $raw Raw input.
	 * @return array
	 */
	public static function raswpai_sanitize_options( $raw ) {
		$defaults = self::raswpai_get_default_options();
		$clean    = array();

		$clean['api_key']             = isset( $raw['api_key'] ) ? trim( (string) $raw['api_key'] ) : '';
		$clean['system_prompt']       = isset( $raw['system_prompt'] ) ? wp_kses_post( (string) $raw['system_prompt'] ) : $defaults['system_prompt'];
		$clean['model']               = isset( $raw['model'] ) ? sanitize_text_field( (string) $raw['model'] ) : $defaults['model'];
		$clean['temperature']         = isset( $raw['temperature'] ) ? floatval( $raw['temperature'] ) : $defaults['temperature'];
		$clean['temperature']         = max( 0.0, min( 2.0, $clean['temperature'] ) );
		$clean['max_tokens']          = isset( $raw['max_tokens'] ) ? intval( $raw['max_tokens'] ) : $defaults['max_tokens'];
		$clean['max_tokens']          = max( 1, min( 4000, $clean['max_tokens'] ) );
		$clean['multi_turn']          = ! empty( $raw['multi_turn'] ) ? 1 : 0;
		$clean['logging_mode']        = in_array( isset( $raw['logging_mode'] ) ? $raw['logging_mode'] : $defaults['logging_mode'], array( 'none', 'anonymized', 'full' ), true ) ? $raw['logging_mode'] : $defaults['logging_mode'];
		$clean['retention_days']      = isset( $raw['retention_days'] ) ? max( 1, intval( $raw['retention_days'] ) ) : $defaults['retention_days'];
		$clean['out_of_scope_enabled']= ! empty( $raw['out_of_scope_enabled'] ) ? 1 : 0;
		$clean['scope_topic']         = isset( $raw['scope_topic'] ) ? sanitize_text_field( (string) $raw['scope_topic'] ) : $defaults['scope_topic'];
		$clean['scope_keywords']      = isset( $raw['scope_keywords'] ) ? sanitize_text_field( (string) $raw['scope_keywords'] ) : '';
		$clean['theme']               = in_array( isset( $raw['theme'] ) ? $raw['theme'] : $defaults['theme'], array( 'light', 'dark', 'auto' ), true ) ? $raw['theme'] : $defaults['theme'];
		$clean['ui_title']            = isset( $raw['ui_title'] ) ? sanitize_text_field( (string) $raw['ui_title'] ) : $defaults['ui_title'];
		$clean['ui_placeholder']      = isset( $raw['ui_placeholder'] ) ? sanitize_text_field( (string) $raw['ui_placeholder'] ) : $defaults['ui_placeholder'];
		$clean['ui_send_label']       = isset( $raw['ui_send_label'] ) ? sanitize_text_field( (string) $raw['ui_send_label'] ) : $defaults['ui_send_label'];
		$clean['ui_intro_text']       = isset( $raw['ui_intro_text'] ) ? wp_kses_post( (string) $raw['ui_intro_text'] ) : '';
		$clean['ui_refusal_template'] = isset( $raw['ui_refusal_template'] ) ? sanitize_text_field( (string) $raw['ui_refusal_template'] ) : $defaults['ui_refusal_template'];
		$clean['delete_data_on_uninstall'] = ! empty( $raw['delete_data_on_uninstall'] ) ? 1 : 0;

		return $clean;
	}

	/**
	 * Generate a session transient key from session ID.
	 *
	 * @param string $session_id Session ID.
	 * @return string
	 */
	public static function raswpai_get_session_key( $session_id ) {
		$session_id = sanitize_key( $session_id );
		return 'raswpai_session_' . md5( $session_id . wp_salt() );
	}

	/**
	 * Determine if a message is within scope using simple keyword checks.
	 *
	 * @param string $message Message.
	 * @param array  $options Options.
	 * @return bool
	 */
	public static function raswpai_is_in_scope( $message, $options ) {
		if ( empty( $options['out_of_scope_enabled'] ) ) {
			return true;
		}
		$topic     = isset( $options['scope_topic'] ) ? $options['scope_topic'] : '';
		$keywords  = isset( $options['scope_keywords'] ) ? $options['scope_keywords'] : '';
		$message_l = mb_strtolower( (string) $message );

		if ( ! empty( $topic ) && false !== mb_stripos( $message_l, mb_strtolower( $topic ) ) ) {
			return true;
		}

		if ( ! empty( $keywords ) ) {
			$list = array_filter( array_map( 'trim', explode( ',', $keywords ) ) );
			foreach ( $list as $kw ) {
				if ( '' === $kw ) {
					continue;
				}
				if ( false !== mb_stripos( $message_l, mb_strtolower( $kw ) ) ) {
					return true;
				}
			}
		}

		return false;
	}
}