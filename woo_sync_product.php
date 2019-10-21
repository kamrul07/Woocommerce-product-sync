<?php

/**
 *
 * @link              https://psdtowpservice.com
 * @since             1.0.0
 * @package           Woo_sync_product
 *
 * @wordpress-plugin
 * Plugin Name:       Woo Sync Product Stock
 * Plugin URI:        https://psdtowpservice.com
 * Description:       A plugin to sync Woo commerce product quantity.
 * Version:           1.0.0
 * Author:            psdtowpservice.com
 * Author URI:        https://psdtowpservice.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo_sync_product
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}


// Display Fields
add_action('woocommerce_product_options_inventory_product_data', 'woo_sync_product_woocommerce_product_custom_fields');


function cus_all_products()    { 
          $product_list =array();
          $args = array(
                'post_type'=> array( 'product' ),
                'posts_per_page'=> -1,
            );

            $query = new WP_Query( $args );

            if ( $query->have_posts() ) {
                while ( $query->have_posts() ) {
                    $query->the_post();
                    $product_list [get_the_ID()] =get_the_title(); 
                }
            }
            wp_reset_postdata();
            return  $product_list;
            var_dump($product_list);
}


function woo_sync_product_woocommerce_product_custom_fields()
{

    global $woocommerce, $post;
    echo '<div class="product_custom_field" style="background:#eeeeee;padding:15px;">';
    echo '<h2>'.esc_attr( "Sync Product settings","woo_sync_product").'</h2>';
   
    echo "<script>

    jQuery(document).ready(function( $ ) {
    $('div.product_custom_field').addClass('menuitemshow') ;      
    $('#_manage_stock').change(function(){
        if($(this).is(':checked')) {
            $('div.product_custom_field').addClass('menuitemshow');
        } else {
            $('div.product_custom_field').removeClass('menuitemshow');
        }
    });   
    });
     </script>
    <style>
    .product_custom_field.menuitemshow{display:block!important}
    .product_custom_field{display:none!important}
    </style>
     ";


   $args = array( 
    'id'            => 'stock_enabled', 
    'wrapper_class' => 'show_if_simple', 
    'label'         => __('Enable product sync', 'woo_sync_product' ), 
    'description'   => __( 'Check me to enable product sync.', 'woo_sync_product' )     
    );
  woocommerce_wp_checkbox( $args );


  $select_field = array(
    'id' => 'syncd_product_id',
    'label' => __( 'Sync Product', 'woo_sync_product' ),
    'options' =>cus_all_products(),
    );
  woocommerce_wp_select($select_field);
  echo '</div>';
}


add_action( 'woocommerce_process_product_meta', 'woo_sync_product_woocom_save_general_proddata_custom_field' );

function woo_sync_product_woocom_save_general_proddata_custom_field( $post_id ) {

  $syncd_product_id = $_POST['syncd_product_id'];
  if( ! empty( $syncd_product_id ) ) {
     update_post_meta( $post_id, 'syncd_product_id', esc_attr( $syncd_product_id ) );
  }

   $stock_enabled = $_POST['stock_enabled'];
   update_post_meta( $post_id, 'stock_enabled', esc_attr( $stock_enabled ) );
}

add_action('save_post','save_post_callback');
function save_post_callback($post_id){
    global $post; 
    if ($post->post_type = 'product'){

    	    $main_product_qty =  get_post_meta($post_id, '_stock', true);
            $syncd_product_id = get_post_meta($post_id, 'syncd_product_id', true);  
            $synce_enable = get_post_meta($post_id, 'stock_enabled', true);

            if($synce_enable == "yes"){}
             update_post_meta( $syncd_product_id, '_stock', $main_product_qty );
             update_post_meta( $syncd_product_id, '_manage_stock', 'yes' );
    }   
}

add_action( 'woocommerce_thankyou', 'woo_sync_product_woo_sync_product_product_stock', 10, 1 );
function woo_sync_product_woo_sync_product_product_stock($order_id){
    $stock_updated = get_post_meta($order_id, 'stock_updated', true);
    if(empty($stock_updated)){
    update_post_meta($order_id, 'stock_updated', 'yes');    
    $order = wc_get_order( $order_id );
    $items = $order->get_items(); 
    foreach ( $items as $item_id => $item ) {
       $product_id = $item->get_variation_id() ? $item->get_variation_id() : $item->get_product_id();
       $product_qty =  $item->get_quantity();
       $synce_enable = get_post_meta($product_id, 'stock_enabled', true);  
       $syncd_product_id = get_post_meta($product_id, 'syncd_product_id', true);  
       $sync_product_qty =  get_post_meta( $syncd_product_id , '_stock', true);
       $main_product_qty =  get_post_meta($product_id, '_stock', true);
       $stocks_done = get_post_meta($product_id, "_manage_stock", true);
        if($synce_enable=="yes"){
         $update_qty=  $sync_product_qty - $product_qty;  
         $s_updated =  update_post_meta( $syncd_product_id, '_stock', $main_product_qty );
        }

      }
    }
}
