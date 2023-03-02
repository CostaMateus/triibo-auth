<?php
/**
 * Initialize this version of the REST API.
 *
 * @author  Mateus Costa <mateus@costamateus.com.br>
 * @package Triibo_Auth/Classes
 * @version 1.0.0
 */
defined( 'ABSPATH' ) || exit;

/**
 * Class responsible for loading the REST API and all REST API namespaces.
 */
class Triibo_Auth_Rest_Api
{
	/**
	 * Initialize the plugin public actions.
     *
	 * @since   1.0.0
	 */
    public function __construct()
	{
		require_once( dirname( __FILE__ ) . "/controllers/version1/class-triibo-auth-rest-users-controller.php" );

        add_action( "rest_api_init", [ $this, "register_rest_routes" ] );
    }

    /**
     * Register REST API routes.
     *
	 * @since   1.0.0
	 * @return  void
     */
    public function register_rest_routes()
    {
        foreach ( self::get_rest_namespaces() as $controllers )
        {
            foreach ( $controllers as $controller_class )
            {
                $controller = new $controller_class();
                $controller->register_routes();
            }
        }
    }

    /**
     * Get API namespaces - new namespaces should be registered here.
     *
	 * @since   1.0.0
     * @return  array List of Namespaces and Main controller classes.
     */
    private static function get_rest_namespaces()
    {
        return apply_filters(
            "triibo_auth_rest_api_namespaces",
            [
                "triibo-rest/v1" => [
                    "users" => "Triibo_Auth_Rest_Users_Controller",
                ]
            ]
        );
    }
}
