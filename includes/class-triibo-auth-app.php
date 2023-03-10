<?php
/**
 * The file that defines the plugin class Triibo-Auth-App
 *
 * @author  Mateus Costa <mateus@costamateus.com.br>
 * @package Triibo_Auth/Classes
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class Triibo_Auth_App
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
	 * @since 	1.0.0
     */
    public function __construct()
    {
		$this->log  = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();

		$this->gate = new Triibo_Api_Gateway();

		add_action( "wp_footer",                           [ $this, "add_modals"       ] );
		add_action( "wp_footer",                           [ $this, "add_script"       ] );

        add_action( "wp_ajax_taa_validate_code",           [ $this, "validate_code"    ] );
        add_action( "wp_ajax_nopriv_taa_validate_code",    [ $this, "validate_code"    ] );

        add_action( "wp_ajax_taa_new_account",             [ $this, "new_account"      ] );
        add_action( "wp_ajax_nopriv_taa_new_account",      [ $this, "new_account"      ] );

        add_action( "wp_ajax_taa_validate_account",        [ $this, "validate_account" ] );
        add_action( "wp_ajax_nopriv_taa_validate_account", [ $this, "validate_account" ] );
    }

    /**
     * Required modals
     *
     * @since   1.0.0
     * @return  void
     */
    public function add_modals()
    {
		require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/taa/new-account.php"      );
		require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/taa/new-password.php"     );
		require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/taa/validate-account.php" );
		require_once( dirname( TRIIBO_AUTH_PLUGIN_FILE ) . "/includes/modals/taa/validate-code.php"    );
    }

    /**
     * Required js
     *
     * @since   1.0.0
     * @return  void
     */
    public function add_script()
    {
		$js_path = "assets/js/frontend/taa-script.js";
		$js_ver  = date( "ymd-His", filemtime( plugin_dir_path( TRIIBO_AUTH_PLUGIN_FILE ) . $js_path  ) );
		wp_enqueue_script( "taa_script", plugins_url( $js_path, TRIIBO_AUTH_PLUGIN_FILE ), [ "jquery" ], $js_ver,  false );
		wp_localize_script( "taa_script", "taa_script_obj", [ "ajaxurl" => admin_url( "admin-ajax.php" ) ] );
    }

    /**
     * Validate code sent by sms/email
     *
     * @since   1.0.0
     * @return  void
     */
    public function validate_code()
    {
        $result    = [
            "code"    => 5000,
            "error"   => true,
            "message" => "Error"
        ];

        $code      = $_POST[ "code"  ];
        $email     = $_POST[ "email" ];

        $sent_code = get_transient( "_taa_triibo_code" );
        delete_transient( "_taa_triibo_code" );

        if ( $sent_code )
        {
            // passo 12
            if ( $sent_code == $code )
            {
				$user_id = email_exists( $email );

                if ( $user_id )
                {
                    $cellphone = get_transient( "_taa_triibo_cellphone" );
                    delete_transient( "_taa_triibo_cellphone" );

                    // passo 13
					update_user_meta( $user_id, "triiboId_phone", "+$cellphone" );

					// passo 14 ( passo_comum )
                    $response = $this->gate->update_user_mkt( $cellphone, $email, "UPDATE" );
					if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
						$this->log( json_encode( $response ) );

                    self::make_login( $user_id );

                    $result = [
                        "code"    => 2000,
                        "error"   => null,
                        "message" => "Usuário conectado"
                    ];
                }
                else
                {
                    $result = [
                        "code"    => 2001,
                        "error"   => null,
                        "message" => "Criar conta"
                    ];
                }
            }
            else
            {
                $result = [
                    "code"    => 4001,
                    "error"   => true,
                    "message" => "Código inválido"
                ];
            }
        }
        else
        {
            $result = [
                "code"    => 4002,
                "error"   => true,
                "message" => "Código expirado"
            ];
        }

        echo json_encode( $result );
        die();
    }

    /**
     * Validate account, if it exists connect, if not create
     *
     * @since   1.0.0
     * @return  void
     */
    public function new_account()
    {
        $result    = [
            "code"    => 5000,
            "error"   => true,
            "message" => "Error"
        ];

        $email     = $_POST[ "email" ];
        $pass      = $_POST[ "pass"  ];

        $cellphone = get_transient( "_taa_triibo_cellphone" );
        delete_transient( "_taa_triibo_cellphone" );

        $user      = get_user_by( "email", $email );

        // valida se conta existe e se a senha está correta
        if ( $user )
        {
            if ( wp_check_password( $pass, $user->data->user_pass, $user->ID ) )
            {
                add_user_meta( $user->ID, "triiboId_phone", "+$cellphone" );

                $response = $this->gate->update_user_mkt( $cellphone, $email, "CREATE" );
                if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
                    $this->log( json_encode( $response ) );

                self::make_login( $user->ID );

                $result = [
                    "code"    => 2000,
                    "error"   => null,
                    "message" => "Usuário conectado"
                ];
            }
            else
            {
                $result = [
                    "code"    => 4001,
                    "error"   => true,
                    "message" => "E-mail e/ou senha incorreta"
                ];
            }
        }
        else
        {
            $name    = explode( "@", $email )[ 0 ];

            // passo 18
            $user_id = wp_insert_user( [
                "user_login"    => $email,
                "user_pass"     => wp_hash_password( "Triibo" . date( "Y" ) . "@!#" ),
                "user_nicename" => $name,
                "user_email"    => $email,
                "display_name"  => $name,
                "nickname"      => $name,
                "first_name"    => $name,
                "last_name"     => "",
            ] );

            if ( ! is_wp_error( $user_id ) )
            {
                wp_set_password( $pass, $user_id );

                // passo 19
                add_user_meta( $user_id, "triiboId_phone", "+$cellphone" );

                $user = get_user_by( "id", $user_id );

                $user->set_role( "subscriber" );
                if ( class_exists( "WooCommerce" ) ) $user->set_role( "customer" );

                // passo 20 ( passo_comum )
                $response = $this->gate->update_user_mkt( $cellphone, $email, "UPDATE" );
                if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
                    $this->log( json_encode( $response ) );

                self::make_login( $user_id );

                $result = [
                    "code"    => 2000,
                    "error"   => null,
                    "message" => "Conta criada"
                ];
            }
            else
            {
                $result = [
                    "code"    => 5000,
                    "error"   => true,
                    "message" => "Falha na criação da conta"
                ];
            }
        }

        echo json_encode( $result );
        die();
    }

    /**
     * validate account, if it exists connect
     *
     * @since   1.0.0
     * @return  void
     */
    public function validate_account()
    {
        $result    = [
            "code"    => 5000,
            "error"   => true,
            "message" => "Error"
        ];

        $email     = $_POST[ "email" ];
        $pass      = $_POST[ "pass"  ];

        $cellphone = get_transient( "_taa_triibo_cellphone" );
        delete_transient( "_taa_triibo_cellphone" );

        $user      = get_user_by( "email", $email );

        if ( ! $user )
        {
            $result = [
                "code"    => 4004,
                "error"   => true,
                "message" => "Usuário não encontrado",
            ];
        }
        else
        {
            if ( $user && wp_check_password( $pass, $user->data->user_pass, $user->ID ) )
            {
                // passo 28 ( passo_comum )
                $response = $this->gate->update_user_mkt( $cellphone, $email, "CREATE" );
                if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
                    $this->log( json_encode( $response ) );

                self::make_login( $user->ID );

                $result = [
                    "code"    => 2000,
                    "error"   => null,
                    "message" => "Usuário conectado"
                ];
            }
            else
            {
                $result = [
                    "code"    => 4001,
                    "error"   => true,
                    "message" => "E-mail e/ou senha incorreta"
                ];
            }
        }

        echo json_encode( $result );
        die();
    }

    /**
     * Log in to the account
     *
     * @since   1.0.0
     * @param   integer $user_id
     * @return  void
     */
	private static function make_login( int $user_id )
	{
		clean_user_cache( $user_id );
		wp_clear_auth_cookie();
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
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
