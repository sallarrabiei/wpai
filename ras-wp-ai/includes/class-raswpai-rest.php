<?php
/**
 * REST API endpoint for chat.
 *
 * @package ras-wp-ai
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class raswpai_Rest
 */
class raswpai_Rest {
	/**
	 * Init hooks.
	 */
	public function raswpai_init() {
		add_action( 'rest_api_init', array( $this, 'raswpai_register_routes' ) );
	}

	/**
	 * Register REST routes.
	 */
	public function raswpai_register_routes() {
		register_rest_route(
			'raswpai/v1',
			'/chat',
			array(
				'args'                => array(
					'message'    => array( 'required' => true, 'type' => 'string' ),
					'session_id' => array( 'required' => false, 'type' => 'string' ),
					'nonce'      => array( 'required' => true, 'type' => 'string' ),
					'attrs'      => array( 'required' => false, 'type' => 'object' ),
				),
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'raswpai_handle_chat' ),
				'permission_callback' => array( $this, 'raswpai_permission_check' ),
			)
		);
	}

	/**
	 * Permission check using nonce.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return bool|WP_Error
	 */
	public function raswpai_permission_check( $request ) {
		$nonce = $request->get_param( 'nonce' );
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'raswpai_chat' ) ) {
			return new WP_Error( 'raswpai_invalid_nonce', __( 'Invalid security token.', 'ras-wp-ai' ), array( 'status' => 403 ) );
		}
		return true;
	}

	/**
	 * Handle chat completion.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response|WP_Error
	 */
	public function raswpai_handle_chat( $request ) {
		$params     = $request->get_params();
		$message    = isset( $params['message'] ) ? (string) $params['message'] : '';
		$session_id = isset( $params['session_id'] ) ? sanitize_text_field( (string) $params['session_id'] ) : '';
		$attrs      = isset( $params['attrs'] ) && is_array( $params['attrs'] ) ? $params['attrs'] : array();

		if ( '' === trim( $message ) ) {
			return new WP_Error( 'raswpai_empty', __( 'Message cannot be empty.', 'ras-wp-ai' ), array( 'status' => 400 ) );
		}

		$options = raswpai_Plugin::raswpai_get_options();
		$api_key = $options['api_key'];
		if ( empty( $api_key ) ) {
			return new WP_Error( 'raswpai_no_key', __( 'API key not configured.', 'ras-wp-ai' ), array( 'status' => 500 ) );
		}

		// Effective params (attrs override defaults but validated server-side).
		$model       = isset( $attrs['model'] ) ? sanitize_text_field( (string) $attrs['model'] ) : $options['model'];
		$temperature = isset( $attrs['temperature'] ) ? floatval( $attrs['temperature'] ) : floatval( $options['temperature'] );
		$temperature = max( 0.0, min( 2.0, $temperature ) );
		$max_tokens  = isset( $attrs['max_tokens'] ) ? intval( $attrs['max_tokens'] ) : intval( $options['max_tokens'] );
		$max_tokens  = max( 1, min( 4000, $max_tokens ) );

		// Out-of-scope detection.
		if ( ! raswpai_Plugin::raswpai_is_in_scope( $message, $options ) ) {
			$refusal = $options['ui_refusal_template'];
			$refusal = str_replace( '{topic}', $options['scope_topic'], $refusal );

			$this->raswpai_maybe_log( $session_id, 'user', $message, $options );
			$this->raswpai_maybe_log( $session_id, 'assistant', $refusal, $options );

			return new WP_REST_Response( array(
				'session_id' => $this->raswpai_get_or_create_session_id( $session_id ),
				'message'    => $refusal,
				'finish_reason' => 'out_of_scope',
			), 200 );
		}

		// Build messages array with system prompt and session context.
		$messages = array();
		$messages[] = array(
			'role'    => 'system',
			'content' => (string) $options['system_prompt'],
		);

		$session_id = $this->raswpai_get_or_create_session_id( $session_id );
		$session_key = raswpai_Plugin::raswpai_get_session_key( $session_id );
		$session_ctx = get_transient( $session_key );
		if ( ! is_array( $session_ctx ) ) {
			$session_ctx = array();
		}
		if ( ! empty( $options['multi_turn'] ) && ! empty( $session_ctx ) ) {
			foreach ( $session_ctx as $ctx_msg ) {
				if ( isset( $ctx_msg['role'], $ctx_msg['content'] ) ) {
					$messages[] = array(
						'role'    => $ctx_msg['role'],
						'content' => (string) $ctx_msg['content'],
					);
				}
			}
		}
		$messages[] = array(
			'role'    => 'user',
			'content' => $message,
		);

		// Make OpenAI API call.
		$body = array(
			'model'       => $model,
			'messages'    => $messages,
			'temperature' => $temperature,
			'max_tokens'  => $max_tokens,
		);

		$response = wp_remote_post( 'https://api.openai.com/v1/chat/completions', array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'Content-Type'  => 'application/json',
			),
			'body'    => wp_json_encode( $body ),
			'timeout' => 45,
		) );

		if ( is_wp_error( $response ) ) {
			return new WP_Error( 'raswpai_api_error', $response->get_error_message(), array( 'status' => 500 ) );
		}

		$code = wp_remote_retrieve_response_code( $response );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( $code < 200 || $code >= 300 || ! is_array( $data ) ) {
			return new WP_Error( 'raswpai_bad_api_response', __( 'Unexpected API response.', 'ras-wp-ai' ), array( 'status' => 500 ) );
		}

		$assistant = '';
		$finish    = '';
		if ( isset( $data['choices'][0]['message']['content'] ) ) {
			$assistant = (string) $data['choices'][0]['message']['content'];
		}
		if ( isset( $data['choices'][0]['finish_reason'] ) ) {
			$finish = (string) $data['choices'][0]['finish_reason'];
		}

		// Update session context.
		$ctx_to_store = $session_ctx;
		$ctx_to_store[] = array( 'role' => 'user', 'content' => $message );
		$ctx_to_store[] = array( 'role' => 'assistant', 'content' => $assistant );
		$ttl = DAY_IN_SECONDS * max( 1, intval( $options['retention_days'] ) );
		set_transient( $session_key, $ctx_to_store, $ttl );

		// Logging depending on mode.
		$this->raswpai_maybe_log( $session_id, 'user', $message, $options );
		$this->raswpai_maybe_log( $session_id, 'assistant', $assistant, $options );

		return new WP_REST_Response( array(
			'session_id'    => $session_id,
			'message'       => $assistant,
			'finish_reason' => $finish,
		), 200 );
	}

	/**
	 * Get or create a session id.
	 *
	 * @param string $session_id Provided.
	 * @return string
	 */
	private function raswpai_get_or_create_session_id( $session_id ) {
		if ( ! empty( $session_id ) ) {
			return $session_id;
		}
		return wp_generate_uuid4();
	}

	/**
	 * Conditionally log message based on logging mode.
	 *
	 * @param string $session_id Session id.
	 * @param string $role       Role.
	 * @param string $content    Content.
	 * @param array  $options    Options.
	 */
	private function raswpai_maybe_log( $session_id, $role, $content, $options ) {
		$mode = isset( $options['logging_mode'] ) ? $options['logging_mode'] : 'none';
		if ( 'none' === $mode ) {
			return;
		}
		$meta = array(
			'ip_hash' => $this->raswpai_get_ip_hash(),
			'user_id' => get_current_user_id(),
		);
		if ( 'anonymized' === $mode ) {
			$content = '***redacted***';
		}
		raswpai_Logger::raswpai_log( $session_id, $role, $content, $meta );
	}

	/**
	 * Hash IP with salt for anonymization.
	 *
	 * @return string
	 */
	private function raswpai_get_ip_hash() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		return hash( 'sha256', $ip . wp_salt() );
	}
}