<?php
/**
 Plugin Name: EDD Software Licensing REST API
 Description: Adds WordPress REST API endpoints to Easy Digital Downloads Software Licensing
 Version:  0.1.0
 Plugin URI: https://calderawp.com
 Author URI: https://CalderaWP.com
 Plugin Author: Josh Pollock for CalderaWP LLC
 */


/**
 * Copyright 2016 Josh Pollock for CalderaWP LLC
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License, version 2, as
 * published by the Free Software Foundation.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
 */

add_action( 'rest_api_init', function(){
	//include __DIR__ . '/vendor/autoload.php';
	require_once __DIR__ . '/vendor/calderawp/edd-sl-queries/src/file.php';
	require_once  __DIR__ .'/src/route.php';
	require_once __DIR__ .'/src/sites.php';
	require_once __DIR__ . '/vendor/calderawp/edd-sl-queries/src/sites.php';
	require_once __DIR__ . '/vendor/calderawp/edd-sl-queries/src/user.php';
	new \CalderaWP\EDD\SLAPI\route();
	new \CalderaWP\EDD\SLAPI\sites( new \CalderaWP\EDD\SL\sites() );
});

/**
 * Get all licensed add-ons for a user
 *
 * @param null|int $user_id Optional. User ID, current user ID if mull
 * @param bool $names_only Optioanl. If true, the default title of downloads is returned as ipposed to returning all the things.
 * @param bool $include_expired Optional. If false the default, expired licenses will be skipped.
 *
 * @return bool|array Array of download_id => download title or false if none found.
 */
function cwp_edd_sl_get_downloads_by_licensed_user( $user_id = null, $names_only = true, $include_expired = false ) {
	if ( is_null( $user_id ) ){
		$user_id = get_current_user_id();
	}
	$licensed_downloads = false;
	if ( 0 < absint( $user_id ) ) {
		global $wpdb;
		$query = $wpdb->prepare( 'SELECT `post_id` FROM `%2s` WHERE `meta_value` = %d AND `meta_key` = "_edd_sl_user_id"', $wpdb->postmeta, $user_id );
		$licenses = $wpdb->get_results( $query, ARRAY_A );
		if ( ! empty( $licenses ) ) {
			foreach( $licenses as $license ) {
				$license_id = $license[ 'post_id' ];
				if ( false == $include_expired ) {
					
					$status = get_post_meta( $license_id, '_edd_sl_status', true );
					if ( false ==  $status ) {
						continue;
					}
				}

				$download_id = get_post_meta( $license[ 'post_id' ], '_edd_sl_download_id', true );

				if ( $download_id ) {
					if ( $names_only ) {
						$licensed_downloads[ $download_id ] = get_the_title( $download_id );
					}else{
						$activations = EDD_Software_Licensing::instance()->get_site_count( $license_id );
						$at_limit = EDD_Software_Licensing::instance()->is_at_limit( $license_id, $download_id );
						$sites = EDD_Software_Licensing::instance()->get_sites( $license_id );
						$unlimited = EDD_Software_Licensing::instance()->is_lifetime_license( $license_id );
						$limit = EDD_Software_Licensing::instance()->get_license_limit( $download_id, $license_id );
						$code = EDD_Software_Licensing::instance()->get_license_key( $license_id );

						$licensed_downloads[ $download_id ] = [
							'title'       => get_the_title( $download_id ),
							'download'    => $download_id,
							'slug'        => get_post( $download_id )->post_name,
							'code'        => $code,
							'sites'       => $sites,
							'activations' => $activations,
							'at_limit'    => $at_limit,
							'unlimited'   => $unlimited,
							'limit'       => $limit,
							'license'     => $license_id
						];
					}
				}
			}
		}
	}

	return $licensed_downloads;
}
