<?php
/**
 * Plugin Name: 			#Triibo_Auth
 * Plugin URI: 				https://triibo.com.br
 * Description: 			Disponibiliza o login pela Triibo, força o login Triibo antes do checkout, login oriundo do Triibo App. Dependências: Triibo API Services e WooCommerce.
 * Author: 					Mateus Costa
 * Author URI: 				https://costamateus.com.br/
 * Version: 				2.2.1
 * Text Domain: 			triibo_auth
 * Requires PHP: 			7.4
 * Requires at least: 		6.0
 * Tested up to: 			6.3
 * WC requires at least: 	6.5
 * WC tested up to: 		8.1
 *
 * @package 	Triibo_Auth
 * @version 	2.2.1
 */
defined( "ABSPATH" ) || exit;

if ( !in_array( "woocommerce/woocommerce.php", apply_filters( "active_plugins", get_option( "active_plugins" ) ) ) ) return;

// Plugin constants.
define( "TRIIBO_AUTH_ID",           "triibo-auth"          );
define( "TRIIBO_AUTH_APP",          "triibo-auth-app"      );
define( "TRIIBO_AUTH_CHECKOUT",     "triibo-auth-checkout" );
define( "TRIIBO_AUTH_LOGIN",        "triibo-auth-login"    );
define( "TRIIBO_AUTH_DOMAIN",       "triibo_auth"          );
define( "TRIIBO_AUTH_VERSION",      "2.2.1"                );
define( "TRIIBO_AUTH_PLUGIN_FILE",  __FILE__               );

if ( !class_exists( "Triibo_Auth" ) )
{
	include_once dirname( __FILE__ ) . "/includes/class-triibo-auth.php";
	add_action( "plugins_loaded", [ "Triibo_Auth", "get_instance" ] );
}


add_filter( "determine_current_user", "triibo_auth_json_basic_auth_handler", 20 );
function triibo_auth_json_basic_auth_handler( $user )
{
	global $wp_json_basic_auth_error;

	$wp_json_basic_auth_error = null;

	// Don't authenticate twice
	if ( !empty( $user ) ) return $user;

	// Check that we're trying to authenticate
	if ( !isset( $_SERVER[ "PHP_AUTH_USER" ] ) ) return $user;

	$username = $_SERVER[ "PHP_AUTH_USER" ];
	$password = $_SERVER[ "PHP_AUTH_PW"   ];

	remove_filter( "determine_current_user", "triibo_auth_json_basic_auth_handler", 20 );

	$user     = wp_authenticate( $username, $password );

	add_filter( "determine_current_user", "triibo_auth_json_basic_auth_handler", 20 );

	if ( is_wp_error( $user ) )
	{
		$wp_json_basic_auth_error = $user;
		return null;
	}

	$wp_json_basic_auth_error = true;

	return $user->ID;
}

add_filter( "rest_authentication_errors", "triibo_auth_json_basic_auth_error" );
function triibo_auth_json_basic_auth_error( $error )
{
	if ( !empty( $error ) ) return $error;

	global $wp_json_basic_auth_error;
	return $wp_json_basic_auth_error;
}