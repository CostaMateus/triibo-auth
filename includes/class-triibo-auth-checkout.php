<?php
/**
 * The file that defines the plugin class Triibo-Auth-Checkout
 *
 * @author  Mateus Costa <mateus@costamateus.com.br>
 * @package Triibo_Auth/Classes
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class Triibo_Auth_Checkout extends Triibo_Auth_Ajax
{
    /**
     * Redirect URL
     *
     * @since   1.0.0
     * @var     string
     */
    protected static $_ckt_url      = null;

    /**
     * Option 1 of disclaimer
     *
     * @since   1.0.0
     * @var     string
     */
    protected static $_disclaimer_1 = "Antes de avançar para o Checkout, informe seu celular para acessar sua conta.<br><br>Caso não tenha uma conta, você será guiado pelo processo de criação.";

    /**
     * Option 2 of disclaimer
     *
     * @since   1.0.0
     * @var     string
     */
    protected static $_disclaimer_2 = "Antes de avançar para o Checkout, informe seu celular para receber o código SMS e validar sua conta.";

    /**
     * Construct
     *
     * @since   1.0.0
     */
	public function __construct ()
    {
		$this->log      = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
		$this->gate     = new Triibo_Api_Gateway();

        self::$_ckt_url = md5( "redirect_to_checkout" );

        add_action( "template_redirect",                      [ $this, "redirect_to_account_page" ], 100 );
        add_filter( "woocommerce_registration_redirect",      [ $this, "redirect_to_checkout"     ], 100 );
        add_filter( "woocommerce_login_redirect",             [ $this, "redirect_to_checkout"     ], 100 );

        add_action( "wp_ajax_tal_auth_sms",                   [ $this, "auth_sms"                 ]      );
        add_action( "wp_ajax_nopriv_tal_auth_sms",            [ $this, "auth_sms"                 ]      );
        add_action( "wp_ajax_tal_validate_sms",               [ $this, "validate_sms"             ]      );
        add_action( "wp_ajax_nopriv_tal_validate_sms",        [ $this, "validate_sms"             ]      );
        add_action( "wp_ajax_tal_validate_code_email",        [ $this, "validate_code_email"      ]      );
        add_action( "wp_ajax_nopriv_tal_validate_code_email", [ $this, "validate_code_email"      ]      );
        add_action( "wp_ajax_tal_user_info",                  [ $this, "user_info"                ]      );
        add_action( "wp_ajax_nopriv_tal_user_info",           [ $this, "user_info"                ]      );
        add_action( "wp_ajax_tal_resend_email_code",          [ $this, "resend_email_code"        ]      );
        add_action( "wp_ajax_nopriv_tal_resend_email_code",   [ $this, "resend_email_code"        ]      );
        add_action( "wp_ajax_tal_find_user",                  [ $this, "find_user"                ]      );
        add_action( "wp_ajax_nopriv_tal_find_user",           [ $this, "find_user"                ]      );
        add_action( "wp_ajax_tal_add_triibo_id",              [ $this, "add_triibo_id"            ]      );
        add_action( "wp_ajax_nopriv_tal_add_triibo_id",       [ $this, "add_triibo_id"            ]      );
    }

    /**
     * Returns the Triibo_Auth_Checkout status
     * TRUE  for active.
     * FALSE for inactive.
     *
     * @since   1.0.0
     * @return 	boolean
     */
    public static function status()
	{
        $status = get_option( TRIIBO_AUTH_ID . "_checkout_status" );
		return $status == "on" ? true : false;
	}

    /**
     * Get disclaimer of logged user or not
     *
     * @since   1.0.0
     * @return  string
     */
    public static function get_disclaimer()
    {
        return ( !is_user_logged_in() ) ? self::$_disclaimer_1 : self::$_disclaimer_2;
    }

    /**
     * Checkout URL
     *
     * @return string
     */
    public static function get_checkout_url()
    {
        global $woocommerce;
        return $woocommerce->cart ? $woocommerce->cart->get_checkout_url() : "";
    }

    /**
     * Check if URL has ckt param
     *
     * @since   1.0.0
     * @return  boolean
     */
    public static function has_query_param()
    {
		return isset( $_GET[ "ckt" ] ) && $_GET[ "ckt" ] === self::$_ckt_url;
	}

    /**
     * Login page URL
     *
     * @since   1.0.0
     * @return  string
     */
    private function get_login_page_url()
    {
		return apply_filters( "wc_login_before_checkout_login_page_url",
			get_permalink( get_option( "woocommerce_myaccount_page_id" ) )
		);
	}

    /**
     * Redirect to account page if:
     * - redirect is to checkout
     * - user is logged in and dont have triibo_id
     * - user isnt logged in
     *
     * @since   1.0.0
     * @return  void
     */
	public function redirect_to_account_page()
    {
        if ( is_checkout() )
        {
            if ( is_user_logged_in() )
            {
                // user logged in
                $user_id      = get_current_user_id();
                $triibo_phone = get_user_meta( $user_id, "_triibo_phone", true );
                $triibo_phone = $triibo_phone ?: get_user_meta( $user_id, "triiboId_phone", true );

                if ( empty( $triibo_phone ) )
                {
                    // user without _triibo_phone
                    $this->safe_redirect(); 
                }
            }
            else
            {
                // user not logged in
                $this->safe_redirect();
            }
        }
	}

    /**
     * Redirect to checkout page
     *
     * @since   1.0.0
     * @param   string $redirect
     * @return  string
     */
	public function redirect_to_checkout( string $redirect )
    {
		return ( self::has_query_param() ) ? wc_get_checkout_url() : $redirect;
	}

    /**
     * Make redirection
     *
     * @since   1.0.0
     * @return  void
     */
    private function safe_redirect()
    {
        wp_safe_redirect( add_query_arg( "ckt", self::$_ckt_url, $this->get_login_page_url() ) );
        die();
    }
}
