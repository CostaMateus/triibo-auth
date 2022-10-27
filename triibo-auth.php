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
			wp_enqueue_style(  self::DOMAIN, dirname( __FILE__ ) . "/assets/css/admin.css", [],           self::VERSION, "all" );
			wp_enqueue_script( self::DOMAIN, dirname( __FILE__ ) . "/assets/js/admin.js",   [ "jquery" ], self::VERSION, false );

			add_action( "admin_init", 					 [ $this, "register_and_build_fields" ]     );
			add_action( "admin_menu",                    [ $this, "add_submenu_page"          ], 11 );
			add_action( "triibo_api_service_add_button", [ $this, "add_btn"                   ], 11 );

			add_filter( "plugin_action_links_" . plugin_basename( __FILE__ ), [ $this, "plugin_action_links" ] );

			self::includes();
			self::init_classes();
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
	 * Includes.
	 */
	private static function includes()
    {
		require_once( dirname( __FILE__ ) . "/includes/rest-api/class-triibo-auth-rest-api.php" );

		// require_once( dirname( __FILE__ ) . "/includes/class-triibo-auth-app.php"      );
		// require_once( dirname( __FILE__ ) . "/includes/class-triibo-auth-checkout.php" );
		// require_once( dirname( __FILE__ ) . "/includes/class-triibo-auth-login.php"    );
    }

	/**
	 * Init classes
	 */
	private static function init_classes()
	{
		new Triibo_Auth_Rest_Api();
	}

	/**
	 * Triibo-Api-Service missing notice.
	 */
	public static function triibo_api_services_missing_notice()
	{
		require_once( dirname( __FILE__ ) . "/templates/notices/missing-triibo_api_services.php" );
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
		// include_once dirname( __FILE__ ) . "/templates/admin-page.php";

		if ( !current_user_can( "manage_options" ) ) wp_die( __( "Você não tem permissões suficientes para acessar esta página." ) );

		$active_tab = isset( $_GET[ "tab" ] ) ? $_GET[ "tab" ] : "general";

		if ( isset( $_GET[ "error_message" ] ) )
		{
			add_action( "admin_notices", [ $this, self::DOMAIN . "_settings_messages" ] );
			do_action( "admin_notices", $_GET[ "error_message" ] );
		}

		require_once( dirname( __FILE__ ) . "/templates/admin-page.php" );
	}

	public function add_btn()
	{
        $url  = esc_url( admin_url( "admin.php?page=" . self::DOMAIN . "-settings" ) );
		$text = __( "Configurações", self::DOMAIN );
		$link = "<a href='{$url}' >{$text}</a>";

		echo "<p>Triibo Auth | {$link}</p>";
	}

	/**
	 * Register and build the plugin fields
	 *
	 * @since 	1.0.0
	 */
	public function register_and_build_fields()
	{
		$this->prefix = self::DOMAIN . "_";
		$this->sufix  = "_section";

		$this->page   = $this->prefix . "settings";

		$resources    = [
			"login"    => [
				[
					"key"   => "login_status",
					"title" => "Ativar / Desativar",
					"label" => "Ativar recurso",
					"value" => true,
				],
			],
			"checkout" => [
				[
					"key"   => "checkout_status",
					"title" => "Ativar / Desativar",
					"label" => "Ativar recurso",
					"value" => true,
				],
			],
			"app"      => [
				[
					"key"   => "app_status",
					"title" => "Ativar / Desativar",
					"label" => "Ativar recurso",
					"value" => true,
				],
			]
		];

		register_setting( $this->page, $this->prefix . "options" );

		$this->add_allowed_options( $this->page, $resources );

		add_settings_section( $this->prefix . "login"    . $this->sufix, "Login",    [ $this, "message_section" ], $this->page );
		add_settings_section( $this->prefix . "checkout" . $this->sufix, "Checkout", [ $this, "message_section" ], $this->page );
		add_settings_section( $this->prefix . "app"      . $this->sufix, "App",      [ $this, "message_section" ], $this->page );

		foreach ( $resources as $res => $fields )
		{
			foreach ( $fields as $index => $field )
			{
				$this->add_settings_field( $res, $index, $field );
				$this->register_settings( $field );
			}
		}
	}

	/**
	 * Adds plugin fields to allowed options
	 *
	 * @since 	1.0.0
	 *
	 * @param string $page
	 * @param array $data
	 */
	public function add_allowed_options( $page, $data )
	{
		$new_options = [ $page => [] ];

		foreach ( $data as $fields )
			foreach ( $fields as $field )
				$new_options[ $page ][] = $this->prefix . $field[ "key" ];

		add_allowed_options( $new_options );
	}

	/**
	 * Configure each plugin field
	 *
	 * @since 	1.0.0
	 *
	 * @param string $section
	 * @param string $index
	 * @param array $field
	 */
	public function add_settings_field( $section, $index, $field )
	{
		$id      = $this->prefix . $field[ "key" ];
		$title   = $field[ "title" ];
		$section = $this->prefix . $section . $this->sufix;

		add_settings_field(
            $id, $title, [ $this, "render_settings_field" ], $this->page, $section,
			[
				"type"             => "input",
				"subtype"          => "checkbox",        // ( in_array( $index, [ 0, 1 ] ) ) ? "checkbox"        : "text",
				"label"            => $field[ "label" ], // ( in_array( $index, [ 0, 1 ] ) ) ? $field[ "label" ] : "",
				"value"            => $field[ "value" ], // ( $index == 1 ) ? $field[ "value" ] : "",
				"id"               => $id,
				"name"             => $id,
				"required"         => true,
				"get_options_list" => "",
				"value_type"       => "normal",
				"wp_data"          => "option"
			]
		);
	}

	/**
	 * Register each plugin field
	 *
	 * @since 	1.0.0
	 *
	 * @param array $field
	 */
	public function register_settings( $field )
	{
		$name = $this->prefix . $field[ "key" ];

		register_setting( $this->page, $name );
	}

	/**
	 * Renders each field according to the configured args
	 *
	 * @since 	1.0.0
	 *
	 * @param array $args
	 */
	public function render_settings_field( $args )
	{
		if ( $args[ "wp_data" ] == "option" )
		{
			$wp_data_value = get_option( $args[ "name" ] );
		}
		elseif ( $args[ "wp_data" ] == "post_meta" )
		{
			$wp_data_value = get_post_meta( $args[ "post_id" ], $args[ "name" ], true );
		}

		switch ( $args[ "type" ] )
		{
			case "input":
				$status = 0;

				if ( strpos( $args[ "id" ], "login"    ) !== false ) $status = get_option( "triibo_auth_login_status"    );
				if ( strpos( $args[ "id" ], "checkout" ) !== false ) $status = get_option( "triibo_auth_checkout_status" );
				if ( strpos( $args[ "id" ], "app"      ) !== false ) $status = get_option( "triibo_auth_app_status"      );

				$value = ( $args[ "value_type" ] == "serialized" ) ? serialize( $wp_data_value ) : $wp_data_value;

				if ( $args[ "subtype" ] != "checkbox")
				{
					$prependStart = ( isset( $args[ "prepend_value" ] ) ) ? "<div class='input-prepend'> <span class='add-on'>{$args[ "prepend_value" ]}</span>" : "";
					$prependEnd   = ( isset( $args[ "prepend_value" ] ) ) ? "</div>"                   : "";
					$step         = ( isset( $args[ "step"          ] ) ) ? "step='{$args[ "step" ]}'" : "";
					$min          = ( isset( $args[ "min"           ] ) ) ?  "min='{$args[ "min"  ]}'" : "";
					$max          = ( isset( $args[ "max"           ] ) ) ?  "max='{$args[ "max"  ]}'" : "";
					$required     = ( $args[ "required" ] && $status    ) ? "required='required'"      : "";

					if ( isset( $args[ "disabled" ] ) )
					{
						// hide the actual input bc if it was just a disabled input the info saved in the database would be wrong - bc it would pass empty values and wipe the actual information
						echo $prependStart . "
							<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}_disabled' {$step} {$max} {$min} name='{$args[ "name" ]}_disabled' disabled value='" . esc_attr( $value ) . "' />
							<input type='hidden' id='{$args[ "id" ]}' {$step} {$max} {$min} name='{$args[ "name" ]}' value='" . esc_attr( $value ) . "' />" . $prependEnd;
					}
					else
					{
						echo $prependStart . "
							<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}' {$required} {$step} {$max} {$min} name='{$args[ "name" ]}' value='" . esc_attr( $value ) . "' />" . $prependEnd;
					}
				}
				else
				{
					$checked = ( $value ) ? "checked" : "";
					echo "<input type='{$args[ "subtype" ]}' id='{$args[ "id" ]}' name='{$args[ "name" ]}' {$checked} />";
					echo "<label for='{$args[ "id" ]}' >{$args[ "label" ]}</label>";
				}
			break;
		}
	}

	/**
	 * Echos out any content at the top of the section (between heading and fields).
	 *
	 * @since 	1.0.0
	 *
	 * @param array $args
	 */
	public function message_section( $args )
	{
		if ( strpos( $args[ "id" ], "login"    ) !== false ) echo "<p>Login pela Triibo</p>";
		if ( strpos( $args[ "id" ], "checkout" ) !== false ) echo "<p>Login Triibo antes do Checkout</p>";
		if ( strpos( $args[ "id" ], "app"      ) !== false ) echo "<p>Login pelo App</p>";
	}

}

add_action( "plugins_loaded", [ "Triibo_Auth", "get_instance" ], 222 );
