<?php

/**
 * Plugin Name: DPL Vend Stocks
 * Description: DPL Vend Stocks shows stock status of products from different showrooms.
 * Version: 1.0
 * Text Domain: dpl-vend-stocks
 * Domain Path: /languages
 * Author: DigitalPie
 * Author URI: https://www.digitalpie.co.nz
 */


 defined( 'ABSPATH' ) || exit;
 
 // Define constants
 define( 'DPL_VEND_STOCKS_VERSION','1.0' );
 define( 'DPL_VEND_STOCKS_PATH', realpath( plugin_dir_path( __FILE__ ) ) . '/' );

 // Include custom functions
 require_once 'functions.php';

 // Add custom cron Schedule
 add_filter( 'cron_schedules', 'dpl_vend_stocks_add_custom_cron_schedule',10,1);

 // Get all valid cron schedules
//$schedules = wp_get_schedules();

// Output the schedules
/**echo '<pre>';
print_r($schedules);
echo '</pre>';
die();**/

if(isset($_GET['fortestinghere'])){
    echo get_option('dpl_vend_stocks_access_token',false)."<br>";
    echo get_option('dpl_vend_stocks_token_type',false)."<br>";
    echo get_option('dpl_vend_stocks_token_expires',false)."<br>";
    echo get_option('dpl_vend_stocks_token_expires_in',false)."<br>";
    echo get_option('dpl_vend_stocks_token_refresh_token',false)."<br>";
    echo get_option('dpl_vend_stocks_domain_prefix',false)."<br>"; 
    echo get_option('dpl_vend_stocks_access_token_expiry_date',false);
    die();
}

 
 // Include & Instantiate Settings class
 require_once 'inc/class-dpl-vend-stocks-settings.php';
 $dpl_vend_stocks_settings = new Dpl_Vend_Stocks\Settings;
 
 // Include & Instantiate Authorization class
 require_once 'inc/class-dpl-vend-stocks-authorization-code.php';
 $dpl_vend_stocks_authorization_code = new Dpl_Vend_Stocks\Authorization_Code;
 
 // Inclulde & Instantiate Access Token class
 require_once 'inc/class-dpl-vend-stocks-access-token.php';
 $dpl_vend_stocks_access_token = new Dpl_Vend_Stocks\Access_Token;

 // Include & Instantiate Product class
 require_once 'inc/class-dpl-vend-stocks-product.php';
 $dpl_vend_stocks_product = new Dpl_Vend_Stocks\Product;
 //$dpl_vend_stocks_product->dpl_vend_stocks_get_all_inventory(); // run for emergency testing
 
 // Include & Instantiate Config class
 require_once 'inc/class-dpl-vend-stocks-config.php';
 $dpl_vend_stocks_config = new Dpl_Vend_Stocks\Config;
 // $dpl_vend_stocks_config->dpl_vend_stocks_cron_callback(); // Just for testing multiple pages in cron
 
 // Exec custom cron schedule
 add_action( 'dpl_vend_stocks_cron_hook', array('Dpl_Vend_Stocks\Config','dpl_vend_stocks_cron_callback'));

  // This block of code will only executes when user is approving the app to connect to the retail account
 $dpl_vend_stocks_authorization_code->dpl_vend_stocks_get_auth_response();

 if ( $dpl_vend_stocks_authorization_code->dpl_vend_stocks_auth_has_return_error() ) {

    // Display message if connection is unsuccessful
    add_action('dpl_vend_stocks_display_auth_result', function(){
        echo __('Access denied! There was an error connecting to your app.','dpl-vend-stocks');
    });

 } else {

    if ($dpl_vend_stocks_authorization_code->dpl_vend_stocks_auth_has_valid_code()) {

        // Display message if connection is successful
        add_action('dpl_vend_stocks_display_auth_result', function(){
            echo __('Congratulations! You have successfully connected your app to your Lightspeed Retail account!','dpl-vend-stocks');
        });
        
        // Request access token        
        $dpl_vend_stocks_access_token->dpl_vend_stocks_request_access_token();
        
        // Get initial inventory record from the API & save them into the database
        $dpl_vend_stocks_product->dpl_vend_stocks_get_all_inventory();
    }    

 }
 
 // This checks if token expired
 if( $dpl_vend_stocks_access_token->dpl_vend_stocks_token_is_expired() ) {

    // Request new token
    $dpl_vend_stocks_access_token->dpl_vend_stocks_curl_request_new_token();
 }
 
// Activate plugin
register_activation_hook(__FILE__, function(){    

    // Instantiate Config class
    $dpl_vend_stocks_config = new Dpl_Vend_Stocks\Config;

    // Create table
    $dpl_vend_stocks_config->dpl_vend_stocks_create_db_table();    

    // Run cron dpl_vend_stocks_cron_hook
    if (! wp_next_scheduled ( 'dpl_vend_stocks_cron_hook' )){
        wp_schedule_event(time(), 'hourly', 'dpl_vend_stocks_cron_hook');
    }        
    
});

// Deactivate plugin
register_deactivation_hook(__FILE__,function(){

    //Clear cron schedule
    wp_clear_scheduled_hook( 'dpl_vend_stocks_cron_hook' );
});