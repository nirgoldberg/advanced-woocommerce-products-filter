<?php
/**
 * AWPF - admin dashboard filter, actions, variables and includes
 *
 * @author		Nir Goldberg
 * @package		admin
 * @version		1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class awpf_dashboard {

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
		$dashboard_slug	= 'awpf-dashboard';
		$capability		= awpf_get_setting('capability');

		// Dashboard
		add_submenu_page( $slug, __('Products Filter Dashboard', 'awpf'), __('Dashboard', 'awpf'), $capability, $dashboard_slug, array($this, 'html') );

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
			'tabs'			=> array(
				'new'			=> __("What's New", 'awpf'),
				'changelog'		=> __("Changelog", 'awpf')
			),
			'active'		=> 'new'
		);

		// set active tab
		if ( !empty($_GET['tab']) && array_key_exists( $_GET['tab'], $view['tabs'] ) ) {

			$view['active'] = $_GET['tab'];

		}

		// load view
		awpf_get_view('dashboard', $view);

	}

}

// initialize
new awpf_dashboard();