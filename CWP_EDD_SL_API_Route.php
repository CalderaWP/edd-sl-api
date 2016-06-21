<?php

class CWP_EDD_SL_API_Route {


	/**
	 * CWP_EDD_SL_API_Route constructor.
	 *
	 * @since 0.1.0
	 */
	public function __construct() {
		$this->add_routes();
	}

	/**
	 * Add routes
	 *
	 * @since 0.1.0
	 */
	protected function add_routes(){
		register_rest_route( 'edd-sl-api/v1', '/licenses', [
			'methods' => 'GET',
			'permissions_callback' => [ $this, 'permissions '],
			'callback' => [ $this, 'get_licenses' ],
			'args' => [
				'return'=> [
					'default' => 'names',
					'validate_callback' => [ $this, 'validate_return_type' ]
				]
			]
		] );
		register_rest_route( 'edd-sl-api/v1', '/license/(?P<id>\d+)', [
			'methods' => 'POST',
			'permissions_callback' => [ $this, 'permissions '],
			'args' =>[
				'download' => [
					'required' => true,
					'sanitize_callback' => 'sanitize_title'
				],
				'url' => [
					'required' => true,
					'sanitize_callback' => 'esc_url_raw'
				],
				'action' => [
					'required' => true,
					'validate_callback' => [ $this, 'validate_action' ]
				]

			],
			'callback' => [ $this, 'update_license' ]
		] );

		register_rest_route( 'edd-sl-api/v1', '/license/(?P<id>\d+)', [
			'methods' => 'GET',
			'permissions_callback' => [ $this, 'permissions '],
			'callback' => [ $this, 'get_license' ],
			'args' => [
				'download' => [
					'required' => true,
					'sanitize_callback' => 'sanitize_title'
				],
			]
		] );

		register_rest_route( 'edd-sl-api/v1', '/license/(?P<id>\d+)/file', [
			'methods' => 'GET',
			'permissions_callback' => [ $this, 'permissions '],
			'callback' => [ $this, 'get_file' ],
			'args' => [
				'download' => [
					'required' => true,
					'sanitize_callback' => 'sanitize_title'
				],
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
		return is_user_logged_in();
	}

	/**
	 * Get licenses for logged in users
	 * 
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return mixed|WP_REST_Response
	 */
	public function get_licenses( WP_REST_Request $request ){
		if( 0 == get_current_user_id() ) {
			return $this->return_error( 403, 'You must be logged in' );
		}
		
		if( 'full' ==  $request[ 'return' ] ){
			$names_only = false;
		}else{
			$names_only = true;
		}

		$licenses = cwp_edd_sl_get_downloads_by_licensed_user( get_current_user_id(), $names_only );
		if( ! empty( $licenses ) ){
			return rest_ensure_response( $licenses );
		}else{
			return $this->return_404();
		}
		
	}

	/**
	 * Update a license
	 *
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function update_license( WP_REST_Request $request ){
		return $this->return_not_yet();
	}

	/**
	 * Get a license 
	 * 
	 * @since 0.1.0
	 *
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_license( WP_REST_Request $request ){
		return $this->return_not_yet();
	}

	/**
	 * Get a file for a download
	 *
	 * @since 0.1.0
	 * 
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_file( WP_REST_Request $request ){
		return $this->return_not_yet();
	}

	/**
	 * Validate return type for a license request
	 * 
	 * @since 0.1.0
	 * 
	 * @param $value
	 *
	 * @return bool
	 */
	public function validate_return_type( $value ){
		return in_array( $value, [ 'names', 'full' ] );
	}

	/**
	 * Validate update action
	 * 
	 * @since 0.1.0
	 * 
	 * @param $value
	 *
	 * @return bool
	 */
	public function validate_action( $value ){
		return in_array( $value, [ 'activate', 'deactivate' ] );
	}

	/**
	 * Return an error
	 *
	 * @since 0.1.0.
	 *
	 * @param int $code Optional. Status code. default is 500
	 * @param mixed $data Optional. Data to return. Default is empty string.
	 *
	 * @return WP_REST_Response
	 */
	protected function return_error( $code = 500, $data = '' ){
		$response = new WP_REST_Response( $data );
		$response->set_status( $code );
		return $response;

	}

	/**
	 * Return a 404
	 * 
	 * @since 0.1.0
	 *
	 * @param string $message Optional. Error message. Default is empty string.
	 * 
	 * @return WP_REST_Response
	 */
	protected function return_404( $message = ''){
		return $this->return_error( 404, $message );
	}

	/**
	 * Return a not implemented response 501
	 * 
	 * @since 0.1.0
	 * 
	 * @return WP_REST_Response
	 */
	protected function return_not_yet(){
		return $this->return_error( 501 , __( 'Not implemented', 'cwp-edd-sl-api') );
	}
}