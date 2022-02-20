<?php
/**
 * AWPF Widget frontend
 *
 * @author		Nir Goldberg
 * @package		widgets/awpf
 * @version		1.0
 */

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

if ( ! class_exists('AWPF_Widget_Front') ) :

class AWPF_Widget_Front {

	private $attributes;

	/**
	 * __construct
	 *
	 * A dummy constructor to ensure AWPF_Widget_Front is only initialized once
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
	 * @param		$show_categories_menu (bool) show (true) / hide (false) categories menu
	 * @param		$show_price_filter (bool) show (true) / hide (false) price filter
	 * @param		$price_title (string) price filter title
	 * @param		$tax_list (array) array taxonomy filter titles and taxonomy names
	 * @return		N/A
	 */
	function initialize( $show_categories_menu, $show_price_filter, $price_title, $tax_list ) {

		// initiate attributes
		$this->attributes = array(
			// queried object
			'taxonomy'				=> get_query_var('taxonomy'),
			'term_ids'				=> array( get_queried_object_id() ),
			'wpml_lang'				=> function_exists('icl_object_id') ? ICL_LANGUAGE_CODE : '',

			// filter settings
			'show_categories_menu'	=> $show_categories_menu,
			'show_price_filter'		=> $show_price_filter,
			'price_title'			=> $price_title,
			'tax_list'				=> $tax_list,

			// price filter attributes
			'min_price'				=> null,
			'max_price'				=> null,
			'min_handle_price'		=> null,
			'max_handle_price'		=> null,

			// filter content
			'categories'			=> array(),
			'taxonomies'			=> array(),

			// filtered products data
			'products'				=> array()
		);

		// initiate categories attribute
		if ( $show_categories_menu ) {
			$this->init_categories();
		}

		// initiate taxonomies attribute
		if ( $tax_list ) {
			$this->init_taxonomies();
		}

		if ( ! $show_price_filter && ( ! $show_categories_menu || ! $this->attributes['categories'] ) && ! $this->attributes['taxonomies'] )
			return;
			
		// 1. initiate filter values
		// 2. initiate products attribute as an array of arrays (products and terms associated with each product)
		$this->init_products_filter_values();

		if ( ! $this->attributes['products'] )
			return;

		// display products filter
		$this->display_products_filter();

	}

	/**
	 * rebuild
	 *
	 * Rebuild AWPF
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	function rebuild() {

		// get attributes
		$tax_list = $this->get_attribute( 'tax_list' );

		// set attributes
		$this->set_attribute( 'min_price',			null );
		$this->set_attribute( 'max_price',			null );
		$this->set_attribute( 'min_handle_price',	null );
		$this->set_attribute( 'max_handle_price',	null );
		$this->set_attribute( 'taxonomies',			array() );
		$this->set_attribute( 'products',			array() );

		// initiate taxonomies attribute
		if ( $tax_list ) {
			$this->init_taxonomies();
		}

		// 1. initiate filter values
		// 2. initiate products attribute as an array of arrays (products and terms associated with each product)
		$this->init_products_filter_values();

		if ( ! $this->attributes['products'] ) {
			// die
			die();
		}

		// retrieve products HTML to update later in products grid
		$products_grid = $this->update_products_grid();

		// return results to AJAX script
		$results = array(
			'min_price'			=> $this->get_attribute( 'min_price' ),
			'max_price'			=> $this->get_attribute( 'max_price' ),
			'min_handle_price'	=> $this->get_attribute( 'min_handle_price' ),
			'max_handle_price'	=> $this->get_attribute( 'max_handle_price' ),
			'taxonomies'		=> $this->get_attribute( 'taxonomies' ),
			'products'			=> $this->get_attribute( 'products' ),
			'products_grid'		=> $products_grid
		);

		echo json_encode( $results );

		// die
		die();

	}

	/**
	 * get_attribute
	 *
	 * This function will return a value from the attributes array found in AWPF_Widget_Front object
	 *
	 * @since		1.0
	 * @param		$name (string) the attribute name to return
	 * @return		(mixed)
	 */
	function get_attribute( $name, $default = null ) {

		$arrtibute = awpf_maybe_get( $this->attributes, $name, $default );

		// return
		return $arrtibute;

	}

	/**
	 * set_attribute
	 *
	 * This function will update a value into the attributes array found in AWPF_Widget_Front object
	 *
	 * @since		1.0
	 * @param		$name (string) the attribute name to update
	 * @param		$value (mixed) the attribute value to update
	 * @return		N/A
	 */
	function set_attribute( $name, $value ) {

		$this->attributes[ $name ] = $value;

	}

	/**
	 * init_categories
	 *
	 * Initiate categories attribute as an array of arrays (product categories)
	 *
	 * categories structure:
	 * =====================
	 * $categories[ {category parent ID} ][ {category ID} ][0]	=> number of products associated with this category (including children)
	 * $categories[ {category parent ID} ][ {category ID} ][1]	=> whether this category is checked in subcategory filter [true / false]
	 * $categories[ {category parent ID} ][ {category ID} ][2]	=> whether this category is an ancestor of the current category [true / false]
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	private function init_categories() {

		// get attributes
		$taxonomy				= $this->get_attribute( 'taxonomy' );
		$term_ids				= $this->get_attribute( 'term_ids' );
		$show_categories_menu	= $this->get_attribute( 'show_categories_menu' );

		if ( ! $show_categories_menu )
			return;

		$categories = array();

		$args = array(
			'orderby'	=> 'term_order'
		);
		$terms = get_terms('product_cat', $args);

		if ($terms) {

			foreach ($terms as $term) {
				if ( ! array_key_exists($term->parent, $categories) ) {
					$categories[$term->parent] = array();
				}

				$categories[$term->parent][$term->term_id] = array(
					0 => $term->count,
					1 => $taxonomy == 'product_cat' && ( $term->term_id == $term_ids[0] ),
					2 => $taxonomy == 'product_cat' && ( $term->term_id == $term_ids[0] || cat_is_ancestor_of($term->term_id, $term_ids[0]) )
				);
			}

		}

		// initiate categories attribute
		$this->set_attribute( 'categories', $categories );

	}

	/**
	 * init_taxonomies
	 *
	 * Initiate taxonomies attribute as an array of arrays (taxonomies and terms data)
	 *
	 * taxonomies structure:
	 * =====================
	 * $taxonomies[ {taxonomy name} ][0]					=> taxonomy filter title
	 * $taxonomies[ {taxonomy name} ][1]					=> number of products associated with this taxonomy
	 * $taxonomies[ {taxonomy name} ][2][ {term ID} ][0]	=> number of products associated with this term
	 * $taxonomies[ {taxonomy name} ][2][ {term ID} ][1]	=> whether this term is checked in taxonomy filter [1 = true / 0 = false]
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	private function init_taxonomies() {

		// get attributes
		$tax_list = $this->get_attribute( 'tax_list' );

		if ( ! $tax_list )
			return;

		$taxonomies = array();

		foreach ($tax_list as $tax) {

			// get taxonomy terms
			$args = array(
				'orderby'	=> 'term_order'
			);
			$terms = get_terms($tax['name'], $args);
			
			if ($terms) {

				$taxonomies[ $tax['name'] ] = array(
					0 => $tax['title'],
					1 => 0,
					2 => array()
				);

				foreach ($terms as $term) {
					$taxonomies[ $tax['name'] ][2][$term->term_id] = array(
						0 => 0,
						1 => 0
					);
				}

			}

		}

		// initiate taxonomies attribute
		$this->set_attribute( 'taxonomies', $taxonomies );

	}

	/**
	 * init_products_filter_values
	 * 
	 * Initiate products filter values according to current taxonomy, term ID, price range and taxonomy terms
	 * 
	 * products structure:
	 * ===================
	 * $products[ {product ID} ][0]							=> product price
	 * $products[ {product ID} ][1]							=> whether this product is displayed according to filter state [1 = true / 0 = false]
	 * $products[ {product ID} ][2][ {taxonomy name} ]		=> array of taxonomy term_id's associated with this product
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	private function init_products_filter_values() {

		global $woocommerce;

		// get attributes
		$taxonomy			= $this->get_attribute( 'taxonomy' );
		$taxonomy_term_ids	= $this->get_attribute( 'term_ids' );

		// get all products related to taxonomy term ID
		$meta_query = $woocommerce->query->get_meta_query();
		
		$args = array(
			'post_type'			=> 'product',
			'post_status'		=> 'publish',
			'posts_per_page'	=> -1,
			'no_found_rows'		=> true,
			'orderby'			=> 'menu_order',
			'order'				=> 'ASC',
			'meta_query'		=> $meta_query
		);

		if ($taxonomy && $taxonomy_term_ids) {
			$args['tax_query']	= array(
				array(
					'taxonomy'	=> $taxonomy,
					'field'		=> 'id',
					'terms'		=> $taxonomy_term_ids
				)
			);
		}
		$query = new WP_Query($args);

		// fill in filter values and $products according to products meta data
		global $post;
		
		if ( $query->have_posts() ) : while ( $query->have_posts() ) : $query->the_post();

			$this->update_filter_by_product( $post->ID );

		endwhile; endif; wp_reset_postdata();

		// update price filter handles in first page load
		$this->set_attribute( 'min_handle_price', $this->get_attribute( 'min_price' ) );
		$this->set_attribute( 'max_handle_price', $this->get_attribute( 'max_price' ) );

	}

	/**
	 * update_filter_by_product
	 *
	 * Update products and taxonomies attributes by a single product meta data
	 *
	 * @since		1.0
	 * @param		$product_id (int) product ID
	 * @return		N/A
	 */
	private function update_filter_by_product( $product_id ) {

		// get attributes
		$min_price		= $this->get_attribute( 'min_price' );
		$max_price		= $this->get_attribute( 'max_price' );
		$taxonomies		= $this->get_attribute( 'taxonomies' );
		$products		= $this->get_attribute( 'products' );

		// get product price
		$_product = wc_get_product($product_id);

		if ( defined('DOING_AJAX') && DOING_AJAX && class_exists('woocommerce_wpml') ) {
			// filter product price according to default currenct exchange rate
			// used in case of an AJAX call and active woocommerce wpml
			$price = round( apply_filters( 'wcml_raw_price_amount', $_product->get_price() ) );
		}
		else {
			$price = round( $_product->get_price() );
		}

		// initiate $products and update product price and product visibility
		$products[$product_id] = array(
			0 => $price,
			1 => 1
		);
		
		// update price filter
		if ( is_null($min_price) || is_null($max_price) ) {
			$min_price = $max_price = $price;
		} else {
			$min_price = min($price, $min_price);
			$max_price = max($price, $max_price);
		}

		// initiate $products before input taxonomies data
		$products[$product_id][2] = array();

		// update $taxonomies filter and $products taxonomies
		if ($taxonomies) {

			foreach ($taxonomies as $tax_name => &$tax_data) {

				// initiate $products before input a single taxonomy data
				$products[$product_id][2][$tax_name] = array();

				// get all particular taxonomy terms associated with this product 
				$p_terms = wp_get_post_terms($product_id, $tax_name);
				
				if ($p_terms) {

					// update $taxonomies counters
					// increment number of products associated with this taxonomy
					$tax_data[1]++;
					
					foreach ($p_terms as $p_term) {
						// store term ID in $products
						$products[$product_id][2][$tax_name][] = $p_term->term_id;

						// update $taxonomies counters
						// increment number of products associated with this term
						$tax_data[2][$p_term->term_id][0]++;
					}

				}

			}

		}

		// update attributes
		$this->set_attribute( 'min_price', $min_price );
		$this->set_attribute( 'max_price', $max_price );
		$this->set_attribute( 'taxonomies', $taxonomies );
		$this->set_attribute( 'products', $products );

	}

	/**
	 * display_products_filter
	 *
	 * Display products filter
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	private function display_products_filter() {

		// get attributes
		$wpml_lang				= $this->get_attribute( 'wpml_lang' );
		$show_categories_menu	= $this->get_attribute( 'show_categories_menu' );
		$show_price_filter		= $this->get_attribute( 'show_price_filter' );
		$tax_list				= $this->get_attribute( 'tax_list' );
		$min_price				= $this->get_attribute( 'min_price' );
		$max_price				= $this->get_attribute( 'max_price' );
		$min_handle_price		= $this->get_attribute( 'min_handle_price' );
		$max_handle_price		= $this->get_attribute( 'max_handle_price' );
		$categories				= $this->get_attribute( 'categories' );
		$taxonomies				= $this->get_attribute( 'taxonomies' );
		$products				= $this->get_attribute( 'products' );

		?>

		<script>
			_AWPF_products_filter_wpml_lang				= '<?php echo $wpml_lang; ?>';
			_AWPF_products_filter_show_categories_menu	=  <?php echo ( $show_categories_menu ) ? 1 : 0; ?>;
			_AWPF_products_filter_show_price_filter		=  <?php echo ( $show_price_filter ) ? 1 : 0; ?>;
			_AWPF_products_filter_tax_list				=  <?php echo json_encode( $tax_list ); ?>;
			_AWPF_products_filter_min_price				=  <?php echo $min_price; ?>;
			_AWPF_products_filter_max_price				=  <?php echo $max_price; ?>;
			_AWPF_products_filter_min_handle_price		=  <?php echo $min_handle_price; ?>;
			_AWPF_products_filter_max_handle_price		=  <?php echo $max_handle_price; ?>;
			_AWPF_products_filter_categories			=  <?php echo json_encode( $categories ); ?>;
			_AWPF_products_filter_taxonomies			=  <?php echo json_encode( $taxonomies ); ?>;
			_AWPF_products_filter_products				=  <?php echo json_encode( $products ); ?>;
			_AWPF_products_filter_currency				= '<?php echo html_entity_decode( get_woocommerce_currency_symbol() ); ?>';
			_AWPF_products_filter_ajaxurl				= '<?php echo ( $wpml_lang ) ? str_replace( "/$wpml_lang/", "/", admin_url( "admin-ajax.php" ) ) : admin_url( "admin-ajax.php" ); ?>';		// workaround for WPML bug
			_AWPF_products_filter_not_found				= '<?php echo "<p class=\"woocommerce-info\">" . __( "No products were found matching your selection.", "awpf" ) . "</p>"; ?>';
		</script>

		<?php
			wp_enqueue_style( 'jquery-ui' );
			wp_enqueue_script( 'jquery-ui' );
			wp_enqueue_script( 'jquery-ui-touch-punch' );
			wp_enqueue_script( 'awpf-products-filter' );
		?>

		<?php

		echo '<div class="widgetcontent">';

			/**
			 * awpf_before_filter_content hook
			 */
			do_action( 'awpf_before_filter_content' );

			// categories menu
			if ( $show_categories_menu && $categories ) {
				awpf_get_template_part( 'awpf-widget/awpf-widget', 'categories-menu' );
			}

			// price filter
			if ( $show_price_filter ) {
				awpf_get_template_part( 'awpf-widget/awpf-widget', 'price-filter' );
			}
			
			// taxonomy filters
			awpf_get_template_part( 'awpf-widget/awpf-widget', 'tax-filter' );

			/**
			 * awpf_after_filter_content hook
			 */
			do_action( 'awpf_after_filter_content' );

		echo '</div>';

	}

	/**
	 * display_categories_menu_items
	 *
	 * Display categories menu items
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		(string)
	 */
	function display_categories_menu_items() {

		// get attributes
		$categories = $this->get_attribute( 'categories' );

		if ( ! $categories )
			return;

		$output = '';

		foreach ( $categories[0] as $cat_id => $category ) {

			$output .= $this->product_categories_menu_item( $cat_id, $category, 0 );

		}

		// return
		return $output;

	}

	/**
	 * product_categories_menu_item
	 *
	 * Recursive function handles display of a single category menu item.
	 * The recursive part handles the children menu items (if there are any) of this parent category 
	 *
	 * @since		1.0
	 * @param		$cat_id (int) category ID
	 * @param		$category (array) holds array of a single category data
	 * @param		$depth (int) indicates current menu depth
	 * @return		(string)
	 */
	function product_categories_menu_item( $cat_id, $category, $depth ) {

		// get attributes
		$categories = $this->get_attribute( 'categories' );

		$has_children = array_key_exists( $cat_id, $categories );
		
		// set classes
		$classes = array('cat-' . $cat_id);

		if ( $has_children || $depth == 0 )
			$classes[] = 'has-children';

		if ( $category[2] )
			$classes[] = 'ancestor';

		if ( $category[2] && $depth == 0 )
			$classes[] = 'collapsed';

		$output = '<li class="' . implode(' ', $classes) . '">';

			if ( $has_children || $depth == 0 ) {

				// top level item and/or a parent item
				$output .=	'<a>' .
								'<span class="item-before"></span>' .
								'<span>' . get_cat_name( $cat_id ) . '</span> ' .
								'(<span class="count">' . $category[0] . '</span>)' .
							'</a>';

			}
			else {

				// low level item without children
				$output .=	'<input type="checkbox" name="product_cat-' . $cat_id . '" id="product_cat-' . $cat_id . '" value="product_cat-' . $cat_id . '"' . ( $category[1] ? ' checked' : '' ) . ' />' .
							'<label for="product_cat-' . $cat_id . '">' .
								'<span>' . get_cat_name( $cat_id ) . ' ' .
									'(<span class="count">' . $category[0] . '</span>)' .
								'</span>' .
							'</label>';

			}

			if ( $has_children || $depth == 0 ) {

				// start a subcategories menu
				$output .= '<ul class="children children-depth-' . $depth . '">';

					// display an "All" item as a first item in the subcategories menu
					$output .=	'<li class="cat-' . $cat_id . '-all">' .
									'<input type="checkbox" name="product_cat-' . $cat_id . '-all' . '" id="product_cat-' . $cat_id . '-all' . '" value="product_cat-' . $cat_id . '-all' . '"' . ( $category[1] ? ' checked' : '' ) . ' />' .
									'<label for="product_cat-' . $cat_id . '-all' . '">' .
										'<span>' . apply_filters( 'awpf_all_subcategories_title', __('All', 'awpf') ) . ' ' .
											'(<span class="count">' . $category[0] . '</span>)' .
										'</span>' .
									'</label>' .
								'</li>';

					// recursive call
					if ( $has_children ) {
						foreach ( $categories[$cat_id] as $sub_cat_id => $subcategory ) {
							$output .= $this->product_categories_menu_item( $sub_cat_id, $subcategory, $depth+1 );
						}
					}

				$output .= '</ul>';

			}

		$output .= '</li>';

		// return
		return $output;

	}

	/**
	 * display_tax_terms
	 *
	 * Display taxonomies terms
	 *
	 * @since		1.0
	 * @param		$tax_name (string) taxonomy name
	 * @param		$terms (array) array of taxonomy terms - view from $taxonomies attribute
	 * @return		(string)
	 */
	function display_tax_terms( $tax_name, $terms ) {

		if ( ! $tax_name || ! $terms )
			return;

		$output = '';

		foreach ( $terms as $term_id => $term_data ) {
			$term_name = get_term_by('id', $term_id, $tax_name)->name;

			$output .=	'<li ' . ( ! $term_data[0] ? 'style="display: none;"' : '' ) . '>' .
							'<input type="checkbox" name="' . $term_id . '" id="' . $term_id . '" value="' . $term_id . '" />' .
							'<label for="' . $term_id . '">' .
								'<span>' . $term_name . ' </span>' .
								'(<span class="count">' . $term_data[0] . '</span>)' .
							'</label>' .
						'</li>';

		}

		// return
		return $output;

	}

	/**
	 * update_products_grid
	 *
	 * Retrieve products HTML to update later in products grid
	 * Called by rebuild function as part of AJAX call
	 *
	 * @since		1.0
	 * @param		N/A
	 * @return		N/A
	 */
	private function update_products_grid() {

		// get attributes
		$products = $this->get_attribute( 'products' );

		ob_start();

		if ( $products ) {

			global $post, $product;

			foreach ($products as $p_id => $p_data) {

				$post = get_post($p_id);
				$product = wc_get_product($p_id);

				wc_get_template_part( 'content', 'product' );

			}

		}

		// return
		$products_grid = ob_get_clean();
		return $products_grid;

	}

}

/**
 * awpf_widget_front
 *
 * This function initialize AWPF_Widget_Front
 *
 * @since	1.0
 * @param	N/A
 * @return	(object)
 */
function awpf_widget_front() {

	global $awpf_widget_front;

	if( ! isset($awpf_widget_front) ) {

		$awpf_widget_front = new AWPF_Widget_Front();

	}

	// return
	return $awpf_widget_front;

}

// initialize
awpf_widget_front();

endif; // class_exists check