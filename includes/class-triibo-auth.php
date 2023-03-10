<?php
/**
 * Plugin's main class
 *
 * @author  Mateus_Costa
 * @package WooCommerce_SupremPay
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

/**
 * Triibo_Auth bootstrap class
 */
class Triibo_Auth
{
	/**
	 * Admin config prefix
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	protected $prefix = "";

	/**
	 * Admin config sufix
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	protected $sufix  = "";

	/**
	 * Admin config page
	 *
	 * @since 	1.0.0
	 * @var 	string
	 */
	protected $page   = "";

	/**
	 * Instance of this class.
	 *
	 * @since 	1.0.0
	 * @var 	object
	 */
	protected static $instance = null;

	/**
	 * Construct
	 *
	 * @since 	1.0.0
	 */
	private function __construct()
    {
		if ( class_exists( "Triibo_Api_Services" ) )
		{
			$this->enqueue_files();

			$this->add_actions();
			$this->add_filters();

			$this->includes();
			$this->init_classes();
        }
		else
		{
			add_action( "admin_notices", [ $this, "triibo_api_services_missing_notice" ] );
		}
    }

	/**
	 * Return an instance of this class.
	 *
	 * @since 	1.0.0
	 * @return 	object A single instance of this class.
	 */
	public static function get_instance()
    {
		// If the single instance hasn't been set, set it now.
		if ( null === self::$instance ) self::$instance = new self;
		return self::$instance;
	}

	/**
	 * Enqueue admin js and css files
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	private function enqueue_files()
	{
		$js_path  = "assets/js/admin/admin.js";
		$css_path = "assets/css/admin/admin.css";

		$js_ver   = date( "ymd-His", filemtime( plugin_dir_path( TRIIBO_AUTH_PLUGIN_FILE ) . $js_path  ) );
		$css_ver  = date( "ymd-His", filemtime( plugin_dir_path( TRIIBO_AUTH_PLUGIN_FILE ) . $css_path ) );

		wp_enqueue_script( TRIIBO_AUTH_ID . "_js",  plugins_url( $js_path,  TRIIBO_AUTH_PLUGIN_FILE ), [ "jquery" ], $js_ver,  false );
		wp_enqueue_style(  TRIIBO_AUTH_ID . "_css", plugins_url( $css_path, TRIIBO_AUTH_PLUGIN_FILE ), [],           $css_ver, "all" );
	}

	/**
	 * Add plugin actions
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	private function add_actions()
	{
		add_action( "admin_init", 					       [ $this, "register_and_build_fields" ]     );
		add_action( "admin_menu",                          [ $this, "add_submenu_page"          ], 11 );
		add_action( "triibo_api_service_add_button",       [ $this, "add_btn"                   ], 11 );
		add_action( "triibo_api_service_list_plugin_gate", [ $this, "add_info_dependency"       ]     );
		add_action( "wp_footer",                           [ $this, "add_global_css"            ]     );
	}

	/**
	 * Add plugin filters
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	private function add_filters()
	{
		add_filter( "plugin_action_links_" . plugin_basename( TRIIBO_AUTH_PLUGIN_FILE ), [ $this, "plugin_action_links" ] );
	}

	/**
	 * Includes.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	private function includes()
    {
		require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/rest-api/class-triibo-auth-rest-api.php" );
		require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/class-triibo-auth-app.php"               );
		// require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/class-triibo-auth-checkout.php"          );
		// require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/class-triibo-auth-login.php"             );
    }

	/**
	 * Init classes
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	private function init_classes()
	{
		new Triibo_Auth_Rest_Api();

		if ( Triibo_Auth_App::status()      ) new Triibo_Auth_App();
		// if ( Triibo_Auth_Checkout::status() ) new Triibo_Auth_Checkout();
		// if ( Triibo_Auth_Login::status()    ) new Triibo_Auth_Login();
	}

	/**
	 * Triibo-Api-Service missing notice.
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public static function triibo_api_services_missing_notice()
	{
		require_once( dirname( __FILE__ ) . "/templates/notices/missing-triibo_api_services.php" );
	}

	/**
	 * Action links.
	 *
	 * @since 	1.0.0
	 * @param 	array $links
	 * @return 	array
	 */
	public static function plugin_action_links( array $links )
    {
        $url            = esc_url( admin_url( "admin.php?page=" . TRIIBO_AUTH_ID . "-settings" ) );
        $text           = __( "Configurações", TRIIBO_AUTH_ID );
		$plugin_links   = [];
		$plugin_links[] = "<a href='{$url}' >{$text}</a>";

		return array_merge( $plugin_links, $links );
	}

	/**
	 * Add submenu
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function add_submenu_page()
	{
		add_submenu_page(
			Triibo_Api_Services::get_name(),
			"Triibo Auth",
			"Auth",
			"manage_options",
			TRIIBO_AUTH_ID . "-settings",
			[ $this, "display_admin_menu_settings" ]
		);
	}

	/**
	 * Add config page
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function display_admin_menu_settings()
	{
		if ( !current_user_can( "manage_options" ) ) wp_die( __( "Você não tem permissões suficientes para acessar esta página." ) );

		$active_tab = isset( $_GET[ "tab" ] ) ? $_GET[ "tab" ] : "general";

		if ( isset( $_GET[ "error_message" ] ) )
		{
			add_action( "admin_notices", [ $this, TRIIBO_AUTH_ID . "_settings_messages" ] );
			do_action( "admin_notices", $_GET[ "error_message" ] );
		}

		require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/templates/admin-page.php" );
	}

	/**
	 * Add config buttom
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function add_btn()
	{
        $url  = esc_url( admin_url( "admin.php?page=" . TRIIBO_AUTH_ID . "-settings" ) );
		$text = __( "Configurações", TRIIBO_AUTH_ID );
		$link = "<a href='{$url}' >{$text}</a>";

		echo "<p>Triibo Auth | {$link}</p>";
	}

	/**
	 * Add information about dependenc
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function add_info_dependency()
	{
		echo "<p> - Triibo Auth</p>";
	}

	/**
	 * Register and build the plugin fields
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function register_and_build_fields()
	{
		$this->prefix = TRIIBO_AUTH_ID . "_";
		$this->sufix  = "_section";

		$this->page   = $this->prefix . "settings";

		$resources    = [
			"app"      => [
				[
					"key"   => "app_status",
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
			"login"    => [
				[
					"key"   => "login_status",
					"title" => "Ativar / Desativar",
					"label" => "Ativar recurso",
					"value" => true,
				],
			],
		];

		register_setting( $this->page, $this->prefix . "options" );

		$this->add_allowed_options( $this->page, $resources );

		add_settings_section( $this->prefix . "app"      . $this->sufix, "App",      [ $this, "message_section" ], $this->page );
		add_settings_section( $this->prefix . "checkout" . $this->sufix, "Checkout", [ $this, "message_section" ], $this->page );
		add_settings_section( $this->prefix . "login"    . $this->sufix, "Login",    [ $this, "message_section" ], $this->page );

		foreach ( $resources as $res => $fields )
		{
			foreach ( $fields as $field )
			{
				$this->add_settings_field( $res, $field );
				$this->register_settings( $field );
			}
		}
	}

	/**
	 * Adds plugin fields to allowed options
	 *
	 * @since 	1.0.0
	 * @param 	string 	$page
	 * @param 	array 	$data
	 */
	public function add_allowed_options( string $page, array $data )
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
	 * @param 	string 	$section
	 * @param 	array 	$field
	 */
	public function add_settings_field( string $section, array $field )
	{
		$id      = $this->prefix . $field[ "key" ];
		$title   = $field[ "title" ];
		$section = $this->prefix . $section . $this->sufix;

		add_settings_field(
            $id, $title, [ $this, "render_settings_field" ], $this->page, $section,
			[
				"type"             => "input",
				"subtype"          => "checkbox",
				"label"            => $field[ "label" ],
				"value"            => $field[ "value" ],
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
	 * @param 	array 	$field
	 * @return 	void
	 */
	public function register_settings( array $field )
	{
		$name = $this->prefix . $field[ "key" ];

		register_setting( $this->page, $name );
	}

	/**
	 * Renders each field according to the configured args
	 *
	 * @since 	1.0.0
	 * @param 	array 	$args
	 * @return 	void
	 */
	public function render_settings_field( array $args )
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
	 * @param 	array 	$args
	 * @return 	void
	 */
	public function message_section( array $args )
	{
		if ( strpos( $args[ "id" ], "login"    ) !== false ) echo "<p>Login pela Triibo</p>";
		if ( strpos( $args[ "id" ], "checkout" ) !== false ) echo "<p>Login Triibo antes do Checkout</p>";
		if ( strpos( $args[ "id" ], "app"      ) !== false ) echo "<p>Login pelo App</p>";
	}

	/**
	 * Includes global css
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
	public function add_global_css()
	{
		$css_path = "assets/css/frontend/style.css";
		$css_ver  = date( "ymd-His", filemtime( plugin_dir_path( TRIIBO_AUTH_PLUGIN_FILE ) . $css_path ) );
		wp_enqueue_style ( "taa_css", plugins_url( $css_path, TRIIBO_AUTH_PLUGIN_FILE ), false, $css_ver, "all" );
	}
}