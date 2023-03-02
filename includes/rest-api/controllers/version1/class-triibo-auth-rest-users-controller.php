<?php
/**
 * REST API Users controller
 * Handles requests to the /users endpoint.
 *
 * @author  Mateus Costa <mateus@costamateus.com.br>
 * @package Triibo_Auth/Classes
 * @version 1.0.0
 */
defined( "ABSPATH" ) || exit;

/**
 * REST API Users controller class.
 */
class Triibo_Auth_Rest_Users_Controller extends WP_REST_Controller
{
	/**
	 * Endpoint namespace.
	 *
	 * @since   1.0.0
	 * @var 	string
	 */
	protected $namespace = "triibo-rest/v1";

	/**
	 * Route base.
	 *
	 * @since   1.0.0
	 * @var 	string
	 */
	protected $rest_base = "users";

	/**
	 * Stores the request.
	 *
	 * @since   1.0.0
	 * @var 	array
	 */
	protected $request   = [];

	/**
	 * ID of resource.
	 *
	 * @since   1.0.0
	 * @var 	string
	 */
	protected $id        = Triibo_Auth::DOMAIN . "_app";

	/**
	 * API Gateway.
	 *
	 * @since   1.0.0
	 * @var 	Triibo_Api_Gateway
	 */
	protected $gate;

	/**
	 * API Node.
	 *
	 * @since   1.0.0
	 * @var 	Triibo_Api_Node
	 */
	protected $node;

	/**
	 * WC Logger.
	 *
	 * @since   1.0.0
	 * @var 	wc_get_logger|WC_Logger
	 */
	protected $log;

	/**
	 * Construct
	 *
	 * @since   1.0.0
	 */
	public function __construct()
	{
		$this->log  = ( function_exists( "wc_get_logger" ) ) ? wc_get_logger() : new WC_Logger();
		$this->gate = new Triibo_Api_Gateway();
		$this->node = new Triibo_Api_Node();
	}

    /**
     * Returns the Triibo_Auth_App status
     * TRUE  for active.
     * FALSE for inactive.
     *
     * @since   1.0.0
     * @return 	boolean
     */
	public function status()
	{
        $status = get_option( Triibo_Auth::DOMAIN . "_app_status" );
		return $status == "on" ? true : false;
	}

	/**
	 * Register the routes for users.
	 *
	 * @since   1.0.0
	 * @return 	void
	 */
	public function register_routes()
	{
		register_rest_route(
			$this->namespace,
			"/$this->rest_base/login",
			[
				[
					"methods"             => WP_REST_Server::READABLE, // WP_REST_Server::EDITABLE, // POST, PUT, PATCH
					"callback"            => [ $this, "process_login" ],
					"permission_callback" => "__return_true",
					"args"                => $this->get_collection_params(),
				],
			]
		);
	}

	/**
	 * Process login for user
	 *
	 * @since   1.0.0
	 * @param 	WP_REST_Request $request
	 * @return 	void
	 */
	public function process_login( $request )
	{
		$url = $request->get_param( "url" ) ?: null;

		if ( !$this->status() )
		{
			wp_clear_auth_cookie();
			self::redirect( $url );
		}

		/**
		 * Gate: false === prod
		 * Node: false === prod
		 */
		if ( $this->gate->env() === false && $this->node->env() === false )
		{
			$token  = $request->get_param( "mkt" ) ?: null;
			$bearer = $request->get_header( "authorization" );

			/**
			 * ios     - manda apenas bearer
			 * android - manda apenas token
			 *
			 * ausencia dos dois é problema
			 */
			if ( !$bearer && ! $token                      ) self::redirect( $url );
			if ( !$this->validate_token( $bearer, $token ) ) self::redirect( $url );
		}

		$cellphone = $request->get_param( "cellphone" ) ?: null;
        $cellphone = preg_replace( "/\D/", "", $cellphone );
		set_transient( "_taa_triibo_cellphone", $cellphone );

		$data      = $request->get_param( "data" ) ?: null;
		$user      = null;
		$params    = null;

		// celular e email ausentes na request
		// redireciona para $url ou para "/", caso $url == null
		if ( !$cellphone && !$data ) self::redirect( $url );

		// passo 2
		if ( $data )
		{
			$arr      = explode( "|", $data );
			$type     = $arr[ 0 ]; // Type:     email || emailMarketplace
			$email    = $arr[ 1 ]; // Value:    me@example.com
			$verified = $arr[ 2 ]; // Verified: true || false || 1 || 0
			$verified = filter_var( $verified, FILTER_VALIDATE_BOOLEAN );

			// passo 3
			if ( $type == "emailMarketplace" && $verified )
			{
				// passo 4
				$user_id = email_exists( $email );

				// passo 5 ( passo_comum )
				self::make_login( $user_id, $url );
			}
			// passo 6
			else
			{
				// passo 7
				$user_id = email_exists( $email );

				// passo 8
				if ( $user_id && $verified )
				{
					// passo 9
					update_user_meta( $user_id, "triiboId_phone", "+$cellphone" );

					// passo 10 ( passo_comum )
					$response = $this->gate->update_user_mkt( $cellphone, $email, "ADD" );
					if ( !$response[ "success" ] || !$response[ "data" ][ "success" ] )
						$this->log->add( $this->id, json_encode( $response ) );

					self::make_login( $user_id, $url );
				}
				// passo 8
				else if ( $user_id && !$verified )
				{
					// passo 11
					$params = self::send_email_code( $email ) ? "taac=code&email={$email}" : null;

					// passo 12
					self::redirect( $url, $params );
				}
				// passo 16
				else if ( !$user_id && $verified )
				{
					// passo 17
					$params = "taac=newacc&email={$email}";

					self::redirect( $url, $params );
				}
				// passo 16
				else // ( !$user_id && !$verified )
				{
					// passo 21
					$params = self::send_email_code( $email ) ? "taac=code&email={$email}" : null;

					self::redirect( $url, $params );
				}
			}
		}
		else if ( $cellphone )
		{
			// passo 25
			$user = get_users( [
				"meta_key"   => "triiboId_phone",
				"meta_value" => "+$cellphone"
			] );

			if ( !empty( $user ) )
			{
				// passo 26
				$user   = reset( $user );
				$email  = $user->user_email;

				// passo 27
				$params = "taac=acc&email=$email";

				self::redirect( $url, $params );
			}
			else
			{
				// passo 29
				$params = "taac=newacc";

				self::redirect( $url, $params );
			}
		}
		else
		{
			wp_clear_auth_cookie();

			self::redirect( $url );
		}
	}

	/**
	 * Redirect user
	 *
	 * @since   1.0.0
	 * @param 	string|null $url
	 * @param 	string $params
	 * @return 	void
	 */
	private static function redirect( string $url = null, string $params = null )
	{
		if ( $url )
		{
			if ( ( strpos( $url, "?" ) ) !== false && $params )
				$url .= "&$params";
			elseif ( $params )
				$url .= "?$params";
		}
		else
		{
			$url = home_url();
		}

		wp_safe_redirect( esc_url_raw( $url ) );
		exit();
	}

	/**
	 * Send code by email
	 *
	 * @since   1.0.0
	 * @param 	string $email
	 * @return 	void
	 */
	private static function send_email_code( string $email )
    {
        $validCode = str_pad( mt_rand( 0, 999999 ), 6, "0", STR_PAD_LEFT );

        $to      = $email;
        $subject = "Seu código de ativação";
        $body    = "<html lang='pt-br'><body><p>Seu código de ativação do Marketplace Triibo é:</p><p style='font-size:18px;'><b>$validCode</b></p><p style='font-size:12px;margin:0'>Não compartilhe esse código com ninguém.</p></body></html>";
        $headers = [ "Content-Type: text/html; charset=UTF-8","From: Marketplace Triibo <equipemarketplace@triibo.com.br>" ];

        if ( wp_mail( $to, $subject, $body, $headers ) )
		{
			set_transient( "_taa_triibo_code", $validCode, 300 );
			return true;
		}

		return false;
    }

	/**
	 * Make login for user
	 *
	 * @since   1.0.0
	 * @param 	integer $user_id
	 * @param 	string 	$url
	 * @return 	void
	 */
	private static function make_login( int $user_id, string $url )
	{
		clean_user_cache( $user_id );
		wp_clear_auth_cookie();
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
		self::redirect( $url );
	}

	/**
	 * Validates the token
	 *
	 * @since   1.0.0
	 * @param 	string $bearer
	 * @param 	string $tkn
	 * @return 	void
	 */
	private function validate_token( string $bearer, string $tkn = null )
	{
		$bearer   = ( !$bearer && $tkn ) ? base64_decode( $tkn ) : $bearer;
		$response = $this->node->validate_token( $bearer );

		if ( $response[ "success" ] && $response[ "data" ][ "success" ] ) return true;

		$this->log->add( $this->id, json_encode( $response ) );

		return false;
	}
}
