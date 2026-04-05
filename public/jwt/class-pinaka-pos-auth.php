<?php
/**
 * Setup JWT-Auth.
 *
 * @package pinaka-pos
 */

use Firebase\JWT\JWT;

/**
 * The public-facing functionality of the plugin.
 */
class Pinaka_Pos_Auth {
	/**
	 * The namespace to add to the api calls.
	 *
	 * @var string The namespace to add to the api call
	 */
	private $namespace;

	/**
	 * Store errors to display if the JWT is wrong
	 *
	 * @var WP_REST_Response
	 */
	private $jwt_error = null;

	/**
	 * Collection of translate-able messages.
	 *
	 * @var array
	 */
	private $messages = array();

	/**
	 * The REST API slug.
	 *
	 * @var string
	 */
	private $rest_api_slug = 'wp-json';

	/**
	 * Setup action & filter hooks.
	 */
	public function __construct() {
		$this->namespace = 'pinaka-pos/v1';
		$this->messages  = array(
			'jwt_auth_no_auth_header'  => esc_html__( 'Authorization header not found.', 'pinaka-pos' ),
			'jwt_auth_bad_auth_header' => esc_html__( 'Authorization header malformed.', 'pinaka-pos' ),
		);
	}

	/**
	 * Add the endpoints to the API
	 */
	public function register_rest_routes() {

		register_rest_route(
			$this->namespace,
			'token',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_token' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'token-email-password',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_token_email_pass' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'token/validate',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'validate_token' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'token/logout',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'logout_token' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'token/logout-by-id',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'logout_by_user_id' ),
				'permission_callback' => '__return_true', // change if you want restrictions
				'args'                => array(
					'emp_login_pin' => array(
						'required' => true,
						'type'     => 'integer',
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'token/email',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'get_token_by_email' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'token/validate-login-pin',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'validate_login_pin' ),
				'permission_callback' => '__return_true',
			)
		);

		register_rest_route(
			$this->namespace,
			'token/create-invoice',
			[
				[
					'methods'             => 'POST',
					'callback'            => [ $this, 'pinaka_create_invoice' ],
					'permission_callback' => '__return_true'
				],
			]
		);



	}

	/**
	 * Add CORs suppot to the request.
	 */
	public function add_cors_support() {
		$enable_cors = defined( 'PINAKA_JWT_AUTH_CORS_ENABLE' ) ? PINAKA_JWT_AUTH_CORS_ENABLE : false;

		if ( $enable_cors ) {
			$headers = apply_filters( 'jwt_auth_cors_allow_headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization' );

			header( sprintf( 'Access-Control-Allow-Headers: %s', $headers ) );
		}
	}

	/**
	 * Authenticate user either via wp_authenticate or custom auth (e.g: OTP).
	 *
	 * @param string $username The username.
	 * @param string $password The password.
	 * @param mixed  $custom_auth The custom auth data (if any).
	 *
	 * @return WP_User|WP_Error $user Returns WP_User object if success, or WP_Error if failed.
	 */
	public function authenticate_user( $username, $password, $custom_auth = '' ) {
		// If using custom authentication.
		if ( $custom_auth ) {
			$custom_auth_error = new WP_Error( 'jwt_auth_custom_auth_failed', esc_html__( 'Custom authentication failed.', 'pinaka-pos' ) );

			/**
			 * Do your own custom authentication and return the result through this filter.
			 * It should return either WP_User or WP_Error.
			 */
			$user = apply_filters( 'jwt_auth_do_custom_auth', $custom_auth_error, $username, $password, $custom_auth );
		} else {
			$user = wp_authenticate( $username, $password );
		}

		return $user;
	}


	/**
	 * Get token using email & password
	 *
	 * @param WP_REST_Request $request
	 * @return WP_REST_Response
	 */
	public function get_token_by_email( WP_REST_Request $request ) {

		$secret_key = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : false;

		$email    = $request->get_param( 'email' );
		$password = $request->get_param( 'password' );
		// Validate config
		if ( ! $secret_key ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_bad_config',
					'message'    => esc_html__( 'JWT is not configured properly.', 'magni-pos' ),
					'data'       => array(),
				),
				403
			);
		}

		// Validate input
		if ( empty( $email ) || empty( $password ) ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 400,
					'code'       => 'jwt_auth_missing_credentials',
					'message'    => esc_html__( 'Email and password are required.', 'magni-pos' ),
					'data'       => array(),
				),
				400
			);
		}

		// Get user by email
		$user = get_user_by( 'email', sanitize_email( $email ) );

		if ( ! $user ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_invalid_email',
					'message'    => esc_html__( 'Invalid email or password.', 'magni-pos' ),
					'data'       => array(),
				),
				403
			);
		}

		// Verify password
		if ( ! wp_check_password( $password, $user->user_pass, $user->ID ) ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_invalid_password',
					'message'    => esc_html__( 'Invalid email or password.', 'magni-pos' ),
					'data'       => array(),
				),
				403
			);
		}

		// Generate JWT token
		return $this->generate_token( $user, false );
	}

	/**
	 * Get token by sending POST request to magni-pos/v1/token.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */
	public function get_token_email_pass( WP_REST_Request $request ) {
		echo 'Deprecated endpoint. Please use PINAKA POS v2 app for email-password authentication.';
		exit;

		$username    = $request->get_param( 'username' );
		$password    = $request->get_param( 'password' );
		$custom_auth = $request->get_param( 'custom_auth' );

		$user = $this->authenticate_user( $username, $password, $custom_auth );

		// If the authentication is failed return error response.
		if ( is_wp_error( $user ) ) {
			$error_code = $user->get_error_code();

			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => $error_code,
					'message'    => wp_strip_all_tags( $user->get_error_message( $error_code ) ),
					'data'       => array(),
				),
				403
			);
		}

		// Valid credentials, the user exists, let's generate the token.
		return $this->generate_token( $user, false );
	}
	/**
	 * Get token by sending POST request to pinaka-pos/v1/token with a 6-digit PIN.
	 *
	 * @param WP_REST_Request $request The request.
	 * @return WP_REST_Response The response.
	 */
	public function get_token(WP_REST_Request $request) {
		$secret_key = defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : false;
		$emp_login_pin   = $request->get_param('emp_login_pin'); // Get PIN from request

		// Check if secret key exists
		if (!$secret_key) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_bad_config',
					'message'    => esc_html__('JWT is not configured properly.', 'pinaka-pos'),
					'data'       => array(),
				),
				403
			);
		}

		// Validate PIN (must be exactly 6 digits)
		if (!preg_match('/^\d{6}$/', $emp_login_pin)) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 400,
					'code'       => 'invalid_pin_format',
					'message'    => esc_html__('PIN must be exactly 6 digits.', 'pinaka-pos'),
					'data'       => array(),
				),
				400
			);
		}

		// Authenticate user by PIN
		$user = $this->authenticate_by_pin($emp_login_pin);

		// Check if a valid session is already active
		$existing_token = get_user_meta($user->ID, 'current_jwt_token', true);
		$token_created_at = get_user_meta($user->ID, 'token_created_at', true);

		if ( $existing_token && $token_created_at ) {
			$expire = $token_created_at + ( DAY_IN_SECONDS * 30 );
			if ( $expire > time() ) {
				return new WP_REST_Response(
					array(
						'success'    => false,
						'statusCode' => 403,
						'code'       => 'session_active_elsewhere',
						'message'    => __('Your session is already active on another console. Please logout first to continue.', 'pinaka-pos'),
						'data'       => array(),
					),
					403
				);
			}
		}

		// If authentication fails, return error
		if (is_wp_error($user)) {
			$error_code = $user->get_error_code();

			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => $error_code,
					'message'    => wp_strip_all_tags($user->get_error_message($error_code)),
					'data'       => array(),
				),
				403
			);
		}

		// Valid PIN, generate token
		return $this->generate_token($user, false);
	}


	/**
	 * Authenticate user using PIN code.
	 *
	 * @param string $emp_login_pin The PIN code.
	 * @return WP_User|WP_Error User object if authenticated, otherwise error.
	 */
	private function authenticate_by_pin($emp_login_pin) {
		// Search for user with matching PIN
		$users = get_users([
			'meta_key'   => 'emp_login_pin',
			'meta_value' => $emp_login_pin,
			'number'     => 1,
		]);

		if (empty($users)) {
			return new WP_Error('invalid_pin', __('Invalid PIN or user not found.', 'pinaka-pos'));
		}

		$user = $users[0];

		// Check expiration date
		$expiration_date = get_option('pinaka_license_expiration');
		
		if ($expiration_date && strtotime($expiration_date) < time()) {
			return new WP_Error('license_expired', __('Your POS license has expired. Please renew to continue.', 'pinaka-pos'));
		}

		return $user;
	}


	/**
	 * Generate token
	 *
	 * @param WP_User $user The WP_User object.
	 * @param bool    $return_raw Whether or not to return as raw token string.
	 *
	 * @return WP_REST_Response|string Return as raw token string or as a formatted WP_REST_Response.
	 */
	public function generate_token( $user, $return_raw = true ) {
		$secret_key = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : false;
		$issued_at  = time();
		$not_before = $issued_at;
		$not_before = apply_filters( 'jwt_auth_not_before', $not_before, $issued_at );
		$expire     = $issued_at + ( DAY_IN_SECONDS * 30 );
		$expire     = apply_filters( 'jwt_auth_expire', $expire, $issued_at );

		$payload = array(
			'iss'  => $this->get_iss(),
			'iat'  => $issued_at,
			'nbf'  => $not_before,
			'exp'  => $expire,
			'data' => array(
				'user' => array(
					'id' => $user->ID,
				),
			),
		);

		$alg = $this->get_alg();

		// Let the user modify the token data before the sign.
		$token = JWT::encode( apply_filters( 'jwt_auth_payload', $payload, $user ), $secret_key, $alg );

		// If return as raw token string.
		if ( $return_raw ) {
			return $token;
		}

		// The token is signed, now create object with basic info of the user.
		$response = array(
			'success'    => true,
			'statusCode' => 200,
			'code'       => 'jwt_auth_valid_credential',
			'message'    => __( 'Credential is valid', 'pinaka-pos' ),
			'data'       => array(
				'token'       => $token,
				'id'          => $user->ID,
				'email'       => $user->user_email,
				'nicename'    => $user->user_nicename,
				'firstName'   => $user->first_name,
				'lastName'    => $user->last_name,
				'displayName' => $user->display_name,
				'role'        => $user->roles[0],
				'avatar'      => get_avatar_url( $user->ID ),
				'shift_id'	  => $this->get_open_shift_id($user->ID),
				'safe_enable'	  => get_option('enable_safes'),
				'safe_enable_drop'	  => get_option('enable_safes_drop')

			),
		);
		update_user_meta($user->ID, 'current_jwt_token', $token);
		update_user_meta($user->ID, 'token_created_at', time());
		// Let the user modify the data before send it back.
		return apply_filters( 'jwt_auth_valid_credential_response', $response, $user );
	}


	/**
	 * Logout user by clearing stored JWT token.
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function logout_token( WP_REST_Request $request ) {
		$payload = $this->validate_token( false );

		if ( $this->is_error_response( $payload ) ) {
			return $payload;
		}

		$user_id = $payload->data->user->id;

		// Delete stored token meta
		delete_user_meta( $user_id, 'current_jwt_token' );
		delete_user_meta( $user_id, 'token_created_at' );

		return new WP_REST_Response(
			array(
				'success'    => true,
				'statusCode' => 200,
				'code'       => 'jwt_auth_logout_success',
				'message'    => __( 'Successfully logged out.', 'pinaka-pos' ),
				'data'       => array(),
			)
		);
	}

	/**
	 * Logout a user by clearing stored token using emp_login_pin (no JWT required).
	 *
	 * @param WP_REST_Request $request The request object.
	 * @return WP_REST_Response
	 */
	public function logout_by_user_id( WP_REST_Request $request ) {

		// Get PIN from request
		$emp_login_pin = intval( $request->get_param( 'emp_login_pin' ));

		if ( ! $emp_login_pin ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 400,
					'code'       => 'missing_pin',
					'message'    => __( 'Employee login PIN is required.', 'pinaka-pos' ),
					'data'       => array(),
				),
				400
			);
		}

		// Find user by PIN
		$users = get_users([
			'meta_key'   => 'emp_login_pin',
			'meta_value' => $emp_login_pin,
			'number'     => 1,
			'fields'     => array( 'ID' ),
		]);

		if ( empty( $users ) ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 404,
					'code'       => 'user_not_found',
					'message'    => __( 'User not found.', 'pinaka-pos' ),
					'data'       => array(),
				),
				404
			);
		}

		// Extract user ID
		$user_id = $users[0]->ID;
		if( ! $user_id ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 404,
					'code'       => 'user_not_found',
					'message'    => __( 'User not found.', 'pinaka-pos' ),
					'data'       => array(),
				),
				404
			);

		}else{

			// Delete stored token meta (invalidates JWT instantly)
			delete_user_meta( $user_id, 'current_jwt_token' );
			delete_user_meta( $user_id, 'token_created_at' );
	
			return new WP_REST_Response(
				array(
					'success'    => true,
					'statusCode' => 200,
					'code'       => 'jwt_auth_logout_success',
					'message'    => __( 'User logged out successfully.', 'pinaka-pos' ),
					'data'       => array(),
				)
			);
		}

	}




	/**
	 * Get the token issuer.
	 *
	 * @return string The token issuer (iss).
	 */
	public function get_iss() {
		return apply_filters( 'jwt_auth_iss', get_bloginfo( 'url' ) );
	}

	/**
	 * Get the supported jwt auth signing algorithm.
	 *
	 * @see https://tools.ietf.org/html/draft-ietf-jose-json-web-algorithms-40
	 *
	 * @return string $alg
	 */
	public function get_alg() {
		return apply_filters( 'jwt_auth_alg', 'HS256' );
	}

	/**
	 * Determine if given response is an error response.
	 *
	 * @param mixed $response The response.
	 * @return boolean
	 */
	public function is_error_response( $response ) {
		if ( ! empty( $response ) && property_exists( $response, 'data' ) && is_array( $response->data ) ) {
			if ( ! isset( $response->data['success'] ) || ! $response->data['success'] ) {
				return true;
			}
		}

		return false;
	}

	public function validate_token( $return_response = true ) {
		/**
		 * Looking for the HTTP_AUTHORIZATION header, if not present just
		 * return the user.
		 */
		$headerkey = apply_filters( 'jwt_auth_authorization_header', 'HTTP_AUTHORIZATION' );
		$auth      = isset( $_SERVER[ $headerkey ] ) ? $_SERVER[ $headerkey ] : false;

		// Double check for different auth header string (server dependent).
		if ( ! $auth ) {
			$auth = isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
		}

		if ( ! $auth ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_no_auth_header',
					'message'    => $this->messages['jwt_auth_no_auth_header'],
					'data'       => array(),
				)
			);
		}

		/**
		 * The HTTP_AUTHORIZATION is present, verify the format.
		 * If the format is wrong return the user.
		 */
		list($token) = sscanf( $auth, 'Bearer %s' );

		if ( ! $token ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_bad_auth_header',
					'message'    => $this->messages['jwt_auth_bad_auth_header'],
					'data'       => array(),
				)
			);
		}

		// Get the Secret Key.
		$secret_key = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : false;

		if ( ! $secret_key ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_bad_config',
					'message'    => esc_html__( 'JWT is not configured properly.', 'pinaka-pos' ),
					'data'       => array(),
				),
				403
			);
		}

		// Try to decode the token.
		try {
			$alg     = $this->get_alg();
			$payload = JWT::decode( $token, $secret_key, array( $alg ) );

			// The Token is decoded now validate the iss.
			if ( $payload->iss !== $this->get_iss() ) {
				// The iss do not match, return error.
				return new WP_REST_Response(
					array(
						'success'    => false,
						'statusCode' => 403,
						'code'       => 'jwt_auth_bad_iss',
						'message'    => esc_html__( 'The iss do not match with this server.', 'pinaka-pos' ),
						'data'       => array(),
					),
					403
				);
			}

			// Check the user id existence in the token.
			if ( ! isset( $payload->data->user->id ) ) {
				// No user id in the token, abort!!
				return new WP_REST_Response(
					array(
						'success'    => false,
						'statusCode' => 403,
						'code'       => 'jwt_auth_bad_request',
						'message'    => esc_html__( 'User ID not found in the token.', 'pinaka-pos' ),
						'data'       => array(),
					),
					403
				);
			}

			// So far so good, check if the given user id exists in db.
			$user = get_user_by( 'id', $payload->data->user->id );

			$stored_token = get_user_meta( $user->ID, 'current_jwt_token', true );
			if ( $stored_token !== $token ) {
				return new WP_REST_Response(
					array(
						'success'    => false,
						'statusCode' => 403,
						'code'       => 'jwt_auth_obsolete_token',
						'message'    => __( 'This token has been invalidated. Please login again.', 'pinaka-pos' ),
						'data'       => array(),
					),
					403
				);
			}
			if ( ! $user ) {
				// No user id in the token, abort!!
				return new WP_REST_Response(
					array(
						'success'    => false,
						'statusCode' => 403,
						'code'       => 'jwt_auth_user_not_found',
						'message'    => esc_html__( "User doesn't exist", 'pinaka-pos' ),
						'data'       => array(),
					),
					403
				);
			} elseif ( in_array( 'administrator', $user->roles ) ) {
				if ( isset( $_REQUEST['user_id'] ) ) {
					$payload->data->user->id = $_REQUEST['user_id'];
				}
			}

			// Check extra condition if exists.
			$failed_msg = apply_filters( 'jwt_auth_extra_token_check', '', $user, $token, $payload );

			if ( ! empty( $failed_msg ) ) {
				// No user id in the token, abort!!
				return new WP_REST_Response(
					array(
						'success'    => false,
						'statusCode' => 403,
						'code'       => 'jwt_auth_obsolete_token',
						'message'    => esc_html__( 'Token is obsolete', 'pinaka-pos' ),
						'data'       => array(),
					),
					403
				);
			}

			// Everything looks good, return the payload if $return_response is set to false.
			if ( ! $return_response ) {
				return $payload;
			}

			$response = array(
				'success'    => true,
				'statusCode' => 200,
				'code'       => 'jwt_auth_valid_token',
				'message'    => esc_html__( 'Token is valid', 'pinaka-pos' ),
				'data'       => array(),
			);

			$response = apply_filters( 'jwt_auth_valid_token_response', $response, $user, $token, $payload );

			// Otherwise, return success response.
			return new WP_REST_Response( $response );
		} catch ( Exception $e ) {
			// Something is wrong when trying to decode the token, return error response.
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_invalid_token',
					'message'    => $e->getMessage(),
					'data'       => array(),
				),
				403
			);
		}
	}

	/**
	 * This is our Middleware to try to authenticate the user according to the token sent.
	 *
	 * @param int|bool $user_id User ID if one has been determined, false otherwise.
	 * @return int|bool User ID if one has been determined, false otherwise.
	 */
	public function determine_current_user( $user_id ) {

		$request_uri = $_SERVER['REQUEST_URI'];

		$this->rest_api_slug = get_option( 'permalink_structure' ) ? rest_get_url_prefix() : '?rest_route=/';

		$valid_api_uri = strpos( $request_uri, $this->rest_api_slug );

		if ( ! $valid_api_uri ) {
			return $user_id;
		}

		$validate_uri = strpos( $request_uri, 'token/validate' );

		if ( $validate_uri > 0 ) {
			return $user_id;
		}

		$payload = $this->validate_token( false );

		// If $payload is an error response, then return the default $user_id.
		if ( $this->is_error_response( $payload ) ) {
			if ( 'jwt_auth_no_auth_header' === $payload->data['code'] ||
				'jwt_auth_bad_auth_header' === $payload->data['code']
			) {
				$rest_api_slug = home_url( '/' . $this->rest_api_slug, 'relative' );

				if ( $rest_api_slug . '/pinaka-pos/v1/token' !== $request_uri ) {
					// Whitelist some endpoints by default (without trailing * char).
					$default_whitelist = array(

					);

					// Well, we let you adjust this default whitelist :).
					$default_whitelist = apply_filters( 'jwt_auth_default_whitelist', $default_whitelist );

					$is_ignored    = true;
					$pinaka_pos_url = $rest_api_slug . '/pinaka-pos/v1';
					if ( false !== stripos( $request_uri, $pinaka_pos_url ) ) {
						$is_ignored = false;
					}

					foreach ( $default_whitelist as $endpoint ) {
						if ( false !== stripos( $request_uri, $endpoint ) ) {
							$is_ignored = true;

							break;
						}
					}

					if ( ! $is_ignored ) {
						if ( ! $this->is_whitelisted() ) {
							$this->jwt_error = $payload;
						}
					}
				}
			} else {
				$this->jwt_error = $payload;
			}

			return $user_id;
		}
		// Everything is ok here, return the user ID stored in the token.
		return $payload->data->user->id;
	}

	/**
	 * Check whether or not current endpoint is whitelisted.
	 *
	 * @return bool
	 */
	public function is_whitelisted() {
		$whitelist = apply_filters( 'jwt_auth_whitelist', array() );

		if ( empty( $whitelist ) || ! is_array( $whitelist ) ) {
			return false;
		}

		$request_uri    = $_SERVER['REQUEST_URI'];
		$request_method = $_SERVER['REQUEST_METHOD'];

		$prefix      = get_option( 'permalink_structure' ) ? rest_get_url_prefix() : '?rest_route=/';
		$split       = explode( $prefix, $request_uri );
		$request_uri = '/' . $prefix . ( ( count( $split ) > 1 ) ? $split[1] : $split[0] );

		// Only use string before "?" sign if permalink is enabled.
		if ( get_option( 'permalink_structure' ) && false !== stripos( $request_uri, '?' ) ) {
			$split       = explode( '?', $request_uri );
			$request_uri = $split[0];
		}

		// Let's remove trailingslash for easier checking.
		$request_uri = untrailingslashit( $request_uri );

		foreach ( $whitelist as $endpoint ) {
			if ( is_array( $endpoint ) ) {
				$method = $endpoint['method'];
				$path   = $endpoint['path'];
			} else {
				$method = null;
				$path   = $endpoint;
			}
			// If the endpoint doesn't contain * sign.
			if ( false === stripos( $path, '*' ) ) {
				$path = untrailingslashit( $path );

				if ( $path === $request_uri && ( ! isset( $method ) || $method === $request_method ) ) {
					return true;
				}
			} else {
				$regex = '/' . str_replace( '/', '\/', $path ) . '/';

				if ( preg_match( $regex, $request_uri ) && ( ! isset( $method ) || $method === $request_method ) ) {
					return true;
				}
			}
		}

		return false;
	}

	public function rest_pre_dispatch( $result, WP_REST_Server $server, WP_REST_Request $request ) {

		// ✅ Skip JWT auth for logout-by-id endpoint
		$route = $request->get_route();
		if ( strpos( $route, '/token/logout-by-id' ) !== false ) {
			return $result; // Let WP run your callback with no JWT requirement
		}


		if ( strpos( $route, '/token/create-invoice' ) !== false ) {
			return $result; // Let WP run your callback with no JWT requirement
		}

		if ( null !== $this->jwt_error ) {
			$data   = $this->jwt_error->data;
			$method = $request->get_method();
			if ( $method == 'OPTIONS' ) {
				// only for cors request...
				return $result;
			} else {
				return new WP_REST_Response(
					$data,
					403
				);
			}
		}

		if ( empty( $result ) ) {
			return $result;
		}

		return $result;
	}


	public function get_open_shift_id( $user_id ) {
		$meta_query = array(
			array(
				'key'     => '_shift_status',
				'value'   => 'open',
				'compare' => '='
			)
		);

		$meta_query[] = array(
			'key'     => '_shift_assigned_staff',
			'value'   => $user_id,
			'compare' => '='
		);

		$args = array(
			'post_type'   => 'shifts',
			'post_status' => 'publish',
			'numberposts' => 1,
			'meta_query'  => $meta_query,
			'date_query'  => array(
				array(
					'after'     => date( 'Y-m-d', strtotime( '-7 days' ) ),
					'inclusive' => true,
				)
			),
			'orderby'     => 'date',
			'order'       => 'DESC'
		);

		$shifts = get_posts( $args );

		if ( ! empty( $shifts ) && isset( $shifts[0] ) ) {
			return $shifts[0]->ID;
		}

		return null;
	}

	public function validate_login_pin( WP_REST_Request $request ) {

    	// Get and sanitize PIN
		$emp_login_pin = sanitize_text_field( $request->get_param( 'emp_login_pin' ) );

		// Validate PIN format (exactly 6 digits)
		if ( ! preg_match( '/^\d{6}$/', $emp_login_pin ) ) {
			return new WP_REST_Response(
				[
					'success'    => false,
					'statusCode' => 400,
					'code'       => 'invalid_pin_format',
					'message'    => esc_html__( 'PIN must be exactly 6 digits.', 'pinaka-pos' ),
					'data'       => [],
				],
				400
			);
		}

		// Validate token presence
		/**
		 * Looking for the HTTP_AUTHORIZATION header, if not present just
		 * return the user.
		 */
		$headerkey = apply_filters( 'jwt_auth_authorization_header', 'HTTP_AUTHORIZATION' );
		$auth      = isset( $_SERVER[ $headerkey ] ) ? $_SERVER[ $headerkey ] : false;

		// Double check for different auth header string (server dependent).
		if ( ! $auth ) {
			$auth = isset( $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ) ? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] : false;
		}

		if ( ! $auth ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_no_auth_header',
					'message'    => $this->messages['jwt_auth_no_auth_header'],
					'data'       => array(),
				)
			);
		}

		/**
		 * The HTTP_AUTHORIZATION is present, verify the format.
		 * If the format is wrong return the user.
		 */
		list($token) = sscanf( $auth, 'Bearer %s' );

		if ( ! $token ) {
			return new WP_REST_Response(
				array(
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'jwt_auth_bad_auth_header',
					'message'    => $this->messages['jwt_auth_bad_auth_header'],
					'data'       => array(),
				)
			);
		}

		// Get secret key
		$secret_key = defined( 'SECURE_AUTH_KEY' ) ? SECURE_AUTH_KEY : '';
		if ( empty( $secret_key ) ) {
			return new WP_REST_Response(
				[
					'success'    => false,
					'statusCode' => 500,
					'code'       => 'missing_secret_key',
					'message'    => esc_html__( 'Server authentication configuration error.', 'pinaka-pos' ),
					'data'       => [],
				],
				500
			);
		}

		// Decode JWT safely
		try {
			$alg     = $this->get_alg();
			$payload = JWT::decode( $token, $secret_key, array( $alg ) );
		} catch ( Exception $e ) {
			return new WP_REST_Response(
				[
					'success'    => false,
					'statusCode' => 401,
					'code'       => 'invalid_token',
					'message'    => esc_html__( 'Invalid or expired token.', 'pinaka-pos' ),
					'data'       => [],
				],
				401
			);
		}

		// Extract user ID from token
		if ( empty( $payload->data->user->id ) ) {
			return new WP_REST_Response(
				[
					'success'    => false,
					'statusCode' => 401,
					'code'       => 'invalid_token_payload',
					'message'    => esc_html__( 'Invalid token payload.', 'pinaka-pos' ),
					'data'       => [],
				],
				401
			);
		}

		$payload_user_id = (int) $payload->data->user->id;

		// Find user by PIN
		$users = get_users( [
			'meta_key'   => 'emp_login_pin',
			'meta_value' => $emp_login_pin,
			'number'     => 1,
			'fields'     => 'ID',
		] );

		if ( empty( $users ) ) {
			return new WP_REST_Response(
				[
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'invalid_pin',
					'message'    => esc_html__( 'Invalid PIN or user not found.', 'pinaka-pos' ),
					'data'       => [],
				],
				403
			);
		}

		$user_id = (int) $users[0];
		// Match PIN user with token user
		if ( $payload_user_id !== $user_id ) {
			return new WP_REST_Response(
				[
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'pin_user_mismatch',
					'message'    => esc_html__( 'The provided PIN does not match the logged-in user.', 'pinaka-pos' ),
					'data'       => [],
				],
				403
			);
		}

		$cash_drawer_access = get_user_meta( $user_id, 'cash_drawer_access', true );
		
		if ( !$cash_drawer_access ) {
			return new WP_REST_Response(
				[
					'success'    => false,
					'statusCode' => 403,
					'code'       => 'no_cash_drawer_access',
					'message'    => esc_html__( 'User does not have cash drawer access.', 'pinaka-pos' ),
					'data'       => [],
				],
				403
			);
		}

		// Success
		return new WP_REST_Response(
			[
				'success'    => true,
				'statusCode' => 200,
				'code'       => 'valid_login_pin',
				'message'    => esc_html__( 'Valid login PIN.', 'pinaka-pos' ),
				'data'       => [],
			],
			200
		);
	}

	function pinaka_create_invoice( WP_REST_Request $request ) {
		$logger      = wc_get_logger();
		$log_context = array( 'source' => 'pinaka-invoice' );
		// Get full request JSON
		$request_data = $request->get_json_params();
		// Log request + token
		$logger->info(
			'Logger → request data: ' . print_r( $request_data, true ) .
			' | Token: ' . $token,
			$log_context
		);
		
		return new WP_REST_Response(
			array(
				'success' => true,
				'message' => 'Invoice creation endpoint hit successfully.',
			),
			200
		 );
	}

}
