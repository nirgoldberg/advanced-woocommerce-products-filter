<?php
/**
 * AWPF - hooks
 *
 * @author 		Nir Goldberg
 * @package 	includes
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

add_action( 'wp_ajax_filter_products', 'awpf_filter_products' );
add_action( 'wp_ajax_nopriv_filter_products', 'awpf_filter_products' );