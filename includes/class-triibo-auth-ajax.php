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
	 * API Node.
	 *
	 * @since 	2.0.0
	 * @var     Triibo_Api_Node
	 */
	protected $node;

    /**
     * Send types
     *
	 * @since 	2.0.0
     * @var     array
     */
    protected $send_types = [ "sms", "wpp" ];

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
     * @since   2.1.0 New Node API securityCode
     * @since   2.0.0 New Node API for send code by sms/wpp
     * @since   1.0.1 Log improvement
     * @since   1.0.0
     * @return  void
     */
    public function auth_sms()
    {
        /**
         * @since 2.1.0 Added api security code
         */
        $response = $this->node->get_security_code();

        if ( $response[ "success" ] )
        {
            $code     = $response[ "data" ][ "securityCode" ];

            $response = $this->node->auth( "", $code );

            if ( $response[ "success" ] )
            {
                $_token    = $response[ "data"  ][ "token" ];

                $type      = ( !in_array( $_POST[ "type" ], $this->send_types ) ) ? "sms" : $_POST[ "type" ];
                $type      = get_option( TRIIBO_AUTH_ID . "_wpp_status" ) ? $type : "sms";

                $cellphone = $_POST[ "cellphone" ];

                $response  = $this->node->auth_code( $_token, [
                    "cellphone" => $cellphone,
                    "type"      => $type
                ] );

                if ( $response[ "success" ] )
                {
                    $this->log( "INFO - auth_sms : " . json_encode( $response ) );

                    $return = [
                        "error"   => false,
                        "success" => [
                            "transactionId" => $response[ "data" ][ "transactionId" ],
                        ]
                    ];

                    echo json_encode( $return );
                    die();
                }
                else
                {
                    $this->log( "ERROR - auth_code : " . json_encode( $response ) );

                    $err   = $response[ "data" ][ "error" ];
                    $code  = ( $err == "Usuário excedeu o limite de requisições" ) ? 429 : 400;
                    $error = [
                        "success" => false,
                        "error"   => [
                            "errorCode" => $code,
                        ]
                    ];
                }
            }
            else
            {
                $this->log( "ERROR - auth : " . json_encode( $response ) );

                $error = [
                    "success" => false,
                    "error"   => [
                        "errorCode" => 500,
                    ]
                ];
            }
        }
        else
        {
            $this->log( "ERROR - auth_security : " . json_encode( $response ) );

            $error = [
                "success" => false,
                "error"   => [
                    "errorCode" => 401,
                ]
            ];
        }

        echo json_encode( $error );
        die();
    }

    /**
     * Validates code sent by sms
     *
     * @since   2.2.0 Change validation to Node API
     * @since   1.0.1 Log improvement
     * @since   1.0.0
     * @return  void
     */
    public function validate_sms()
    {
        $code      = $_POST[ "code"          ];
        $cellphone = $_POST[ "cellphone"     ];
        $trans_id  = $_POST[ "transactionId" ];

        $response  = $this->node->get_security_code();

        if ( $response[ "success" ] )
        {
            $s_code   = $response[ "data" ][ "securityCode" ];

            $response = $this->node->auth( "", $s_code );

            if ( $response[ "success" ] )
            {
                $_token   = $response[ "data"  ][ "token" ];

                $data     = [
                    "code"      => $code,
                    "trans_id"  => $trans_id,
                    "cellphone" => $cellphone,
                ];

                $response = $this->node->validate_sms( $_token, $data );

                $this->log( "ERROR - validate_sms 1 : " . json_encode( $response ) );

                echo json_encode( $response );
                exit();
            }
            else
            {

                $this->log( "ERROR - validate_sms 3 : " . json_encode( $response ) );

                echo json_encode( $response );
                exit();
            }
        }
        else
        {
            $this->log( "ERROR - validate_sms 2 : " . json_encode( $response ) );

            $error = [
                "success" => false,
                "error"   => [
                    "errorCode" => 401,
                ],
                "data"    => [
                    "logged_in" => is_user_logged_in(),
                ]
            ];

            echo json_encode( $error );
            exit();
        }




        // // ====================================================================
        // $response  = $this->gate->validate_sms( $code, $cellphone, $trans_id );
        //
        // if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
        // {
        //     $response[ "data_sent" ] = [
        //         "function"      => "validate_sms",
        //         "code"          => $code,
        //         "cellphone"     => $cellphone,
        //         "transactionId" => $trans_id,
        //     ];
        //
        //     $this->log( json_encode( $response ) );
        // }
        //
        // $response[ "data" ][ "logged_in" ] = is_user_logged_in();
        //
        // echo json_encode( $response[ "data" ] );
        // die();
    }

    /**
     * Get user info triibo
     *
     * @since   1.1.0 New flow
     * @since   1.0.1 Log improvement
     * @since   1.0.0
     * @return  void
     */
    public function user_info()
    {
        $error     = true;
        $cellphone = $_POST[ "cellphone" ];

        $response  = $this->gate->user_info( $cellphone );

        if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
        {
            $response[ "data_sent" ] = [
                "function" => "user_info_1",
                "cellphone" => $cellphone
            ];

            $this->log( json_encode( $response ) );
        }

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
        }
        else
        {
            $uid       = $response[ "data" ][ "success" ][ "userInfo" ][ "uId" ];
            $cellphone = "+" . preg_replace( "/\D/", "", $cellphone );

            $user      = get_users( [
                "meta_key"   => "_triibo_id",
                "meta_value" => $uid
            ] );

            // achou usuario com _triibo_id == uid
            if ( !empty( $user ) )
            {
                $user     = reset( $user );
                $email    = $user->user_email;

                delete_user_meta( $user->ID, "triiboId_phone", $cellphone );
                update_user_meta( $user->ID, "_triibo_phone",  $cellphone );

                $result   = $this->login_by_triibo( "id", $user->ID );
            }
            else
            {
                $user = get_users( [
                    "meta_key"   => "triiboId_phone",
                    "meta_value" => $cellphone
                ] );

                // achou usuario com triiboId_phone
                if ( !empty( $user ) )
                {
                    $user     = reset( $user );
                    $email    = $user->user_email;

                    $response = $this->create_or_update_user_triibo( $cellphone, $email, $user->first_name, $user->nickname );

                    if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
                    {
                        $response[ "data_sent" ] = [
                            "function"  => "user_info_3",
                            "cellphone" => $cellphone,
                            "email"     => $email,
                        ];

                        $this->log( json_encode( $response ) );
                    }

                    $uid      = ( $uid == $response[ "data" ][ "success" ][ "newUserInfo" ][ "uId" ] ) ? $uid : $response[ "data" ][ "success" ][ "userInfo" ][ "uId" ];

                    delete_user_meta( $user->ID, "triiboId_phone", $cellphone );
                    update_user_meta( $user->ID, "_triibo_phone",  $cellphone );
                    update_user_meta( $user->ID, "_triibo_id",     $uid       );

                    $result   = $this->login_by_triibo( "id", $user->ID );
                }
                else
                {
                    $result   = [
                        "success" => false,
                        "error"   => [
                            "message" => "Celular não encontrado",
                            "code"    => 401,
                        ],
                    ];
                }
            }
        }

        if ( $error )
        {
            $log                = $result;
            $log[ "data_sent" ] = [
                "function"  => "user_info_2",
                "cellphone" => $cellphone
            ];

            $this->log( json_encode( $log ) );
        }

        echo json_encode( $result );
        die();
    }

    /**
     * Create user triibo
     *
     * @since   1.1.0 New flow. Non-existent user no longer validates email
     * @since   1.0.1 Log improvement
     * @since   1.0.0
     * @return  void
     */
    public function find_user()
    {
        $cellphone = $_POST[ "cellphone" ];
        $email     = $_POST[ "email"     ];
        $passw     = $_POST[ "password"  ];
        $terms     = $_POST[ "terms"     ];

        $user      = get_user_by( "email", $email );

        if ( !empty( $user ) )
        {

            if ( $user && wp_check_password( $passw, $user->data->user_pass, $user->ID ) )
            {
                $response  = $this->create_or_update_user_triibo( $cellphone, $email, $user->first_name, $user->nickname, $terms );

                if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
                {
                    $response[ "data_sent" ] = [
                        "function"  => "find_user",
                        "cellphone" => $cellphone,
                        "email"     => $email,
                    ];

                    $this->log( json_encode( $response ) );
                }

                $cellphone = "+" . preg_replace( "/\D/", "", $cellphone );

                delete_user_meta( $user->ID, "triiboId_phone", $cellphone );
                update_user_meta( $user->ID, "_triibo_phone",  $cellphone );

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
        else
        {
            /**
             * @since 1.1.0
             * Create marketplace account directly
             */
            $response = $this->create_or_update_user_triibo( $cellphone, $email, "", "", $terms );

            if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
            {
                $response[ "data_sent" ] = [
                    "function"  => "find_user",
                    "cellphone" => $cellphone,
                    "email"     => $email,
                ];

                $this->log( json_encode( $response ) );
            }

            $uid      = isset( $response[ "uid" ] ) ? $response[ "uid" ] : null;
            $result   = $this->create_user_marketplace( $uid, $cellphone, $email, $passw );
        }

        echo json_encode( $result );
        die();
    }

    /**
     * Create user triibo
     *
     * @since   1.0.1 Log improvement
     * @since   1.0.0
     * @return  void
     */
    public function add_triibo_id()
    {
        $cellphone = $_POST[ "cellphone" ];
        $user      = wp_get_current_user();

        $response  = $this->create_or_update_user_triibo( $cellphone, $user->user_email, $user->first_name, $user->nickname );

        if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
        {
            $response[ "data_sent" ] = [
                "function"  => "add_triibo_id",
                "cellphone" => $cellphone
            ];

            $this->log( json_encode( $response ) );
        }

        $cellphone = "+" . preg_replace( "/\D/", "", $cellphone );

        delete_user_meta( $user->ID, "triiboId_phone", $cellphone );
        update_user_meta( $user->ID, "_triibo_phone",  $cellphone );

        if ( isset( $response[ "uid" ] ) ) update_user_meta( $user->ID, "_triibo_id", $response[ "uid" ] );

        echo json_encode( [ "success"  => true ] );
        die();
    }

    /**
     * Create or update user triibo
     *
     * @since   1.1.0   Change 'Null Coalescing Operator' to 'Elvis Operator'
     * @since   1.0.0
     * @param   string  $cellphone
     * @param   string  $email
     * @param   string  $name
     * @param   string  $nick
     * @param   bool    $terms
     * @return  string
     */
    private function create_or_update_user_triibo( string $cellphone, string $email, string $name = "", string $nick = "", bool $terms = null )
    {
        $name = $name ?: reset( explode( "@", $email ) );
        $nick = $nick ?: $name;

        return $this->gate->update_user_mkt( $cellphone, $email, "UPDATE", $name, $nick, $terms );
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

            // add_user_meta( $user_id, "triiboId_phone", $cellphone );
            add_user_meta( $user_id, "_triibo_phone", $cellphone );

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
     * @param   string|int  $value
     * @return  array
     */
    private function login_by_triibo( string $field, string $value )
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