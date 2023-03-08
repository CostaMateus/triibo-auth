<?php
/**
 * The file that defines the plugin class Triibo-Auth-Checkout
 *
 * @author  Mateus Costa <mateus@costamateus.com.br>
 * @package Triibo_Auth/Classes
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class Triibo_Auth_Checkout
{
    /**
     * Redirect URL
     *
     * @since   1.0.0
     * @var     string
     */
    protected static $_lbc_url      = null;

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
        if ( $this->status() )
        {
            self::$_lbc_url = md5( "redirect_to_checkout" );

            // valida se o redirect é checkout,
            // valida se o usuário está logado e não tem triibo_id,
            // valida se o usuário não está logado.
            add_action( "template_redirect",                  [ $this, "redirect_to_account_page" ], 100 );

            // redireciona para tela de chackout
            add_filter( "woocommerce_registration_redirect",  [ $this, "redirect_to_checkout"     ], 100 );
            add_filter( "woocommerce_login_redirect",         [ $this, "redirect_to_checkout"     ], 100 );

            // add modal na página minha conta - tela login
            add_action( "wc_login_before_checkout_add_modal", [ $this, "modal_form_login"         ]      );

            // add modal na página minha conta - tela dashboard
            add_action( "woocommerce_account_content",        [ $this, "modal_myacc_dashboard"    ]      );
        }
    }

    /**
     * Returns the Triibo_Auth_Checkout status
     * TRUE  for active.
     * FALSE for inactive.
     *
     * @since   1.0.0
     * @return 	boolean
     */
    private function status()
	{
        $status = get_option( TRIIBO_AUTH_ID . "_checkout_status" );
		return $status == "on" ? true : false;
	}

    /**
     * Get disclaimer of logged user or not
     *
     * @since   1.0.0
     * @param   boolean $user_logged
     * @return  string
     */
    public static function get_disclaimer( bool $user_logged = false )
    {
        return ( !$user_logged ) ? self::$_disclaimer_1 : self::$_disclaimer_2;
    }

    /**
     * Checkout URL
     *
     * @return string
     */
    public static function get_checkout_url()
    {
        global $woocommerce;
        return $woocommerce->cart->get_checkout_url();
    }

    /**
     * Check if URL has lbc param
     *
     * @since   1.0.0
     * @return  boolean
     */
    public static function has_query_param()
    {
		return isset( $_GET[ "lbc" ] ) && $_GET[ "lbc" ] === self::$_lbc_url;
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
     * Redirect to account page
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
                // user logado
                $user_id   = get_current_user_id();
                $triibo_id = get_user_meta( $user_id, "triiboId_phone", true );

                if ( empty( $triibo_id ) )
                {
                    // user sem triibo_id
                    $this->safe_redirect();
                }
            }
            else
            {
                // user nao logado
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
	public function redirect_to_checkout( $redirect )
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
        wp_safe_redirect( add_query_arg( "lbc", self::$_lbc_url, $this->get_login_page_url() ) );
        die();
    }

    /**
     * Require form login modal
     *
     * @since   1.0.0
     * @return  void
     */
    public function modal_form_login()
    {
        require_once( plugin_dir_path( TRIIBO_AUTH_PLUGIN_FILE ) . "includes/modals/tal/form-login.php" );
    }

    /**
     * Require my account dashboard modal
     *
     * @since   1.0.0
     * @return  void
     */
    public function modal_myacc_dashboard()
    {
        require_once( plugin_dir_path( TRIIBO_AUTH_PLUGIN_FILE ) . "includes/modals/tal/myacc-dashboard.php" );
    }
}
