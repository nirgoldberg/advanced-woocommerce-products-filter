<?php
/**
 * AWPF - admin widgets filter, actions, variables and includes
 *
 * @author		Nir Goldberg
 * @package		admin
 * @version		1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class awpf_widgets {

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
		add_action( 'admin_print_styles-widgets.php', array( $this, 'awpf_widgets_style' ) );

	}

	/**
	 * awpf_widgets_style
	 *
	 * This function will add specific style to AWPF widgets in WP admin
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function awpf_widgets_style() {
		
		// exit if no widget_style
		if( ! awpf_get_setting('widget_style') )
			return;

		echo <<<EOF
			<style type="text/css">
				div.widget[id*=awpf_widget] .widget-title h3 {
					color: #2191bf;
				}
			</style>
EOF;

	}

}

// initialize
new awpf_widgets();