<?php
/**
 * @link 			https://triibo.com.br
 * @since 			1.0.0
 * @package 		Triibo_Auth
 *
 * Plugin Name: 	#Triibo_Auth
 * Plugin URI: 		https://triibo.com.br
 * Description: 	Disponibiliza o login pela Triibo, força o login Triibo antes do checkout, login oriundo do Triibo App. Dependências: Triibo API Services e WooCommerce.
 * Version: 		1.0.2
 * Author: 			Mateus Costa
 * Author URI: 		https://costamateus.com.br/
 * Text Domain: 	triibo_auth
 */
defined( "ABSPATH" ) || exit;

if ( !in_array( "woocommerce/woocommerce.php", apply_filters( "active_plugins", get_option( "active_plugins" ) ) ) ) return;

// Plugin constants.
define( "TRIIBO_AUTH_ID",           "triibo-auth"          );
define( "TRIIBO_AUTH_APP",          "triibo-auth-app"      );
define( "TRIIBO_AUTH_CHECKOUT",     "triibo-auth-checkout" );
define( "TRIIBO_AUTH_LOGIN",        "triibo-auth-login"    );
define( "TRIIBO_AUTH_DOMAIN",       "triibo_auth"          );
define( "TRIIBO_AUTH_VERSION",      "1.0.2"                );
define( "TRIIBO_AUTH_PLUGIN_FILE",  __FILE__               );

if ( !class_exists( "Triibo_Auth" ) )
{
	include_once dirname( __FILE__ ) . "/includes/class-triibo-auth.php";
	add_action( "plugins_loaded", [ "Triibo_Auth", "get_instance" ] );
}
