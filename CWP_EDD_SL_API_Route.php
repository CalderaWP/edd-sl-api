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
		register_rest_route( 'edd-sl-api/v1', '/licenses/(?P<id>\d+)', [
			'methods' => 'POST',
			'permissions_callback' => [ $this, 'permissions '],
			'args' =>[
				'download' => [
					'required' => true,
					'sanitize_callback' => 'sanitize_title'
				],
				'url' => [
					'required' => true,
					'sanitize_callback' => [ $this, 'prepare_url' ]
				],
				'action' => [
					'required' => true,
					'validate_callback' => [ $this, 'validate_action' ]
				]

			],
			'callback' => [ $this, 'update_license' ]
		] );

		register_rest_route( 'edd-sl-api/v1', '/licenses/(?P<id>\d+)', [
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

		register_rest_route( 'edd-sl-api/v1', '/licenses/(?P<id>\d+)/file', [
			'methods' => 'GET',
			'permissions_callback' => [ $this, 'permissions '],
			'callback' => [ $this, 'get_file' ],
			'args' => [
				'download' => [
					'required' => true,
					'sanitize_callback' => 'sanitize_title'
				],
				'url' => [
					'required' => true,
					'sanitize_callback' => [ $this, 'prepare_url' ]
				]
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
		return true;
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
		if( 0 == get_current_user_id() ) {
			//return $this->return_error( 403, 'You must be logged in' );
		}

		$sl = EDD_Software_Licensing::instance();
		$license_key = $sl->get_license_key( $request[ 'id' ] );
		$download = get_post( $request[ 'download'] );
		if( ! is_object( $download ) ){
			return $this->return_error();
		}

		$args = array(
			'key'        => $license_key,
			'item_name'  => $download->post_title,
			'item_id'    => $download->ID,
			'url'        => $request[ 'url' ]
		);


		$activated = $sl->activate_license( $args );
		return rest_ensure_response( $activated );
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
	 * Get a file URL for a download
	 *
	 * @since 0.1.0
	 * 
	 * @param WP_REST_Request $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_file( WP_REST_Request $request ){
		$license_id = $request[ 'id' ];
		$url = urldecode( $request['url'] );
		
		if( EDD_Software_Licensing::instance()->is_site_active( $license_id, $url ) ){
			$download_id = $request[ 'download' ];
			$url = $this->get_download_file( $download_id, $license_id );
			if( ! empty( $url ) && filter_var( $url, FILTER_VALIDATE_URL ) ){
				return rest_ensure_response( [ 'link' => $url ] );
			}else{
				return $this->return_error( 500, esc_html__( 'Error making a file download link. Please complain to Josh', 'edd-sl-api' ) );
			}

		}else{
			$this->return_error( '500', esc_html__( 'Download license not active on this site', 'edd-sl-api' ) );
		}

	}

	/**
	 *
	 * This is  EDD_SL_Package_Download::get_download_package() minus the security. Assumption here is user is authenticated.
	 *
	 * @param $download_id
	 *
	 * @return mixed|void
	 */
	protected function get_download_file( $download_id, $license_id ){


		$payment_id = EDD_Software_Licensing::instance()->get_payment_id( $license_id );
		$payment_key = edd_get_payment_key( $payment_id );
		$file_key  = get_post_meta( $download_id, '_edd_sl_upgrade_file_key', true );
		$email       = edd_get_payment_user_email( $payment_id );

		$file = edd_get_download_file_url( $payment_key, $email, $file_key, $download_id );

		return $file;

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

	public function prepare_url( $url ){
		return esc_url_raw( urldecode( $url ) );
	}
}