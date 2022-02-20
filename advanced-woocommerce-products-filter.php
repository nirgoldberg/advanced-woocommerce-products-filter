<?php
/**
 * Plugin Name: Advanced WooCommerce Products Filter
 * Plugin URI: http://www.htmline.com/
 * Description: WooCommerce widget for products filter
 * Version: 1.0 
 * Author: Nir Goldberg 
 * Author URI: http://www.htmline.com/
 * License: GPLv2+
 * Text Domain: awpf
 * Domain Path: /lang
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// check if WooCommerce is active
if ( ! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option('active_plugins') ) ) )
	return;

if ( ! class_exists('awpf') ) :

class awpf {

	var $settings;

	/**
	 * __construct
	 *
	 * A dummy constructor to ensure AWPF is only initialized once
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function __construct() {

		/* Do nothing here */

	}

	/**
	 * initialize
	 *
	 * The real constructor to initialize AWPF
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function initialize() {

		$this->settings = array(
			// basic
			'name'			=> __('Advanced WooCommerce Products Filter', 'awpf'),
			'version'		=> '1.0',

			// urls
			'basename'		=> plugin_basename( __FILE__ ),
			'path'			=> plugin_dir_path( __FILE__ ),		// with trailing slash
			'dir'			=> plugin_dir_url( __FILE__ ),		// with trailing slash

			// options
			'show_admin'	=> true,
			'widget_style'	=> true,
			'capability'	=> 'manage_options',
			'active_skin'	=> 'skin01',
			'debug'			=> false
		);

		// include helpers
		include_once('api/api-helpers.php');

		// wpml fix
		if ( defined('DOING_AJAX') && DOING_AJAX ) {
			add_action( 'setup_theme', array($this, 'awpf_wpml_fix') );
		}

		// admin
		if ( is_admin() ) {
			
			awpf_include('admin/admin.php');
			awpf_include('admin/dashboard.php');
			awpf_include('admin/settings.php');
			awpf_include('admin/widgets.php');

		}

		// functions
		awpf_include('includes/awpf-hooks.php');
		awpf_include('includes/awpf-functions.php');

		// widgets
		awpf_include('widgets/awpf/awpf-widget.php');
		awpf_include('widgets/awpf/awpf-widget-front.php');

		// actions
		add_action( 'init',	array($this, 'init'), 5 );
		add_action( 'init',	array($this, 'register_assets'), 5 );

		// plugin activation / deactivation
		register_activation_hook( __FILE__,		array( $this, 'awpf_install' ) );
		register_deactivation_hook( __FILE__,	array( $this, 'awpf_uninstall' ) );

	}

	/**
	 * init
	 *
	 * This function will run after all plugins and theme functions have been included
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function init() {

		// exit if called too early
		if ( ! did_action('plugins_loaded') )
			return;

		// exit if already init
		if( awpf_get_setting('init') )
			return;

		// only run once
		awpf_update_setting('init', true);

		// redeclare dir - allow another plugin to modify dir
		awpf_update_setting( 'dir', plugin_dir_url( __FILE__ ) );

		// set text domain
		load_textdomain( 'awpf', awpf_get_path( 'lang/awpf-' . get_locale() . '.mo' ) );

		// action for 3rd party
		do_action('awpf/init');

	}

	/**
	 * register_assets
	 *
	 * This function will register scripts and styles
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function register_assets() {

		// vars
		$version	= awpf_get_setting('version');
		$lang		= get_locale();
		$scripts	= array();
		$styles		= array();

		// append scripts
		$scripts['jquery-ui'] = array(
			'src'	=> awpf_get_dir('assets/js/jquery-ui.min.js'),
			'deps'	=> array('jquery')
		);

		$scripts['jquery-ui-touch-punch'] = array(
			'src'	=> awpf_get_dir('assets/js/jquery.ui.touch-punch.min.js'),
			'deps'	=> array('jquery-ui')
		);

		$scripts['awpf-products-filter'] = array(
			'src'	=> awpf_get_dir('assets/js/awpf-products-filter.min.js'),
			'deps'	=> array('jquery-ui')
		);

		// register scripts
		foreach ( $scripts as $handle => $script ) {

			wp_register_script( $handle, $script['src'], $script['deps'], $version );

		}

		// append styles
		$styles['jquery-ui'] = array(
			'src'	=> awpf_get_dir('assets/css/jquery-ui.min.css'),
			'deps'	=> false
		);

		$styles['awpf-admin-style'] = array(
			'src'	=> awpf_get_dir('assets/css/awpf-admin-style.css'),
			'deps'	=> false
		);

		// register styles
		foreach( $styles as $handle => $style ) {

			wp_register_style( $handle, $style['src'], $style['deps'], $version );

		}

	}

	/**
	 * awpf_wpml_fix
	 *
	 * Fix WPML current language
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	public static function awpf_wpml_fix() {

		global $sitepress;

		if ( method_exists( $sitepress, 'switch_lang' ) && isset( $_POST['wpml_lang'] ) ) {
			$sitepress->switch_lang( $_POST['wpml_lang'], true );
		}

	}

	/**
	 * template_path
	 *
	 * Get the template path
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		string
	 */
	public function template_path() {

		return apply_filters( 'awpf_template_path', 'awpf/templates/' );

	}

	/**
	 * skin_path
	 *
	 * Get the skin path
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		string
	 */
	public function skin_path() {

		return apply_filters( 'awpf_skin_path', 'awpf/skins/' );

	}

	/**
	 * awpf_install
	 *
	 * Actions perform on activation of plugin
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function awpf_install() {}

	/**
	 * awpf_uninstall
	 *
	 * Actions perform on deactivation of plugin
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function awpf_uninstall() {}

}

/**
 * awpf
 *
 * The main function responsible for returning the one true awpf Instance
 *
 * @since	1.0
 * @param	N/A
 * @return	(object)
 */
function awpf() {

	global $awpf;

	if( ! isset($awpf) ) {

		$awpf = new awpf();

		$awpf->initialize();

	}

	// return
	return $awpf;

}

// initialize
awpf();

endif; // class_exists check