EDD Software Licensing REST API
===============================

Provides a RESTful API, using the WordPress REST API for managing Easy Digital Downloads software licenses.

Note: All routes require authentication through WordPress. I recommend using [JWT](https://wordpress.org/plugins/jwt-authentication-for-wp-rest-api/), which is easy and is as secure as wp-login (IE fairly secure if using HTTPS and you are using fail2ban or something.)

<strong>Requires PHP 7.0 or later</strong>


# Routes

## Licenses 
By license ID for getting and using licenses;

### `/licenses`

GET only

* `edd-sl-api/v1/licenses`
    * Get the titles of all products current user has an active license for.
* `edd-sl-api/v1/licenses?return=full`
    * Get full details about all licenses current user has an active license for.
    * Returns:
        * `title` - Download title
        * `download` - download id
        * `slug` - Download slug
        * `code` - License code
        * `activations` - Number of times license has been activates
        * `sites` - Urls of sites license is active on
        * `at_limit` - If license is at limit
        * `unlimited` - If license is unlimited
        * `limit` - License activation limit
        * `license` - License ID

### `license/<id>`
ID is ID of license code, not the license code.

* POST - Activate or deactivate a license
    * Required arguments
        * `download` - ID of download
        * `url` - URL of site license is being activated on
        * `action` - Either `activate` or `deactivate`
    * Returns:
            * Uses EDD_Software_Licensing::activate_license() return is the same.
            * example return object:
                ```
                    {
                        "success": true
                        "license_limit": "1"
                        "site_count": 1
                        "expires": "1466644121"
                        "activations_left": 0
                    }
                ```
* GET - View license info for a specific license
    * <strong> not implemented </strong>
    * Required arguments
        * `download` - ID of download
    * Returns:
        * ??

### `license/<id>/file`
ID is ID of license code, not the license code.

* GET get download file for license
    * Site must have already been activated or an error will happen.
    * Required arguments
        * `download` - ID of download
        * `url` - URL of site with license activated for that site
    * Returns:
        * Array with one key `link` that has the download link.
        * NOTE: This is the secured EDD link and is subject to same expiration limits.


## Sites 
Get sites with licenses activated
* Makes use of our [EDD SL Queries Library](https://github.com/CalderaWP/edd-sl-queries)


### `sites`
GET All sites with an active license for any download

### `sites/user/<id>`
GET All sites that a user has a license of any download active on.

### `sites/download/<id>`
GET All sites that a download has an activated license on.

### Copyright/ License
Copyright 2016 Josh Pollock & CalderaWP LLC. Licensed under the terms of the GNU GPL v2 or later.

