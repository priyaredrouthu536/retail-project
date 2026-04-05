<?php
/**
 * The login screen customization functionality of the plugin.
 *
 * @link       https://www.pinaka.com/
 * @since      1.0.0
 *
 * @package    Pinaka_Pos
 * @subpackage Pinaka_Pos/admin
 */

// If this file is called directly, abort.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Pinaka_Login_Customizer {

	private $plugin_name;
	private $version;

	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version     = $version;

		// Add all login screen actions
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_login_styles' ) );
		add_action( 'login_header', array( $this, 'print_login_header_wrapper' ) );
		add_action( 'login_footer', array( $this, 'print_login_footer_wrapper' ) );

		// Logo link/text
		add_filter( 'login_headerurl', array( $this, 'login_logo_url' ) );
		add_filter( 'login_headertext', array( $this, 'login_logo_title' ) );

		// ❌ REMOVED USER LOGIN TITLE
		// add_filter( 'login_message', array( $this, 'login_custom_title' ) );
	}

	/**
	 * Enqueue CSS (inline) & Google Fonts
	 */
	public function enqueue_login_styles() {

		// Use full media URL (temporary)
$logo_url = 'https://merchantretail.alektasolutions.com/wp-content/uploads/2025/12/pinaka-logo.png';
$logo_url_esc = esc_url( $logo_url );

		wp_enqueue_style(
			'pinaka-login-google-fonts',
			'https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap',
			array(),
			null
		);

		// Inline CSS
		$css = <<<CSS
:root{
  --left-bg: #1a3c71;
  --accent-red: #d82929;
  --text-dark: #111315;
  --link-goback: #4c5f7d;
  --card-bg: #ffffffff;
  --input-border: #4c5f7d;
  --button-bg: #1a3c71;
  --button-text: #ffffff;
  --card-radius: 10px;
  --card-shadow: 0 10px 30px rgba(17,19,21,0.06);
  --font-family: "Poppins", system-ui, sans-serif;
}

html, body { height:100%; margin:0; background:#ffffff; }
body.login { min-height:100vh; display:flex; background:#fff !important; }

.pinaka-login-split { display:flex; width:100%; min-height:100vh; }

.pinaka-left {
	width:50%;
	background:var(--left-bg);
	display:flex;
	align-items:center;
	justify-content:center;
	padding:40px;
	box-sizing:border-box;
}
/* Fix password visibility eye icon alignment */
.login form .input, 
#login input[type="password"] {
    line-height: 46px !important; 
}

.login .wp-hide-pw {
    top: 40% !important;
    transform: translateY(-50%) !important;
}


.pinaka-logo {
	width:60%;
	max-width:360px;
	height:220px;
	background-image:url("{$logo_url_esc}");
	background-repeat:no-repeat;
	background-size:contain;
	background-position:center;
}

.pinaka-right {
	width:50%;
	padding:64px 80px;
	display:flex;
	flex-direction:column;
	align-items:center;
}

#login {
  width:100% !important;
  max-width:420px !important;
  background:var(--card-bg) !important;
  border-radius:var(--card-radius);
  padding:32px !important;
  box-sizing:border-box;
}

.login h1 a { display:none !important; }

#login label {
  font-size:14px;
  color:var(--text-dark);
  margin-bottom:8px;
}

#login input[type="text"],
#login input[type="password"] {
  width:100%;
  height:46px;
  border:1px solid var(--input-border);
  border-radius:8px;
  padding:10px 14px;
  font-size:15px;
  margin-top:6px;
}

#login input::placeholder { color:#cfcfd4; }

a[href*="lostpassword"] {
  color:var(--accent-red) !important;
  font-size:14px;
  text-decoration:none;
}

#login .submit {
    text-align:center !important;
}

#login .button-primary {
    width:70% !important;
    height:46px !important;
    border-radius:8px !important;
    background:var(--button-bg) !important;
    color:var(--button-text) !important;
    font-size:18px !important;
    font-weight:600 !important;
    text-align:center;
    border:none !important;
    display:inline-block !important;
    float:none !important;
    margin-top:20px;
}

#backtoblog a {
  color:var(--link-goback) !important;
  font-size:15px;
  text-decoration:none;
}
#backtoblog a::before {
  content:"←";
  margin-right:8px;
  color:var(--link-goback);
}

@media(max-width:980px){
  .pinaka-left{ display:none; }
  .pinaka-right{ width:100%; padding:40px; }
}
CSS;

		printf( '<style>%s</style>', $css );
	}

	public function print_login_header_wrapper() {
		echo '
		<div class="pinaka-login-split">
			<div class="pinaka-left">
				<div class="pinaka-logo" role="img" aria-label="' . esc_attr( get_bloginfo( 'name' ) ) . '"></div>
			</div>
			<div class="pinaka-right">
		';
	}

	public function print_login_footer_wrapper() {
		echo '
			</div><!-- .pinaka-right -->
		</div><!-- .pinaka-login-split -->
		';
	}

	/**
	 * ❌ Modified so heading does NOT show even if filter added later
	 */
	public function login_custom_title( $message ) {
		return $message; // No heading added
	}

	public function login_logo_url() {
		return home_url();
	}

	public function login_logo_title() {
		return get_bloginfo( 'name' );
	}
}
