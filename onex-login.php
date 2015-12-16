<?php
/**
* Plugin Name: One Express Login and Register Plugin
*
*/
class Onex_Login_Register_Plugin{

	public function __construct(){
		add_action( 'login_form_login', array( $this, 'redirect_to_custom_login') );
		add_action( 'wp_logout', array( $this, 'redirect_after_logout') );
		add_action( 'login_form_register', array( $this, 'redirect_to_custom_register') );
		add_action( 'login_form_register', array( $this, 'do_register_user' ));
		add_filter( 'authenticate', array( $this, 'maybe_redirect_at_authenticate'), 101, 3 );
		add_filter( 'login_redirect', array( $this, 'redirect_after_login'), 10, 3);
		add_shortcode( 'onex-login-form', array($this, 'render_login_form') );
		add_shortcode( 'onex-register-form', array( $this, 'render_register_form') );
	}

	public static function plugin_activated(){
		$page_definitions =
			array(
				'member-login' =>
					array(
						'title' => __( 'Sign In', 'onex-login'),
						'content' => '[onex-login-form]'
					),
				/*'member-account' =>
					array(
						'title' => __( 'Your Account', 'onex-login'),
						'content' => '[onex-account-info]'
					),*/
				'member-register' =>
					array(
						'title' => __( 'Register', 'onex-login'),
						'content' => '[onex-register-form]'
					)
			);

		foreach ( $page_definitions as $slug => $page ){
			$query = new WP_Query( 'pagename=' . $slug );

			if ( !$query->have_posts()){
				wp_insert_post(
					array(
						'post_content' => $page['content'],
						'post_name' => $slug,
						'post_title' => $page['title'],
						'post_status' => 'publish',
						'post_type' => 'page',
						'ping_status' => 'closed',
						'comment_status' => 'closed'
					)
				);
			}
		}
	}

	public function render_login_form( $attributes, $content = null ){
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		//$show_title = $attributes['show_title'];

		if( is_user_logged_in() ) {
			return __( 'You are already signed in.', 'onex-login');
		}

		// Pass the redirect parameter to the WordPress login functionality: by default,
		// don't specify a redirect, but if a valid redirect URL has been passed as
		// requrest parameter, use it.
		$attributes['redirect'] = '';
		if( isset($_REQUEST['redirect_to']) ) {
			$attributes['redirect'] = wp_validate_redirect( $_REQUEST['redirect_to'], $attributes['redirect'] );
		}

		// Error message
		$errors = array();
		if ( isset( $_REQUEST['login'] )) {
			$error_codes = explode( ',', $_REQUEST['login'] );

			foreach ( $error_codes as $code ) {
				$errors []= $this->get_error_message( $code );
			}
		}
		$attributes['errors'] = $errors;

		// Check if user just logged out
		$attributes['logged_out'] = isset( $_REQUEST['logged_out'] ) && $_REQUEST['logged_out'] == true;

		$attributes['registered'] = isset( $_REQUEST['registered'] );

		// Render the login form using an external template
		return $this->get_template_html( 'login_form', $attributes );
	}

	public function render_register_form( $attributes, $content = null ){
		// Parse shortcode attributes
		$default_attributes = array( 'show_title' => false );
		$attributes = shortcode_atts( $default_attributes, $attributes );
		//$show_title = $attributes['show_title'];

		if( is_user_logged_in() ) {
			return __( 'Anda sudah login.', 'onex-login');
		}else if( !get_option('users_can_register') ){
			return __( 'Untuk saat ini tidak dapat melakukan registrasi.', 'onex-login');
		}else{

			$attributes['errors'] = array();
			if( isset( $_REQUEST['register-errors']) ){
				$error_codes = explode( ',', $_REQUEST['register-errors']);

				foreach ( $error_codes as $error_code ) {
					$attributes['errors'][] = $this->get_error_message( $error_code );
				}
			}

			return $this->get_template_html( 'register_form', $attributes );
		}
		
	}

	private function get_template_html( $template_name, $attributes = null ) {
		if ( ! $attributes ) {
			$attributes = array();
		}

		ob_start();

		do_action( 'onex_login_before_' . $template_name );

		require( 'templates/' . $template_name . '.php');

		do_action( 'onex_login_after_' . $template_name );

		$html = ob_get_contents();
		ob_end_clean();

		return $html;
	}

	function redirect_to_custom_login(){
		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? $_REQUEST['redirect_to'] : null;
		if( $_SERVER['REQUEST_METHOD'] == 'GET' ){

			if( is_user_logged_in() ){
				$this->redirect_logged_in_user( $redirect_to );
				exit;
			}

			// The rest are redirected to the login page
			$login_url = home_url( 'member-login');
			if( !empty( $redirect_to )) {
				$login_url = add_query_arg( 'redirect_to', $redirect_to, $login_url );
			}

			wp_redirect( $login_url );
			exit;

		}
	}

	public function redirect_to_custom_register(){
		if( 'GET' == $_SERVER['REQUEST_METHOD'] ) {
			if( is_user_logged_in() ){
				$this->redirect_logged_in_user();
			}else{
				wp_redirect( home_url( 'member-register' ) );
			}
			exit;
		}
	}

	private function redirect_logged_in_user( $redirect_to = null ){
		$user = wp_get_current_user();
		//var_dump(user_can( $user, 'manage_options' ));
		if( user_can( $user, 'manage_options' ) ){
			if( $redirect_to ) {
				wp_safe_redirect( $redirect_to );
			} else {
				wp_redirect( admin_url() );
			}
		}else {
			wp_redirect( home_url( 'member-account' ) );
		}
	}

	private function register_user( $email, $first_name, $last_name ) {
		$errors = new WP_Error();

		if ( ! is_email($email) ) {
			$errors->add( 'email', $this->get_error_message( 'email' ));
			return $errors;
		}

		if ( username_exists( $email ) || email_exists( $mail ) ) {
			$errors->add( 'email_exists', $this->get_error_message( 'email_exists'));
			return $errors;
		}

		$password = wp_generate_password( 12, false );

		$user_data = array(
			'user_login' => $email,
			'user_email' => $email,
			'user_pass'  => $password,
			'first_name' => $first_name,
			'last_name'  => $last_name,
			'nickname'   => $first_name,
		);

		$user_id = wp_insert_user( $user_data );
		wp_new_user_notification ( $user_id, $password );

		return $user_id;
	}

	public function do_register_user(){
		if( 'POST' == $_SERVER['REQUEST_METHOD'] ){
			$redirect_url = home_url( 'member-register' );

			if( ! get_option( 'users_can_register' ) ){
				$redirect_url = add_query_arg( 'register-errors', 'closed', $redirect_url );
			}else {
				$email = $_POST['email'];
				$first_name = sanitize_text_field( $_POST['first_name'] );
				$last_name = sanitize_text_field( $_POST['last_name'] );
				//$username = sanitize_text_field( $_POST['username'] );

				$result = $this->register_user( $email, $first_name, $last_name );
				//$result = $this->register_user( $email, $user_name, $first_name, $last_name );
			}

			if( is_wp_error( $result )) {
				$errors = join( ',', $result->get_error_codes() );
				$redirect_url = add_query_arg( 'register-errors', $error, $redirect_url );
			}else{
				$redirect_url = home_url( 'member-login' );
				$redirect_url = add_query_arg( 'registered', $email, $redirect_url );
			}
		}

		wp_redirect( $redirect_url );
		exit;
	}

	function maybe_redirect_at_authenticate( $user, $username, $password ){
		// Check if the earlier authenticate filter (most likely,
		// the default WordPress authentication) functions have found errors
		if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
			if( is_wp_error( $user ) ) {
				$error_codes = join( ',', $user->get_error_codes() );

				$login_url = home_url( 'member-login' );
				$login_url = add_query_arg( 'login', $error_codes, $login_url );

				wp_redirect( $login_url );
				exit;
			}
		}
		return $user;
	}

	private function get_error_message( $error_code ) {
		switch( $error_code ){
			case 'empty_username':
				return __( 'Kolom Username belum diisi.', 'onex-login');
			case 'empty_password':
				return __( 'Kolum Password belum diisi', 'onex-login');
			case 'invalid_username':
				return __( "Username tidak ditemukan", 'onex-login');
			case 'incorrect_password':
				$err = __(
					"Password salah. <a href='%s'>Lupa Password</a>?", 'onex-login'
				);
				return sprintf( $err, wp_lostpassword_url() );
			case 'email':
				return __( 'Email tidak valid.', 'onex-login');
			case 'email_exists':
				return __( 'Email sudah pernah digunakan untuk registrasi.', 'onex-login');
			case 'closed':
				return __( 'Untuk saat ini tidak dapat melakukan registrasi.', 'onex-login');
			default:
				break;
		}

		return __( 'Terjadi kesalahan. Mohon tunggu beberapa saat lagi.', 'onex-login');
	}

	public function redirect_after_logout(){
		$redirect_url = home_url( 'member-login?logged_out=true' );
		wp_safe_redirect( $redirect_url );
		exit;
	}

	public function redirect_after_login( $redirect_to, $requested_redirect_to, $user ){
		$redirect_url = home_url();

		if (! isset( $user->ID )) {
			return $redirect_url;
		}

		if( user_can ($user, 'manage_options') ) {
			// Use the redirect_to parameter if one is set, otherwise redirect to admin dashboard.
			if ( $requested_redirect_to == '' ) {
				$redirect_url = admin_url();
			} else{
				$redirect_url = $redirect_to;
			}
		}else {
			// Non-admin users always go to their account page after login
			$redirect_url = home_url( 'member-account' );
		}

		return wp_validate_redirect( $redirect_url, home_url() );
	}
}

$onex_login_register_plugin_obj = new Onex_Login_Register_Plugin();

register_activation_hook( __FILE__, array('Onex_Login_Register_Plugin', 'plugin_activated') );
?>