<?php
/**
 * AWPF - admin filter, actions, variables and includes
 *
 * @author		Nir Goldberg
 * @package		admin
 * @version		1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class awpf_admin {

	/**
	 * __construct
	 *
	 * Initialize filters, action, variables and includes
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function __construct() {

		// actions
		add_action( 'admin_menu', array( $this, 'admin_menu' ) );

	}

	/**
	 * admin_menu
	 *
	 * This function will add the AWPF menu item to the WP admin
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function admin_menu() {
		
		// exit if no show_admin
		if( ! awpf_get_setting('show_admin') )
			return;

		// vars
		$slug			= 'awpf-dashboard';
		$capability		= awpf_get_setting('capability');

		// add parent
		add_menu_page( __('Products Filter', 'awpf'), __('Products Filter', 'awpf'), $capability, $slug, false, awpf_get_dir('assets/images/awpf-logo.png'), '56.01.01' );

	}

}

// initialize
new awpf_admin();