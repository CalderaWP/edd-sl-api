<?php
/**
 * Created by PhpStorm.
 * User: josh
 * Date: 10/30/16
 * Time: 10:00 PM
 */

namespace CalderaWP\EDD\SLAPI;


class sites {

	/**
	 * @var \CalderaWP\EDD\SL\sites
	 */
	protected $queries;

	public function __construct( \CalderaWP\EDD\SL\sites $queries ) {
		$this->queries = $queries;
		$this->add_routes();
	}

	/**
	 * Add routes
	 *
	 * @since 0.1.0
	 */
	protected function add_routes() {
		register_rest_route( 'edd-sl-api/v1', '/sites', [
			'methods'              => 'GET',
			'permissions_callback' => [ $this, 'permissions ' ],
			'callback'             => [ $this, 'get_all' ],
			'args'                 => [

			]
		] );

		register_rest_route( 'edd-sl-api/v1', '/sites/download/(?P<id>\d+)', [
			'methods'              => 'GET',
			'permissions_callback' => [ $this, 'permissions ' ],
			'callback'             => [ $this, 'get_by_download' ],
			'args'                 => [

			]
		] );

		register_rest_route( 'edd-sl-api/v1', '/sites/user/(?P<id>\d+)', [
			'methods'              => 'GET',
			'permissions_callback' => [ $this, 'permissions ' ],
			'callback'             => [ $this, 'get_user_sites' ],
			'args'                 => [

			]
		] );
	}

	/**
	 * Permissions callback for requests
	 *
	 * Checks if user is logged in.
	 *
	 * @since 0.1.0
	 *
	 * @return bool
	 */
	public function permissions(){
		return current_user_can( 'manage_options' );
	}

	public function get_all(){
		return rest_ensure_response( $this->queries->get_all() );
	}

	public function get_by_download( \WP_REST_Request $request ){
		return rest_ensure_response( $this->get_by_download( absint( $request[ 'id' ] )) );
	}

	public function get_user_sites( \WP_REST_Request $request ){
		return rest_ensure_response( $this->get_user_sites( absint( $request[ 'id' ] )) );

	}


}