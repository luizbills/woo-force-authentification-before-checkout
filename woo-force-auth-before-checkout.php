<?php
/*
Plugin Name: WooCommerce Force Authentification Before Checkout
Description: Force customer to log in or register before checkout
Version: 1.2.1
Author: Luiz Bills
Author URI: https://luizpb.com/

License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

Text Domain: wc-force-auth
Domain Path: /languages
*/

if ( ! defined( 'WPINC' ) ) die();

class WC_Force_Auth_Before_Checkout {

	const FILE = __FILE__;
	const URL_ARG = 'redirect_to_checkout';
	const COOKIE = 'woocommerce_redirect_to_checkout';

	protected static $_instance = null;

	protected function __construct () {
		add_action( 'init', [ $this, 'load_plugin_textdomain' ] );
		add_action( 'wp_loaded', [ $this, 'maybe_set_cookie' ] );

		add_action( 'template_redirect', [ $this, 'user_redirect' ] );
		add_action( 'wp_head', [ $this, 'add_notice' ] );

		add_filter( 'woocommerce_registration_redirect', [ $this, 'registration_redirect' ], 100 );
		add_filter( 'woocommerce_login_redirect', [ $this, 'login_redirect' ], 100 );
	}

	public function maybe_set_cookie () {
		if ( ! is_user_logged_in() && isset( $_GET[ self::URL_ARG ] ) ) {
			setcookie( self::COOKIE, '1', 0, '/' );
		}
	}

	public function user_redirect () {
		if( is_checkout() && ! is_user_logged_in() ) {
			$url = get_permalink( get_option( 'woocommerce_myaccount_page_id' ) );
			$url = add_query_arg( self::URL_ARG, '', $url );
			wp_safe_redirect( esc_url( $url ) );
			die;
		}
		if ( is_account_page() && is_user_logged_in() && ( isset( $_GET[ self::URL_ARG ] ) || isset( $_COOKIE[ self::COOKIE ] ) ) )  {
			setcookie( self::COOKIE, '1', time() - 1, '/' );
			$url = wc_get_checkout_url();
			wp_safe_redirect( esc_url( $url ) );
			die;
		}
	}

	public function registration_redirect ( $redirect ) {
		if ( isset( $_GET[ self::URL_ARG ] ) ) {
			return wc_get_checkout_url();
		}
		return $redirect;
	}

	public function login_redirect ( $redirect ) {
		if ( isset( $_GET[ self::URL_ARG ] ) ) {
			return wc_get_checkout_url();
		}
		return $redirect;
	}

	public function add_notice () {
		if ( is_account_page() && ! is_user_logged_in() && $this->has_items_in_cart() ) {
			wc_add_notice( $this->get_message(), 'notice' );
		}
	}

	public function get_message () {
		return apply_filters( 'wc_force_auth_message', __( 'Please log in or register to complete your purchase.', 'wc-force-auth' ) );
	}

	protected function has_items_in_cart () {
		return isset( $_COOKIE['woocommerce_items_in_cart'] );
	}

	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'wc-force-auth', false, dirname( plugin_basename( self::FILE ) ) . '/languages/' );
	}

	public static function get_instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}
		return self::$_instance;
	}
}

add_action( 'plugins_loaded', function () {
	if ( function_exists( 'WC' ) ) {
		WC_Force_Auth_Before_Checkout::get_instance();
	}
} );
