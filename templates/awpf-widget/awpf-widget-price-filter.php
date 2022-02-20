<?php
/**
 * awpf-widget-price-filter
 *
 * Display AWPF widget price filter
 *
 * Override this template by copying it to yourtheme/awpf/templates/awpf-widget-price-filter.php
 *
 * @author		Nir Goldberg
 * @package		templates/awpf-widget
 * @version		1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>

<div class="awpf-filter awpf-price-filter">

	<?php // filter title ?>
	<?php $price_title = awpf_widget_front()->get_attribute( 'price_title' ); ?>

	<?php if ( $price_title ) { ?>

		<div class="awpf-filter-title awpf-price-filter-title">
			<h3><?php echo $price_title; ?></h3>
		</div>

	<?php } ?>

	<?php // filter content ?>
	<div class="awpf-filter-content">

		<?php
			/**
			 * awpf_before_price_filter hook
			 */
			do_action( 'awpf_before_price_filter' );
		?>

		<input type="text" id="awpf-price-filter-amount" readonly>
		<div id="awpf-price-filter-slider"></div>

		<?php
			/**
			 * awpf_after_price_filter hook
			 */
			do_action( 'awpf_after_price_filter' );
		?>

	</div>

</div>