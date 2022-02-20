<?php
/**
 * AWPF - admin settings filter, actions, variables and includes
 *
 * @author		Nir Goldberg
 * @package		admin
 * @version		1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class awpf_settings {

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
		$settings_slug	= 'awpf-settings';
		$capability		= awpf_get_setting('capability');

		// Settings
		add_submenu_page( $slug, __('Products Filter Settings', 'awpf'), __('Settings', 'awpf'), $capability, $settings_slug, array($this, 'html') );

	}

	/**
	 * html
	 *
	 * Output html content
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function html() {

		// vars
		$view = array(
			'version'		=> awpf_get_setting('version'),
		);

		// load view
		awpf_get_view('settings', $view);

	}

}

// initialize
new awpf_settings();