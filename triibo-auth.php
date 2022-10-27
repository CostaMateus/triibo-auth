<?php
/**
 * @link 			https://triibo.com.br
 * @since 			1.0.0
 * @package 		Triibo_Auth
 *
 * Plugin Name: 	#Triibo_Auth
 * Plugin URI: 		https://triibo.com.br
 * Description: 	Disponibiliza o login pela Triibo, força o login Triibo antes do checkout, login oriundo do Triibo App. Dependências: Triibo API Services e WooCommerce.
 * Version: 		1.0.0
 * Author: 			Mateus Costa
 * Author URI: 		https://costamateus.com.br/
 * Text Domain: 	triibo_auth
 */
defined( "ABSPATH" ) || exit;

if ( !in_array( "woocommerce/woocommerce.php", apply_filters( "active_plugins", get_option( "active_plugins" ) ) ) ) return;

class Triibo_Auth
{
	/**
	 * Plugin version.
	 *
	 * @var string
	 */
	const VERSION = "1.0.0";

	const DOMAIN  = "triibo_auth";

	/**
	 * Instance of this class.
	 *
	 * @var object
	 */
	protected static $instance = null;

	private function __construct()
    {
		if ( class_exists( "Triibo_Api_Services" ) )
		{
			add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), [ $this, "plugin_action_links" ] );

			add_action( "admin_menu",                       [ $this, "add_submenu_page"    ], 11    );
			add_action( "triibo_api_service_add_button",    [ $this, "add_btn"             ], 11    );
        }
		else
		{
			add_action( "admin_notices", [ $this, "triibo_api_services_missing_notice" ] );
		}
    }

	/**
	 * Return an instance of this class.
	 *
	 * @return object A single instance of this class.
	 */
	public static function get_instance()
    {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Triibo-Api-Service missing notice.
	 */
	public static function triibo_api_services_missing_notice()
	{
		include_once dirname( __FILE__ ) . "/templates/notices/missing-triibo_api_services.php";
	}

	/**
	 * Action links.
	 *
	 * @param 	array $links
	 * @return 	array
	 */
	public static function plugin_action_links( $links )
    {
        $url            = esc_url( admin_url( "admin.php?page=" . self::DOMAIN . "-settings" ) );
        $text           = __( "Configurações", self::DOMAIN );
		$plugin_links   = [];
		$plugin_links[] = "<a href='{$url}' >{$text}</a>";

		return array_merge( $plugin_links, $links );
	}

	public function add_submenu_page()
	{
		add_submenu_page(
			Triibo_Api_Services::get_name(),
			"Triibo Auth",
			"Auth",
			"manage_options",
			self::DOMAIN . "-settings",
			[ $this, "display_admin_menu_settings" ]
		);
	}

	public function display_admin_menu_settings()
	{
		include_once dirname( __FILE__ ) . "/templates/admin-page.php";
	}

	public function add_btn()
	{
        $url  = esc_url( admin_url( "admin.php?page=" . self::DOMAIN . "-settings" ) );
		$text = __( "Configurações", self::DOMAIN );
		$link = "<a href='{$url}' >{$text}</a>";

		echo "<p>Triibo Auth | {$link}</p>";
	}

}

add_action( "plugins_loaded", array( "Triibo_Auth", "get_instance" ), 222 );
