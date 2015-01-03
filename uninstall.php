<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) { exit; }

// Make sure we're uninstalling
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	return false;
}

// Delete all the term_meta
delete_woocommerce_term_meta ('', 'min_price', '', true); // Delete all
delete_woocommerce_term_meta ('', 'max_price', '', true); // Delete all
?>