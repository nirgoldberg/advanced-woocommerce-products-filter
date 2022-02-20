<?php
/**
 * AWPF - functions
 *
 * @author 		Nir Goldberg
 * @package 	includes
 * @version     1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * awpf_filter_products
 *
 * This function rebuild the products filter and products grid as part of AJAX call after select change event in categories menu
 *
 * @since		1.0
 * @param		$_POST
 * @return		N/A
 */
function awpf_filter_products() {

	// get data
	$taxonomy		= ( isset($_POST['taxonomy'])	&& $_POST['taxonomy'] )		? $_POST['taxonomy']	: '';
	$term_ids		= ( isset($_POST['term_ids'])	&& $_POST['term_ids'] )		? $_POST['term_ids']	: '';
	$wpml_lang		= ( isset($_POST['wpml_lang'])	&& $_POST['wpml_lang'] )	? $_POST['wpml_lang']	: '';
	$tax_list		= ( isset($_POST['tax_list'])	&& $_POST['tax_list'] )		? $_POST['tax_list']	: '';

	if ( ! $taxonomy || ! $term_ids ) {
		// die
		die();
	}

	// set AWPF_Widget_Front attributes
	awpf_widget_front()->set_attribute( 'taxonomy',		$taxonomy );
	awpf_widget_front()->set_attribute( 'term_ids',		$term_ids );
	awpf_widget_front()->set_attribute( 'wpml_lang',	$wpml_lang );
	awpf_widget_front()->set_attribute( 'tax_list',		$tax_list );

	// rebuild AWPF_Widget_Front
	awpf_widget_front()->rebuild();

}

/**
 * awpf_get_terms (for future use)
 *
 * Transient version for get_terms()
 *
 * @since		1.0
 * @param		$taxonomy (string) the taxonomy to retrieve terms from
 * @param		$args (array)
 * @return		(array) array of term objects or an empty array if no terms were found
 */
function awpf_get_terms( $taxonomy, $args ) {

	// get attributes
	$wpml_lang = awpf_widget_front()->get_attribute('wpml_lang');

	$output = array();

	if ( ! isset($taxonomy) )
		return $output;

	$transient_key		= 'AWPF-' . md5( serialize($taxonomy) . serialize($args) . ( $wpml_lang ? serialize($wpml_lang) : '' ) );
	$transient			= get_transient( $transient_key );

	$last_updated_key	= 'AWPF-' . substr($taxonomy, 0, 32) . ( $wpml_lang ? '-' . $wpml_lang : '' ) . '-terms-updated';
	$last_updated		= get_transient( $last_updated_key );

	if ( isset($transient['data']) && isset($last_updated) && $last_updated < $transient['time'] )
		// return data from transient
		return $transient['data'];

	// transient isn't valid or not exist
	if (!$last_updated)
		set_transient( $last_updated_key, time() );
		
	$output = get_terms($taxonomy, $args);
	$data = array( 'time' => time(), 'data' => $output );
	set_transient( $transient_key, $data );

	// return
	return $output;

}

/**
 * awpf_wp_query (for future use)
 *
 * Transient version for WP_Query
 *
 * @since		1.0
 * @param		$args (array) WP_Query arguments
 * @param		$name (string) referenced transient - used to check validity against query transient
 * @return		(array) array of post objects or an empty array if no posts were found
 */
function awpf_wp_query( $args, $name ) {

	$output = array();

	if ( ! isset($args) || ! isset($name) )
		return $output;

	$transient_key		= 'AWPF-' . md5( serialize($args) . serialize($name) );
	$transient			= get_transient( $transient_key );
	$last_updated_key	= 'AWPF-' . $name . '-wp-query-updated';
	$last_updated		= get_transient( $last_updated_key );

	if ( isset($transient['data']) && isset($last_updated) && $last_updated < $transient['time'] )
		// return data from transient
		return $transient['data'];

	// transient isn't valid or not exist
	if (!$last_updated)
		set_transient( $last_updated_key, time() );

	global $post;

	$query = new WP_Query($args);

	if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post();
		$output[] = $post;
	endwhile; endif; wp_reset_postdata();

	$data = array( 'time' => time(), 'data' => $output );
	set_transient( $transient_key, $data );

	// return
	return $output;

}