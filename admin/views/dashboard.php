<?php
/**
 * AWPF - admin dashboard HTML content
 *
 * @author		Nir Goldberg
 * @package		admin/views
 * @version		1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

// extract args
extract( $args );

?>

<div class="wrap about-wrap awpf-wrap">

	<h1><?php _e("Welcome to Advanced WooCommerce Products Filter", 'awpf'); ?> <?php echo $version; ?></h1>
	<div class="about-text"><?php printf( __("Thank you for installing AWPF %s! We hope you like it.", 'awpf'), $version); ?></div>

</div>