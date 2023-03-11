<?php
/**
 * The file that defines the plugin class Triibo-Auth-Login
 *
 * @author  Mateus Costa <mateus@costamateus.com.br>
 * @package Triibo_Auth/Classes
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class Triibo_Auth_Login
{
    /**
     * Class id
     *
     * @since   1.0.0
     * @var     string
     */
    protected $id = TRIIBO_AUTH_APP;

	/**
	 * API Gateway.
	 *
	 * @since 	1.0.0
	 * @var     Triibo_Api_Gateway
	 */
	protected $gate;

	/**
	 * WC Logger.
	 *
	 * @since 	1.0.0
	 * @var     wc_get_logger|WC_Logger
	 */
	protected $log;

    /**
     * Construct
     *
     * @since   1.0.0
     */
	public function __construct ()
    {
		$this->log  = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
		$this->gate = new Triibo_Api_Gateway();

        $this->include_scripts();

        add_filter( "gettext",                      [ $this, "change_text_form_login" ], 20, 1 );
        add_action( "woocommerce_login_form_start", [ $this, "add_text_form_login"    ]        );
        add_action( "woocommerce_login_form_end",   [ $this, "add_form_login"         ]        );

        add_action( "wp_footer",                    [ $this, "add_modals"             ]        );
		add_action( "wp_footer",                    [ $this, "add_scripts"            ]        );

        add_action( "wp_ajax_tal_auth_sms",                   [ $this, "auth_sms"            ] );
        add_action( "wp_ajax_nopriv_tal_auth_sms",            [ $this, "auth_sms"            ] );
        add_action( "wp_ajax_tal_validate_sms",               [ $this, "validate_sms"        ] );
        add_action( "wp_ajax_nopriv_tal_validate_sms",        [ $this, "validate_sms"        ] );

        add_action( "wp_ajax_tal_validate_code_email",        [ $this, "validate_code_email" ] );
        add_action( "wp_ajax_nopriv_tal_validate_code_email", [ $this, "validate_code_email" ] );
        add_action( "wp_ajax_tal_user_info",                  [ $this, "user_info"           ] );
        add_action( "wp_ajax_nopriv_tal_user_info",           [ $this, "user_info"           ] );
        add_action( "wp_ajax_tal_resend_email_code",          [ $this, "resend_email_code"   ] );
        add_action( "wp_ajax_nopriv_tal_resend_email_code",   [ $this, "resend_email_code"   ] );
        add_action( "wp_ajax_tal_find_user",                  [ $this, "find_user"           ] );
        add_action( "wp_ajax_nopriv_tal_find_user",           [ $this, "find_user"           ] );
        add_action( "wp_ajax_tal_add_triibo_id",              [ $this, "add_triibo_id"       ] );
        add_action( "wp_ajax_nopriv_tal_add_triibo_id",       [ $this, "add_triibo_id"       ] );
    }

    /**
     * Returns the Triibo_Auth_Login status
     * TRUE  for active.
     * FALSE for inactive.
     *
     * @since   1.0.0
     * @return 	boolean
     */
    public static function status()
	{
        $status = get_option( TRIIBO_AUTH_ID . "_login_status" );
		return $status == "on" ? true : false;
	}

	/**
	 * Includes js and css from Triibo-Auth-Checkout e Login
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
    private function include_scripts()
    {
		$mk_path = "assets/js/frontend/mask.min.js";
		$js_path = "assets/js/frontend/tal-script.js";

		$mk_ver  = date( "ymd-His", filemtime( plugin_dir_path( TRIIBO_AUTH_PLUGIN_FILE ) . $mk_path  ) );
		$js_ver  = date( "ymd-His", filemtime( plugin_dir_path( TRIIBO_AUTH_PLUGIN_FILE ) . $js_path  ) );

		wp_enqueue_script( "tal_mask",   plugins_url( $mk_path,  TRIIBO_AUTH_PLUGIN_FILE ), [ "jquery" ], $mk_ver,  false );
		wp_enqueue_script( "tal_script", plugins_url( $js_path,  TRIIBO_AUTH_PLUGIN_FILE ), [ "jquery" ], $js_ver,  true  );

		wp_localize_script( "tal_script", "tal_script_obj", [ "ajaxurl"  => admin_url( "admin-ajax.php" ), ] );
    }

    /**
     * Change the title of the login form card
     * From "Log In Your Account | Entrar em Sua Conta"
     * To "Choose how to access your account | Escolha como acessar sua conta"
     *
     * @since   1.0.0
     * @return  string
     */
    public function change_text_form_login( string $translated_text )
    {
        switch ( $translated_text )
        {
            case "Log In Your Account":
            case "Entrar em Sua Conta" :
                $translated_text = __( "Escolha como acessar sua conta", "woocommerce" );
            break;
        }

        return $translated_text;
    }

    /**
     * Add a title before the email and password fields in the login form
     *
     * @since   1.0.0
     * @return  void
     */
    public function add_text_form_login()
    {
        echo "<hr><p class='tal-title-optin' >Entrar com Login e Senha</p>";
    }

    /**
     * Adds to login form, cell field
     * Separates the login snippet by social network
     *
     * @since   1.0.0
     * @return  string
     */
    public function add_form_login()
    {
        require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/login.php"  );
    }

	/**
	 * Include required modals Triibo-Auth-Login
	 *
	 * @since 	1.0.0
	 * @return 	void
	 */
    public function add_modals()
    {
        require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/validate.php"  );

        // require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/login.php"           );
        // require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/tal/myacc-dashboard.php" );
    }

    /**
     * Required js
     *
     * @since   1.0.0
     * @return  void
     */
    public function add_scripts()
    {
		$js_path = "assets/js/frontend/tal-script.js";
		$js_ver  = date( "ymd-His", filemtime( plugin_dir_path( TRIIBO_AUTH_PLUGIN_FILE ) . $js_path  ) );
		wp_enqueue_script( "tal_script", plugins_url( $js_path, TRIIBO_AUTH_PLUGIN_FILE ), [ "jquery" ], $js_ver,  false );
		wp_localize_script( "tal_script", "tal_script_obj", [ "ajaxurl" => admin_url( "admin-ajax.php" ) ] );
    }

    /**
     * Envia códgio por sms
     *
     * @since   1.0.0
     * @return  void
     */
    public function auth_sms()
    {
        $response = $this->gate->auth_sms( $_POST["cellphone"] );

        if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
            $this->log( json_encode( $response ) );

        echo json_encode( $response[ "data" ] );
        die();
    }

    /**
     * Valida código enviado por sms
     *
     * @since   1.0.0
     * @return  void
     */
    public function validate_sms()
    {
        $code      = $_POST[ "code"          ];
        $cellphone = $_POST[ "cellphone"     ];
        $trans_id  = $_POST[ "transactionId" ];

        $response  = $this->gate->validate_sms( $code, $cellphone, $trans_id );

        if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
            $this->log( json_encode( $response ) );

        echo json_encode( $response[ "data" ] );
        die();
    }

    /**
     * Valida código enviado por email
     *
     * @since   1.0.0
     * @return  void
     */
    public function validate_code_email()
    {

    }

    /**
     * Recupera dados do usuário na Triibo
     *
     * @since   1.0.0
     * @return  void
     */
    public function user_info()
    {

    }

    /**
     * Reenvia código por email
     *
     * @since   1.0.0
     * @return  void
     */
    public function resend_email_code()
    {

    }

    /**
     * Cadastra usuário na Triibo
     *
     * @since   1.0.0
     * @return  void
     */
    public function find_user()
    {

    }

    /**
     * Cadastra usuário na Triibo
     *
     * @since   1.0.0
     * @return  void
     */
    public function add_triibo_id()
    {

    }

    /**
     * Register log
     *
     * @since   1.0.0
     * @param   string $message
     * @return  void
     */
    private function log( string $message )
    {
        $this->log( $this->id, $message );
    }
}
