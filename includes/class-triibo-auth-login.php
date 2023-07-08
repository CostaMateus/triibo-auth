<?php
/**
 * The file that defines the plugin class Triibo-Auth-Login
 *
 * @author  Mateus Costa <mateus@costamateus.com.br>
 * @package Triibo_Auth/Classes
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class Triibo_Auth_Login extends Triibo_Auth_Ajax
{
    /**
     * Construct
     *
     * @since   1.0.0
     */
	public function __construct ()
    {
		$this->log  = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
		$this->gate = new Triibo_Api_Gateway();

        add_filter( "gettext",                                [ $this, "change_text_form_login" ], 20, 1 );
        add_action( "woocommerce_login_form_start",           [ $this, "add_text_form_login"    ]        );
        add_action( "woocommerce_login_form_end",             [ $this, "add_form_login"         ]        );

        add_action( "wp_ajax_tal_auth_sms",                   [ $this, "auth_sms"               ]        );
        add_action( "wp_ajax_nopriv_tal_auth_sms",            [ $this, "auth_sms"               ]        );
        add_action( "wp_ajax_tal_validate_sms",               [ $this, "validate_sms"           ]        );
        add_action( "wp_ajax_nopriv_tal_validate_sms",        [ $this, "validate_sms"           ]        );
        // add_action( "wp_ajax_tal_validate_code_email",        [ $this, "validate_code_email"    ]        );
        // add_action( "wp_ajax_nopriv_tal_validate_code_email", [ $this, "validate_code_email"    ]        );
        add_action( "wp_ajax_tal_user_info",                  [ $this, "user_info"              ]        );
        add_action( "wp_ajax_nopriv_tal_user_info",           [ $this, "user_info"              ]        );
        // add_action( "wp_ajax_tal_resend_email_code",          [ $this, "resend_email_code"      ]        );
        // add_action( "wp_ajax_nopriv_tal_resend_email_code",   [ $this, "resend_email_code"      ]        );
        add_action( "wp_ajax_tal_find_user",                  [ $this, "find_user"              ]        );
        add_action( "wp_ajax_nopriv_tal_find_user",           [ $this, "find_user"              ]        );
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
}
