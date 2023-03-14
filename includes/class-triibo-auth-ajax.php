<?php
/**
 * The file that defines the plugin class Triibo-Auth-Ajax
 *
 * @author  Mateus Costa <mateus@costamateus.com.br>
 * @package Triibo_Auth/Classes
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

class Triibo_Auth_Ajax
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
     * Send code by sms
     *
     * @since   1.0.0
     * @return  void
     */
    public function auth_sms()
    {
        $cellphone = $_POST[ "cellphone" ];
        $response  = $this->gate->auth_sms( $cellphone );

        if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
            $this->log( json_encode( $response ) );

        echo json_encode( $response[ "data" ] );
        die();
    }

    /**
     * Validates code sent by sms
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

        $response[ "data" ][ "logged_in" ] = is_user_logged_in();

        echo json_encode( $response[ "data" ] );
        die();
    }

    /**
     * Validates code sent by email
     *
     * @since   1.0.0
     * @return  void
     */
    public function validate_code_email()
    {
        $code      = $_POST[ "codeEmail" ];
        $cellphone = $_POST[ "cellphone" ];
        $email     = $_POST[ "email"     ];
        $passw     = $_POST[ "password"  ];

        $sent_code = get_transient( "_ta_global_code" );
        delete_transient( "_ta_global_code" );

        if ( $sent_code )
        {
            if ( $sent_code == $code )
            {
                $response = $this->create_or_update_user_triibo( $cellphone, $email );

                if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
                    $this->log( json_encode( $response ) );

                $uid      = isset( $response[ "uid" ] ) ? $response[ "uid" ] : null;

                $result   = $this->create_user_marketplace( $uid, $cellphone, $email, $passw );
            }
            else
            {
                $result = [
                    "success" => false,
                    "error"   => [
                        "code"    => 400.1,
                        "message" => "Código inválido"
                    ],
                ];
            }
        }
        else
        {
            $result = [
                "success" => false,
                "error"   => [
                    "code"    => 400.2,
                    "message" => "Código expirado"
                ],
            ];
        }

        echo json_encode( $result );
        die();
    }

    /**
     * Resend code by email
     *
     * @since   1.0.0
     * @return  void
     */
    public function resend_email_code()
    {
        $email    = $_POST[ "email" ];

        $response = Triibo_Auth::send_code_by_email( $email );

        if ( !$response[ "success" ] ) $this->log( json_encode( $response ) );

        echo json_encode( $response );
        die();
    }

    /**
     * Get user info triibo
     *
     * @since   1.0.0
     * @return  void
     */
    public function user_info()
    {
        $checkout  = $_POST[ "ckt"       ];
        $cellphone = $_POST[ "cellphone" ];

        $response  = $this->gate->user_info( $cellphone );

        if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
            $this->log( json_encode( $response ) );

        $data      = $response[ "data" ];

        if ( empty( $data[ "error" ] ) && empty( $data[ "success" ][ "userInfo" ] ) )
        {
            $alert  = reset( $data[ "alert" ] );
            $result = [
                "success" => false,
                "error"   => [
                    "message" => $alert[ "errorMsg" ],
                    "code"    => 404,
                ],
            ];

            $this->log( json_encode( $result ) );
        }
        elseif ( !empty( $data[ "error" ] ) )
        {
            $result = [
                "success" => false,
                "error"   => [
                    "message" => "Server error",
                    "code"    => 500,
                ],
            ];

            $this->log( json_encode( $result ) );
        }
        else
        {
            $uid     = $data[ "success" ][ "userInfo" ][ "id"          ];
            $contact = $data[ "success" ][ "userInfo" ][ "contactList" ];

            $email   = $this->get_verified_email( $contact );

            if ( $email )
            {
                $user_id = email_exists( $email );

                if ( $user_id )
                {
                    // add triibo_phone and triibo_uid when $checkout true
                    if ( $checkout )
                    {
                        $triibo_phone = get_user_meta( $user_id, "triiboId_phone", true );
                        $triibo_uid   = get_user_meta( $user_id, "_triibo_id",     true );

                        // User without triiboId_phone
                        if ( empty( $triibo_phone ) )
                        {
                            $cellphone = "+" . preg_replace( "/\D/", "", $cellphone );
                            update_user_meta( $user_id, "triiboId_phone", $cellphone );
                        }

                        // User without _triibo_id
                        if ( empty( $triibo_uid ) )
                            update_user_meta( $user_id, "_triibo_id", $uid );
                    }

                    $result = $this->login_by_triibo( "id", $user_id );
                }
                else
                {
                    $result = [
                        "success" => false,
                        "error"   => [
                            "message" => "E-mail não encontrado",
                            "code"    => 401,
                        ],
                    ];

                    $this->log( json_encode( $result ) );
                }
            }
            else
            {
                $result = [
                    "success" => false,
                    "error"   => [
                        "message" => "E-mail não verificado",
                        "code"    => 401,
                    ],
                ];

                $this->log( json_encode( $result ) );
            }
        }

        echo json_encode( $result );
        die();
    }

    /**
     * Create user triibo
     *
     * @since   1.0.0
     * @return  void
     */
    public function find_user()
    {
        $cellphone = $_POST[ "cellphone" ];
        $email     = $_POST[ "email"     ];
        $passw     = $_POST[ "password"  ];

        $user      = get_user_by( "email", $email );

        // enviar email com código
        if ( !$user )
        {
            $response = Triibo_Auth::send_code_by_email( $email );

            $message  = ( $response[ "success" ] ) ? "Usuário inexistente" : "Usuário inexistente. Código não enviado!";
            $code     = ( $response[ "success" ] ) ? 301 : 501;

            $result   = [
                "success" => false,
                "error"   => [
                    "message" => $message,
                    "code"    => $code,
                ],
            ];
        }
        else
        {
            if ( $user && wp_check_password( $passw, $user->data->user_pass, $user->ID ) )
            {

                $response  = $this->create_or_update_user_triibo( $cellphone, $email, $user->first_name, $user->nickname );

                if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
                    $this->log( json_encode( $response ) );

                $cellphone = "+" . preg_replace( "/\D/", "", $cellphone );

                update_user_meta( $user->ID, "triiboId_phone", $cellphone );

                if ( isset( $response[ "uid" ] ) ) update_user_meta( $user->ID, "_triibo_id", $response[ "uid" ] );

                $result    = $this->login_by_triibo( "id", $user->ID );
            }
            else
            {
                $result = [
                    "success" => false,
                    "error"   => [
                        "message" => "E-mail e/ou senha incorreta",
                        "code"    => 401,
                    ],
                ];
            }
        }

        echo json_encode( $result );
        die();
    }

    /**
     * Cadastra usuário na Triibo
     *
     * TODO
     * @since   1.0.0
     * @return  void
     */
    public function add_triibo_id()
    {

    }

    /**
     * Check valid email
     *
     * @since   1.0.0
     * @param   array $data
     * @return  string|null
     */
    private function get_verified_email( array $data )
    {
        foreach ( $data as $c )
            if ( $c[ "type" ] == "emailMarketplace" && $c[ "verified" ] )
                return $c[ "value" ];

        return null;
    }

    /**
     * Create or update user triibo
     *
     * @since   1.0.0
     * @param   string $cellphone
     * @param   string $email
     * @param   string $name
     * @param   string $nick
     * @return  string
     */
    private function create_or_update_user_triibo( string $cellphone, string $email, string $name = "", string $nick = "" )
    {
        $name     = $name ?? reset( explode( "@", $email ) );
        $nick     = $nick ?? $name;

        return $this->gate->update_user_mkt( $cellphone, $email, "UPDATE", $name, $nick );
    }

    /**
     * Create marketplace user
     *
     * @since   1.0.0
     * @param   string|null $uid
     * @param   string      $cellphone
     * @param   string      $email
     * @param   string      $passw
     * @return  void
     */
    private function create_user_marketplace( string $uid, string $cellphone, string $email, string $passw )
    {
        $cellphone = "+" . preg_replace( "/\D/", "", $cellphone );
        $name      = reset( explode( "@", $email ) );

        $user_id   = wp_insert_user( [
            "user_login"    => $uid ?? $email,
            "user_pass"     => wp_hash_password( "Triibo@" . date( "Y" ) . "!" ),
            "user_nicename" => $name,
            "user_email"    => $email,
            "display_name"  => $name,
            "nickname"      => $name,
            "first_name"    => $name,
            "last_name"     => "",
        ] );

        if ( !is_wp_error( $user_id ) )
        {
            wp_set_password( $passw, $user_id );

            add_user_meta( $user_id, "triiboId_phone", $cellphone );
            if ( $uid ) add_user_meta( $user_id, "_triibo_id", $uid );

            $user = get_user_by( "id", $user_id );

            $user->set_role( "subscriber" );
            if ( class_exists( "WooCommerce" ) ) $user->set_role( "customer" );
        }

        return $this->login_by_triibo( "id", $user_id );
    }

    /**
     * Log in to Mkt, according to $field passed
     *
     * @since   1.0.0
     * @param   string      $field
     * @param   int|string  $value
     * @return  array
     */
    private function login_by_triibo( string $field, int|string $value )
    {
        $user = get_user_by( $field, $value );

        if ( !is_wp_error( $user ) )
        {
            wp_clear_auth_cookie();
            wp_set_current_user( $user->ID );
            wp_set_auth_cookie( $user->ID, true );

            $result = [
                "success"  => true,
                "redirect" => home_url( "/my-account" ),
            ];
        }
        else
        {
            $result = [
                "success" => false,
                "error"   => [
                    "message" => "Error login",
                    "code"    => 500,
                ],
            ];
        }

        return $result;
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
        $this->log->add( $this->id, $message );
    }
}