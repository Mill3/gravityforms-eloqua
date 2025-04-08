<?php

/**
Plugin Name: Gravity Forms Eloqua Add-On
Plugin URI: https://gravityforms.com
Description: Integrates Gravity Forms with Eloqua.
Version: 0.0.1
Author: MILL3 Studio
Author URI: https://mill3.studio
License: GPL-2.0+
Text Domain: gravityformseloqua
Domain Path: /languages
 **/

// don't load directly
if ( ! defined( 'ABSPATH' ) ) {
	die();
}

define( 'GF_ELOQUA_VERSION', '0.0.1' );

// If Gravity Forms is loaded, bootstrap the Eloqua Add-On.
add_action( 'gform_loaded', array( 'GF_Eloqua_Bootstrap', 'load' ), 5 );

/**
 * Class GF_MailChimp_Bootstrap
 *
 * Handles the loading of the Mailchimp Add-On and registers with the Add-On Framework.
 */
class GF_Eloqua_Bootstrap {

	/**
	 * If the Feed Add-On Framework exists, Mailchimp Add-On is loaded.
	 *
	 * @access public
	 * @static
	 */
	public static function load() {

    // GFCommon::log_debug('Eloqua Add-On loaded : ' . GF_ELOQUA_VERSION);

		if ( ! method_exists( 'GFForms', 'include_addon_framework' ) ) {
			return;
		}

		require_once( 'class-gf-eloqua.php' );

		GFAddOn::register( 'GF_Eloqua' );
	}
}

/**
 * Returns an instance of the GF_Eloqua class
 *
 * @see    GF_Eloqua::get_instance()
 *
 * @return GF_Eloqua
 */
function gf_eloqua() {
	return GF_Eloqua::get_instance();
}
