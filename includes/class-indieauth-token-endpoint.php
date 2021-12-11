<?php
/**
 *
 *
 * Implements IndieAuth Token Endpoint
 */

class IndieAuth_Token_Endpoint extends IndieAuth_Endpoint {

	public function __construct() {
		parent::__construct();
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/**
	 * Register the Route.
	 */
	public function register_routes() {
		register_rest_route(
			'indieauth/1.0',
			'/token',
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'post' ),
					'args'                => array(
						'grant_type'    => array(),
						/* The authorization code received from the authorization endpoint in the redirect.
						 */
						'code'          => array(),
						/* The client's URL, which MUST match the client_id used in the authentication request.
						 */
						'client_id'     => array(
							'validate_callback' => 'rest_is_valid_url',
							'sanitize_callback' => 'esc_url_raw',
						),
						/* The client's redirect URL, which MUST match the initial authentication request.
						 */
						'redirect_uri'  => array(
							'validate_callback' => 'rest_is_valid_url',
							'sanitize_callback' => 'esc_url_raw',
						),
						/* The original plaintext random string generated before starting the authorization request.
						 */
						'code_verifier' => array(),
						/* Currently only Used for Token Revokation as action=revoke
						 */
						'action'        => array(),
						/* Paired with Action for Token Revokation
						 */
						'token'         => array(),
					),
					'permission_callback' => '__return_true',
				),
			)
		);
		register_rest_route(
			'indieauth/1.0',
			'/token',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get' ),
					'args'                => array(),
					'permission_callback' => '__return_true',
				),
			)
		);
	}

	/*
	 * Token Endpoint GET Handler.
	 *
	 * @param WP_REST_Request $request The Request Object.
	 * @return Response to Return to the REST Server.
	 */
	public function get( $request ) {
		$params = $request->get_params();
		$header = $request->get_header( 'Authorization' );
		if ( ! $header && ! empty( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ) {
			$header = wp_unslash( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] );
		}
		$access_token = $this->get_token_from_bearer_header( $header );
		if ( ! $access_token ) {
			return new WP_OAuth_Response(
				'parameter_absent',
				__(
					'Bearer Token Not Supplied or Server Misconfigured to Not Pass Token. Run diagnostic script in WordPress Admin
				IndieAuth Settings Page',
					'indieauth'
				),
				400
			);
		}
		$token = $this->get_token( $access_token );
		if ( ! $token ) {
			return new WP_OAuth_Response( 'invalid_token', __( 'Invalid access token', 'indieauth' ), 401 );
		}
		$token['active'] = 'true';
		return rest_ensure_response( $token );
	}

	/*
	 * Token Endpoint POST Handler.
	 *
	 * @param WP_REST_Request $request The Request Object.
	 * @return Response to Return to the REST Server.
	 */
	public function post( $request ) {
		$params = $request->get_params();

		// You cannot have both an action and a grant_type parameter.
		if ( isset( $params['action'] ) && isset( $params['grant_type'] ) ) {
			return WP_OAuth_Response( 'invalid_request', __( 'Please choose either an action or a grant_type', 'indieauth' ) );
		}

		// Action Handler
		if ( isset( $params['action'] ) ) {
			switch ( $params['action'] ) {
				// Revoke Token
				case 'revoke':
					if ( isset( $params['token'] ) ) {
						$this->delete_token( $params['token'] );
						return __( 'The Token Provided is No Longer Valid', 'indieauth' );
					} else {
						return new WP_OAuth_Response( 'invalid_request', __( 'Revoke is Missing Required Parameter token', 'indieauth' ), 400 );
					}
				default:
					return new WP_OAuth_Response( 'unsupported_action', __( 'Unsupported Action', 'indieauth' ), 400 );
			}
		}

		// Grant Type Handler.
		if ( isset( $params['grant_type'] ) ) {
			switch ( $params['grant_type'] ) {
				// Request Token
				case 'authorization_code':
					return $this->authorization_code( $params );
				case 'refresh_token':
					return $this->refresh_token( $params );
				default:
					return new WP_OAuth_Response( 'unsupported_grant_type', __( 'Unsupported grant_type.', 'indieauth' ), 400 );
			}
		}

		// Everything Failed
		return new WP_OAuth_Response( 'invalid_request', __( 'Invalid Request', 'indieauth' ), 400 );
	}


	// Refresh Token Grant Type.
	public function refresh_token( $params ) {

		$diff = array_diff( array( 'refresh_token' ), array_keys( $params ) );
		if ( ! empty( $diff ) ) {
			return new WP_OAuth_Response( 'invalid_request', __( 'The request is missing one or more required parameters', 'indieauth' ), 400 );
		}
		$refresh = $this->refresh_tokens->get( $params['refresh_token'] );
		if ( ! $refresh ) {
			return new WP_OAuth_Response( 'invalid_grant', __( 'Invalid Token', 'indieauth' ), 400 );
		}

		// Destroy the refresh token.
		$this->refresh_tokens->destroy( $params['refresh_token'] );

		return $this->generate_token_response( $refresh );

	}

	// Authorization Code Grant Type.
	public function authorization_code( $params ) {
		$diff = array_diff( array( 'code', 'client_id', 'redirect_uri' ), array_keys( $params ) );
		if ( ! empty( $diff ) ) {
			return new WP_OAuth_Response( 'invalid_request', __( 'The request is missing one or more required parameters', 'indieauth' ), 400 );
		}
		$args     = array_filter(
			array(
				'code'          => $params['code'],
				'redirect_uri'  => $params['redirect_uri'],
				'client_id'     => $params['client_id'],
				'code_verifier' => isset( $params['code_verifier'] ) ? $params['code_verifier'] : null,
			)
		);
		$response = indieauth_verify_local_authorization_code( $args );

		$error = get_oauth_error( $response );
		if ( $error ) {
			return $error;
		}

		return $this->generate_token_response( $response );

	}

	public function generate_token_response( $response ) {
		$return = array(
			'me' => $response['me'],
		);

		if ( isset( $response['scope'] ) ) {
			$scopes = array_filter( explode( ' ', $response['scope'] ) );
			if ( ! array_key_exists( 'user', $response ) ) {
				$user             = get_user_by_identifier( $response['me'] );
				$response['user'] = $user->ID;
			}
			if ( in_array( 'profile', $scopes, true ) ) {
				$return['profile'] = indieauth_get_user( $response['user'], in_array( 'email', $scopes, true ) );
			}

			// Issue a token
			if ( ! empty( $scopes ) ) {
				$info                 = new IndieAuth_Client_Discovery( $response['client_id'] );
				$return['token_type'] = 'Bearer';

				if ( ! array_key_exists( 'uuid', $response ) ) {
					/* Add UUID for reference. In case you’d like to build infrastructure for additional properties and store them in an alternate location.
					 * As of 4.1.0, the uuid is passed from the authorization code to the access token and refresh token. But if not, it is added here.
					 * This idea came from the core application password implementation.
					 */
					$return['uuid'] = wp_generate_uuid4();
				} else {
					$return['uuid'] = $response['uuid'];
				}

				$return['scope']       = $response['scope'];
				$return['issued_by']   = rest_url( 'indieauth/1.0/token' );
				$return['client_id']   = $response['client_id'];
				$return['client_name'] = $info->get_name();
				$return['client_icon'] = $info->get_icon();
				$return['iat']         = time();

				$expires = (int) get_option( 'indieauth_expires_in' );

				$return = array_filter( $return );

				$return['access_token'] = $this->set_token( $return, $expires, $response['user'] );

				// Do Not Add Expires In for the Return Until After It is Saved to the Database
				if ( 0 !== $expires ) {
					$return['expires_in']    = $expires;
					$return['refresh_token'] = $this->set_refresh_token( $return, $response['user'] );
				}
			}
		}

		if ( $return ) {
			// Return only the standard keys in the response
			return new WP_REST_Response(
				wp_array_slice_assoc(
					$return,
					array(
						'access_token',
						'token_type',
						'scope',
						'me',
						'profile',
						'expires_in',
						'refresh_token',
					)
				),
				200, // Status Code
				array(
					'Cache-Control' => 'no-store',
					'Pragma'        => 'no-cache',
				)
			);
		}
		return new WP_OAuth_Response( 'server_error', __( 'There was an error in response.', 'indieauth' ), 500 );
	}

}

