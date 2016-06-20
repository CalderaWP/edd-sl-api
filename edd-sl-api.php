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
	new CWP_EDD_SL_API_Route();
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
				if ( false == $include_expired ) {
					$status = get_post_meta( $license[ 'post_id' ], '_edd_sl_status', true );
					if ( false ==  $status ) {
						continue;
					}
				}

				$id = get_post_meta( $license[ 'post_id' ], '_edd_sl_download_id', true );

				if ( $id ) {
					if ( $names_only ) {
						$licensed_downloads[ $id ] = get_the_title( $id );
					}else{
						$licensed_downloads[ $id ] = [
							'download' => get_post( $id ),
							'code' => get_post_meta( $license[ 'post_id' ],  '_edd_sl_key', true ),
							'sites' => get_post_meta( $license[ 'post_id' ],  '_edd_sl_sites', true ),
							'code_id'  => $license[ 'post_id' ],

						];
					}
				}
			}
		}
	}

	return $licensed_downloads;
}
