<?php
/**
 * Plugin Name:       Ammar WC Smart Search
 * Plugin URI:        https://www.ammaraslam.com/
 * Description:       Ajax Search Plugin For WooCommerce
 * Version:           1.1
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Ammar Aslam
 * Author URI:        https://www.ammaraslam.com/
 * License:           GPL v2
 * Text Domain:       ammar-wc-smart-search
 * Domain Path:       /languages
 */

function ammar_wc_smart_search()
{

    ob_start(); ?>

<div id="product-search">
    <input type="text" id="product-search-input" class="product-search-input" placeholder="Search here...">
    <div id="product-search-results"></div>
</div>

<?php
       
    return ob_get_clean();

}
add_shortcode('ammar_wc_smart_search', 'ammar_wc_smart_search');
// Register the shortcode and enqueue JavaScript
function register_ajax_product_search()
{
    
    wp_enqueue_script('ajax-search-js', plugin_dir_url(__FILE__). '/js/ajax-search.js', array('jquery'), '1.0', true);
    wp_enqueue_style('ajax-search-css', plugin_dir_url(__FILE__). '/css/ajax-search.css', array(), '1.0');
    wp_localize_script('ajax-search-js', 'ajaxsearch', array('ajaxurl' => admin_url('admin-ajax.php')));
}


add_action('wp_enqueue_scripts', 'register_ajax_product_search');



// Function to handle the AJAX search
function ajax_product_search()
{

    // check_ajax_referer('ajax-nonce', 'security');

    $search_query = isset($_POST['search_query']) ? sanitize_text_field(wp_unslash($_POST['search_query'])) : ''; // Get the search query from the AJAX request
    global $wpdb;

    $query = $wpdb->prepare("SELECT ID, `post_title` FROM $wpdb->posts WHERE post_type = 'product' AND post_status = 'publish' AND post_title LIKE %s", "%$search_query%");

    $results = $wpdb->get_results($query);
                
    // print_r($results); check in console
    // error_log("SQL Query: " . $query);
    // Array to store product data including title, permalink, and image
    $products_with_images = array();

    foreach ($results as $product) {
        $product_id = $product->ID;
        $product_permalink = get_permalink($product_id);

        // Get the product object to retrieve additional data
        $product_data = wc_get_product($product_id);

        // Get the product image
        $product_image = $product_data->get_image();

        // Get the product price
        $product_price = $product_data->get_price_html();

        // Categories
        $terms = get_the_terms($product_id, 'product_cat');
        $category = '';

        if (!empty($terms) && !is_wp_error($terms)) {
            $category = $terms[0]->name; // first category
        }


        // Create an array with product data
        $product_info = array(
            'title' => $product->post_title,
            'permalink' => $product_permalink,
            'image' => $product_image,
            'price' => $product_price,
            'category'  => $category

        );

        // Add the product data to the results array
        $products_with_images[] = $product_info;
    }



    // Format and return the results as JSON
    wp_send_json($products_with_images);

}
add_action('wp_ajax_product_search', 'ajax_product_search');
add_action('wp_ajax_nopriv_product_search', 'ajax_product_search');


?>