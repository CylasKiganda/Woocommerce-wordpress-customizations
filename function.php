<?php

add_action( 'wp_enqueue_scripts', 'theme_child_css', 1001 );

// Load CSS
function theme_child_css() {
	wp_deregister_style( 'styles-child' );
	wp_register_style( 'styles-child', esc_url( get_stylesheet_directory_uri() ) . '/style.css',array(),'2.2.5' );
	wp_enqueue_style( 'styles-child' );

	if ( is_rtl() ) {
		wp_deregister_style( 'styles-child-rtl' );
		wp_register_style( 'styles-child-rtl', esc_url( get_stylesheet_directory_uri() ) . '/style_rtl.css' );
		wp_enqueue_style( 'styles-child-rtl' );
	}
}

function header_tweaks_script() {
    $current_language_code = apply_filters( 'wpml_current_language', null );
    if($current_language_code == 'en') { 
        $load_file = 'header_tweaks_script-en.js';
    }
    elseif($current_language_code == 'tr') { 
        $load_file = 'header_tweaks_script-tr.js';
    }
    else { 
        $load_file = 'header_tweaks_script-ja.js';
    }
    
    if(is_checkout()){
        wp_enqueue_script( 'custom-delivery', get_stylesheet_directory_uri() . '/js/'.'customdelivery.js', array( 'jquery', ),'1.4.15',true ); 
        wp_enqueue_script('jquery-ui-mask', get_stylesheet_directory_uri() . '/js/'.'mask.js', array( 'jquery' ),'1.0',true );

    }
     
wp_enqueue_script( 'header-customtweak', get_stylesheet_directory_uri() . '/js/'.$load_file, array( 'jquery', ),'1.3.12',true );

}
add_action( 'wp_enqueue_scripts', 'header_tweaks_script', 99999  );




add_action( 'template_redirect', 'define_default_payment_gateway' );
function define_default_payment_gateway(){
    if( is_checkout() && ! is_wc_endpoint_url() ) {
        
        
        $default_payment_id = 'PFS_gateway_pick';

        WC()->session->set( 'chosen_payment_method', $default_payment_id );
    }
}

       
add_filter( 'woocommerce_available_payment_gateways', 'payment_gateways_based_on_chosen_shipping_method' );
function payment_gateways_based_on_chosen_shipping_method( $gateways ) {
    $pickup_shipping_method ='rates123:9d707f1f_pfs'; 
    $local = 'rates123:b9026db2_local'; 
    $ups = 'rates123:403f7d07_ups';
    global $woocommerce;
    if(is_checkout()){
        $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
    $chosen_shipping = $chosen_methods[0];

    if ($chosen_shipping == $pickup_shipping_method) 
    {
        unset( $gateways['cod'] );
    }
    else if($chosen_shipping ==$local ){
        unset( $gateways['PFS_gateway_pick'] );
    }
    else if($chosen_shipping ==$ups ){
        unset( $gateways['PFS_gateway_pick'] );
    }
    }
    
    return $gateways;
}




add_action( 'woocommerce_package_rates','show_hide_shipping_methods', 10, 2 );
function show_hide_shipping_methods( $rates, $package ) {
    $payment_method  = 'cod';

    $chosen_payment_method = WC()->session->get('chosen_payment_method');

    if( $payment_method == $chosen_payment_method ){
    	  foreach($rates as $key => $rate ) {
			if ( $rates[$key]->label === 'local' ) {
				unset($rates[$key]);
			}
		}
    }
    return $rates;
}


 
//  ---------custom delivery-checkout field------------------------------------------------
add_action( 'woocommerce_after_shipping_rate', 'checkout_shipping_additional_field', 20, 2 );
function checkout_shipping_additional_field( $method, $index )
{
    if( $method->get_id() == 'tree_table_rate:9d707f1f_local' ){
        echo '<br>
        <label class="lab localpt hidden">Local delivery date: </label>
        <input class="localpt inp hidden " name="localpickup"  type="text" maxlength="10" style="
        border: solid 1px lightgrey; width:150px; height:25px; padding-left:5px;
    " placeholder="____yy__mm__dd" >';
    }
}

add_action('woocommerce_checkout_update_order_meta',  'custom_delivery_checkout_field_update_order_meta' );

function custom_delivery_checkout_field_update_order_meta( $order_id ) {
    
            if ($_POST['localpickup'] ) {
             update_post_meta( $order_id, 'localpickup', esc_attr($_POST['localpickup']));
             }
}

add_action('woocommerce_checkout_process', 'localpickupvalidator');

function localpickupvalidator() {
    $pickup_shipping_method ='rates123:9d707f1f_local'; 
            global $woocommerce;
            $chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
            $chosen_shipping = $chosen_methods[0];
        
            if (($chosen_shipping == $pickup_shipping_method) && !$_POST['localpickup'])
            {
              wc_add_notice( __( 'Please fill in the local pickup date','woocommerce' ), 'error' );
                
            }
}
add_action('woocommerce_admin_order_data_after_billing_address', 'wps_checkout_field_display_admin_order_meta' , 10, 1);
function wps_checkout_field_display_admin_order_meta($order){
                if(get_post_meta( $order->id, 'localpickup', true )){
            	echo '<p><strong>'.__('Pickup On Site Date','woocommerce').':</strong> ' . get_post_meta( $order->id, 'localpickup', true ) . '</p>';
                }
            }
            
add_action( 'woocommerce_email_order_meta', 'add_pickup_field' );

function add_pickup_field($order){
                if(get_post_meta( $order->id, 'localpickup', true )){
            	echo '<p><strong>'.__('Pickup On Site Date','woocommerce').':</strong> ' . get_post_meta( $order->id, 'localpickup', true ) . '</p>';
                }
            }



add_action( 'woocommerce_package_rates','shipping_method_names', 10, 2 );

function shipping_method_names( $rates, $package ) {
  
  
    	  foreach($rates as $key => $rate ) {

			if ( $key == 'rates123:ups' ) {
			    if(ICL_LANGUAGE_CODE=='ln'){
			        $rates[$key]->label = '
                    Kyusa';
			    }
			    else if(ICL_LANGUAGE_CODE=='en'){
			        $rates[$key]->label = 'UPS Transport';
			    }
			    else if(ICL_LANGUAGE_CODE=='tr'){
			        $rates[$key]->label = 'UPS teslimati';
			    }
			    
			}
			if ( $key == 'rates123:9d707f1f_local' ) {
			    if(ICL_LANGUAGE_CODE=='ln'){
			        $rates[$key]->label = '
                    Laba';
			    }
			    else if(ICL_LANGUAGE_CODE=='en'){
			        $rates[$key]->label = 'Local Transport';
			    }
			    else if(ICL_LANGUAGE_CODE=='tr'){
			        $rates[$key]->label = 'Yerel teslimati';
			    }
			    
			}
		}
    
    return $rates;
}




























