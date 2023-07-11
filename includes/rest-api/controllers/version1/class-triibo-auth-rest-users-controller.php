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
	protected $id        = TRIIBO_AUTH_APP;

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
	 * @since   1.1.0 New flow
     * @since   1.0.1 Log improvement
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
			$this->log( "Redirecting only, disabled auth-app module." );
			$this->redirect( $url );
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
			if ( !$bearer && ! $token                      ) $this->redirect( $url );
			if ( !$this->validate_token( $bearer, $token ) ) $this->redirect( $url );
		}

		$cellphone = $request->get_param( "cellphone" ) ?: null;
        $cellphone = preg_replace( "/\D/", "", $cellphone );
		set_transient( "_taa_triibo_cellphone", $cellphone );

		$data      = $request->get_param( "data" ) ?: null;
		$user      = null;

		// celular e email ausentes na request
		// redireciona para $url ou para "/", caso $url == null
		if ( !$cellphone && !$data ) $this->redirect( $url );

		if ( $cellphone )
		{
			$user = get_users( [
				"meta_key"   => "triiboId_phone",
				"meta_value" => "+$cellphone"
			] );

			if ( !empty( $user ) )
			{
				$user     = reset( $user );
				$email    = $user->user_email;

				$response = $this->gate->update_user_mkt( $cellphone, $email, "UPDATE", $user->first_name, $user->nickname );

				if ( !$response[ "success" ] || ( isset( $response[ "data" ] ) && !$response[ "data" ][ "success" ] ) )
				{
					$response[ "data_sent" ] = [
						"function"  => "rest_api_process_login",
						"cellphone" => $cellphone,
						"email"     => $email,
					];

					$this->log( json_encode( $response ) );
				}

                if ( isset( $response[ "uid" ] ) ) update_user_meta( $user->ID, "_triibo_id", $response[ "uid" ] );

				$this->make_login( $user->ID, $url );
			}
		}

		$this->process_data_user( $data, $url );
	}

	/**
	 * Process data user for proper redirect
	 *
	 * @since   1.1.0
	 * @param 	string|null $data
	 * @param 	string 		$url
	 * @return 	void
	 */
	private function process_data_user( string|null $data, string $url )
	{
		if ( $data )
		{
			$arr      = explode( "|", $data );
			$email    = $arr[ 1 ];

			$user_id  = email_exists( $email );

			$params   = ( $user_id ) ? "taac=acc" : "taac=newacc";
			$params  .= "&email={$email}";

			$this->redirect( $url, $params );
		}
		else
		{
			wp_clear_auth_cookie();
			$this->redirect( $url );
		}
	}

    /**
     * Returns the Triibo_Auth_App status
     * TRUE  for active.
     * FALSE for inactive.
     *
     * @since   1.0.0
     * @return 	boolean
     */
	private function status()
	{
		return Triibo_Auth_App::status();
	}

	/**
	 * Redirect user
	 *
	 * @since   1.0.0
	 * @param 	string|null $url
	 * @param 	string $params
	 * @return 	void
	 */
	private function redirect( string $url = null, string $params = null )
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
	 * @deprecated 	1.1.0 	Will be removed in 1.2.0
	 *
	 * @since   1.0.0
	 * @param 	string $email
	 * @return 	void
	 */
	private function send_email_code( string $email )
    {
        $response = Triibo_Auth::send_code_by_email( $email );

		return ( $response[ "success" ]) ? true : false ;
    }

	/**
	 * Make login for user
	 *
	 * @since   1.0.0
	 * @param 	integer $user_id
	 * @param 	string 	$url
	 * @return 	void
	 */
	private function make_login( int $user_id, string $url )
	{
		clean_user_cache( $user_id );
		wp_clear_auth_cookie();
		wp_set_current_user( $user_id );
		wp_set_auth_cookie( $user_id, true );
		$this->redirect( $url );
	}

	/**
	 * Validates the token
	 *
     * @since   1.0.1 Log improvement
	 * @since   1.0.0
	 * @param 	string $bearer
	 * @param 	string $tkn
	 * @return 	void
	 */
	private function validate_token( string $bearer, string $tkn = null )
	{
		$bearer   = ( !$bearer && $tkn ) ? base64_decode( $tkn ) : $bearer;
		$response = $this->node->validate_token( $bearer );

		if ( !$response[ "success" ] && !$response[ "data" ][ "success" ] )
		{
			$response[ "data_sent" ] = [ "function" => "validate_token" ];

			$this->log( json_encode( $response ) );

			return false;
		}

		return true;
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
