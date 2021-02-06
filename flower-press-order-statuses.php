<?php
/**
 * Plugin Name: FlowerPress Woo Order Statuses
 * Plugin URI: https://mk-rom.myjino.ru/
 * Author: Roman Makarov
 * Author URI: https://mk-rom.myjino.ru/
 * Description: FlowerPress Woo Order Statuses
 * Version: 0.1.0
 * License: GPL2 0r Later
 * License URL: http://www.gnu.org/licenses/gpl-2.0.txt
 * text-domain: flowerpress-patterns
*/

add_action('plugins_loaded', 'wan_load_textdomain');

function wan_load_textdomain() {

	load_plugin_textdomain( 'flowerpress-patterns', false, dirname( plugin_basename(__FILE__) ) . '/lang/' );

}

add_action( 'init', 'register_my_order_statuses' );

function register_my_order_statuses() {

    register_post_status( 'wc-ready-for-shipping', array(
            'label' => _x( 'Ready for shipping', 'Order Status', 'flowerpress-patterns' ),
            'public' => true,
            'exclude_from_search' => false,
            'show_in_all_admin_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop( 'Ready for shipping <span class="count">(%s)</span>', 'Ready for shipping <span class="count">(%s)</span>', 'flowerpress-patterns' )
        )
    );

}

add_filter( 'wc_order_statuses', 'my_order_statuses' );

function my_order_statuses( $order_statuses ){

    $order_statuses['wc-ready-for-shipping'] = _x( 'Ready for shipping', 'Order Status', 'flowerpress-patterns' );
    return $order_statuses;

}

add_filter( 'bulk_actions-edit-shop_order', 'my_register_bulk_action' ); // edit-shop_order is the screen ID of the orders page

function my_register_bulk_action( $bulk_actions ) {

	$bulk_actions['mark_awaiting_shipment'] = 'Mark awaiting shipment'; // <option value="mark_awaiting_shipment">Mark awaiting shipment</option>
	return $bulk_actions;

}

/*
 * Bulk action handler
 * Make sure that "action name" in the hook is the same like the option value from the above function
 */
add_action( 'admin_action_mark_awaiting_shipment', 'my_bulk_process_custom_status' ); // admin_action_{action name}

function my_bulk_process_custom_status() {

	// if an array with order IDs is not presented, exit the function
	if( !isset( $_REQUEST['post'] ) && !is_array( $_REQUEST['post'] ) )
		return;

	foreach( $_REQUEST['post'] as $order_id ) {

		$order = new WC_Order( $order_id );
// 		$order_note = 'That\'s what happened by bulk edit:';
		$order->update_status( 'wc-status-name', $order_note, true ); // "misha-shipment" is the order status name (do not use wc-misha-shipment)

	}

	// of course using add_query_arg() is not required, you can build your URL inline
	$location = add_query_arg( array(
    		'post_type' => 'shop_order',
		'marked_awaiting_shipment' => 1, // markED_awaiting_shipment=1 is just the $_GET variable for notices
		'changed' => count( $_REQUEST['post'] ), // number of changed orders
		'ids' => join( $_REQUEST['post'], ',' ),
		'post_status' => 'all'
	), 'edit.php' );

	wp_redirect( admin_url( $location ) );
	exit;

}

/*
 * Notices
 */
add_action('admin_notices', 'my_custom_order_status_notices');

function my_custom_order_status_notices() {

	global $pagenow, $typenow;

	if( $typenow == 'shop_order'
	 && $pagenow == 'edit.php'
	 && isset( $_REQUEST['marked_awaiting_shipment'] )
	 && $_REQUEST['marked_awaiting_shipment'] == 1
	 && isset( $_REQUEST['changed'] ) ) {

		$message = sprintf( _n( 'Order status changed.', '%s order statuses changed.', $_REQUEST['changed'] ), number_format_i18n( $_REQUEST['changed'] ) );
		echo "<div class=\"updated\"><p>{$message}</p></div>";

	}

}
