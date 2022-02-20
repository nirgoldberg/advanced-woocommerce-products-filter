<?php
/**
 * loader
 *
 * Display AWPF widget loader indication
 *
 * Override this template by copying it to yourtheme/awpf/templates/loader.php
 *
 * @author		Nir Goldberg
 * @package		templates
 * @version		1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

?>

<div class="awpf-loader">

	<img src="<?php echo awpf_get_dir('assets/images/ajax-loader.gif'); ?>" width="16" height="16" />

</div>