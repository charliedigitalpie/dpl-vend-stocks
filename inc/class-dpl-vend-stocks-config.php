<?php
namespace Dpl_Vend_Stocks;

class Config {

    private static $dpl_vend_stocks_table_name = "dpl_vend_stocks_tbl";

    function __construct() {
        
    }

    public function dpl_vend_stocks_create_db_table(){

        // Create table
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        
        $table_name = $wpdb->prefix . 'dpl_vend_stocks_tbl';

        $sql = "CREATE TABLE IF NOT EXISTS " . $table_name . " (
            id int(11) NOT NULL AUTO_INCREMENT,
            product_id int(11) NOT NULL,
            product_vend_id VARCHAR(100) NOT NULL,
            outlet_id VARCHAR(100) NOT NULL,
            outlet_name VARCHAR(100) NOT NULL,
            stocks int(11) NOT NULL,
            stocks_as_of datetime NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";
    
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public static function dpl_vend_stocks_cron_callback(){

        error_log('Cron job triggered at ' . current_time('mysql'));

        $dpl_vend_stocks_product = new Product;

        $pages = $dpl_vend_stocks_product->dpl_vend_stocks_get_pages();

        if ( $pages > 0 ) {

            // Run through pages
            for($idx=1;$idx<=$pages;$idx++) {

                $page = $idx;
                
                $dpl_vend_stocks_product->dpl_vend_stocks_get_all_inventory($page);

                // Log if iteration is working
                error_log("Loop page number ".$page);

                // Apply some delay
                sleep(10);
            }
        } else {

            // If one page only
            $dpl_vend_stocks_product->dpl_vend_stocks_get_all_inventory();
        }
        
        
    }

    public function dpl_vend_stocks_clear_stocks_db_table(){

        global $wpdb;

        $db_table_name = $wpdb->prefix . self::$dpl_vend_stocks_table_name; 

        $wpdb->delete($db_table_name);
    }
}