<?php
/*
    Plugin Name: OlalaWeb - WooCommerce Category Prices
    Plugin Script: ola-wccp.php
    Plugin URI: http://olalaweb.com/plugins/
    Description: Display prices on WooCommerce Category Archive page. 
	Author: OlalaWeb 
    Donate Link: http://olalaweb.com/donate/
    License: GPL    
    Version: 1.0
    Author URI: http://olalaweb.com/
    Text Domain: olalaweb
    Domain Path: languages/
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

/* ---------------------------------------------------------------------------------- 
TODO
   ---------------------------------------------------------------------------------- */
// REUSE THE PRICE FORMAT IN -> DISPLAY FUNCTION
// ADD SETTING SCREEN TO SELECT A SEPARATOR ETC... FOR THE PRICES DISPLAY SETTINGS


/* ---------------------------------------------------------------------------------- 
GLOBAL STUFF
   ---------------------------------------------------------------------------------- */
define('OLA_WCCP_PLUGIN_URL', plugin_dir_url(__FILE__ ));
define('OLA_WCCP_PLUGIN_DIR', plugin_dir_path(__FILE__ ));
// define('OLA_WCCP_SETTINGS_URL', 'ola-wccp-settings.php') ;

// Load the admin UI
//require_once OLA_WCCP_PLUGIN_DIR . OLA_WCCP_SETTINGS_URL;


/* ---------------------------------------------------------------------------------- 
FUNCTIONS
   ---------------------------------------------------------------------------------- */

/**
 * Check if WooCommerce is active
 **/
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	// GET ALL CATEGORIES
	function wccp_set_categories_prices() {
		$taxonomy     = 'product_cat';
		$orderby      = 'name';  
		$show_count   = 0;
		$pad_counts   = 0; 
		$hierarchical = 1;
		$title        = '';  
		$empty        = 0;
		$args = array(
		  'taxonomy'     => $taxonomy,
		  'orderby'      => $orderby,
		  'show_count'   => $show_count,
		  'pad_counts'   => $pad_counts,
		  'hierarchical' => $hierarchical,
		  'title_li'     => $title,
		  'hide_empty'   => $empty
		);
		$all_categories = get_categories( $args );
	
		if  ( $all_categories )  { 
			foreach ( $all_categories as $category ) { 
	//			print '<h1>CAT : </h1>' . $category->name;
				$category_id = $category->term_id;
				$all_products = get_posts(array(
					'post_type' => 'product',
					'tax_query' => array(
						array(
						'taxonomy' => 'product_cat',
						'field' => 'term_id',
						'terms' => $category_id)
					)
				));
	
				$prices = array(); // Prepare the Prices array
				foreach ( $all_products as $product ) { 
					$sale = get_post_meta( $product->ID, '_sale_price', true);
					$price = get_post_meta( $product->ID, '_regular_price', true);
					if ( $sale ) {
						$prices[] = $sale;	
					} elseif ( $price ) {
						$prices[] = $price;
					}
				} // END FOREACH $products
	
				$min = 0;
				$max = 0;					
				if ( $prices ) {
	//				print '<h1>PRICES : </h1>' . print_r($prices);
					$min = min(array_values($prices));
					$max = max(array_values($prices));
	//				print '<h1>PRICES for '. $category_id .': </h1>' . $min . ' - ' . $max;
				}
				update_woocommerce_term_meta ( $category_id, 'min_price', $min); 
				update_woocommerce_term_meta ( $category_id, 'max_price', $max); 		
			} // END FOREACH $all_categories
		} // END IF PRICES
	}
	add_action('init', 'wccp_set_categories_prices');
	
	
	// OUTPUT THE CATEGORY PRICE 
	function wccp_display_categories_prices($category) {
		$before = '<span class="price">';
		$after = '</span>';
		$separator 	= get_option('ola_wccp_separator', '-'); 
		$currency 	= get_woocommerce_currency_symbol();
		// @TODO reuse the Price Format set by WooCommerce
		// $format 	= get_woocommerce_price_format();
		
		$category_id = $category->term_id;
	
		$min = get_woocommerce_term_meta ( $category_id, 'min_price' , true);
		$max = get_woocommerce_term_meta ( $category_id, 'max_price' , true);
		if ( $min == $max && $min > 0 ) {
			// @TODO
			// echo $before . $format . $after;
			echo $before . $min . $currency . $after;
			$schema = 1;
		} elseif ( $min != $max ) {
			// @TODO
			// echo $before . $formatmin. $separator . $formatmax . $after;		
			echo $before . $min . $currency . $separator . $max . $currency . $after;	
			$schema = 2;
		} else { 
			echo $before . __('Free', 'olalaweb') . $after;
			$schema = 0;
		}
	
		if ( $schema = 1 ) {	echo
		'<div itemprop="offers" itemscope itemtype="http://schema.org/Offer">
			<meta itemprop="price" content="'.$min.'" />
			<meta itemprop="priceCurrency" content="'.$currency.'" />
		</div>
		';
		} elseif ($schema = 2) {	echo
		'<div itemprop="offers" itemscope itemtype="http://schema.org/Offer">
			<div itemprop="offers" itemscope itemtype="http://schema.org/AggregateOffer" style="display:none;">
				<div itemprop="lowPrice">'.$min.'</div>
				<div itemprop="highPrice">'.$max.'</div> 
			</div>    
			<meta itemprop="priceCurrency" content="'.$currency.'" />
		</div>
		';		
		}
	}
	
	// DISPLAY THE PRICE AFTER CATEGORY LOADED
	add_action( 'woocommerce_after_subcategory', 'wccp_display_categories_prices' );
}