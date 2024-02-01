<?php

namespace Dpl_Vend_Stocks;

class Product {

    private static $_token;
    private static $_domain_prefix;
    private static $_endpoint_url;
    private static $dpl_vend_stocks_table_name = "dpl_vend_stocks_tbl";

    public function __construct(){

        add_action('woocommerce_after_add_to_cart_form',array($this,'dpl_vend_stocks_show_product_stocks_frontend'));

        self::$_token = get_option('dpl_vend_stocks_access_token',false) ? get_option('dpl_vend_stocks_access_token',false) : "";
        self::$_domain_prefix = get_option('dpl_vend_stocks_domain_prefix',false) ? get_option('dpl_vend_stocks_domain_prefix',false) : "";
        self::$_endpoint_url = !empty(self::$_domain_prefix) ? "https://".self::$_domain_prefix.".vendhq.com/api/products/" : "";
    }

    public function dpl_vend_stocks_pull_single_product_stocks_from_api($product){

        $product_id = $product->get_id();

        $product_vend_id = dpl_vend_stocks_get_product_vend_id_by_wc_prod_id($product_id);

        if( empty($product_vend_id) ) {
            return;
        }
        
        // Initialize cURL session
        $ch = curl_init(self::$_endpoint_url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . self::$_token,
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        // Process the response
        if ($response) {

            // Do something with the response (e.g., decode JSON, print, etc.)
            $array_data = json_decode($response);
            $product_vend_id = $array_data->products[0]->id;            
            $inventory_array = $array_data->products[0]->inventory;            
            
            if( !is_array($inventory_array) ) {
                return;
            }

            foreach($inventory_array as $stock) {
                $outlet_id = $stock->outlet_id;
                $outlet_name = $stock->outlet_name;
                $stock_count = $stock->count;
            }

            $return_data = array(

            );

        } else {

            // Handle the case where there was no response
            echo 'No response received.';
        }
    }

    public function dpl_vend_stocks_show_product_stocks_frontend(){  

        global $wpdb,$product;

        $product_id = $product->get_id();

        $db_table_name = $wpdb->prefix . self::$dpl_vend_stocks_table_name; 

        $sql = "SELECT * FROM `$db_table_name` WHERE product_id = %d";

        $query = $wpdb->prepare($sql,$product_id);     

        $results = $wpdb->get_results( $query, ARRAY_A );

        $li = "";

        foreach($results as $stock) {
            $li .= "<li>".
                        "<p>".$stock['outlet_name']." Showroom</p>".
                        "<p> Status: ".($stock['stocks'] > 0 ? "In stock" : "Out of stock")."</p>".
                    "</li>";
        }
        echo "<ul>";
        echo $li;
        echo "</ul>";
    }

    public function dpl_vend_stocks_save_product_stocks($product_id, $product_vend_id,$outlet_id,$outlet_name,$stock_count){
        
        global $wpdb;

        $db_table_name = $wpdb->prefix . self::$dpl_vend_stocks_table_name;        

        // Define your data
        $data_to_insert = array(
            'product_id' => $product_id,
            'product_vend_id' => $product_vend_id,
            'outlet_id' => $outlet_id,
            'outlet_name' => $outlet_name,
            'stocks' => $stock_count,
            'stocks_as_of' => date('Y-m-d H:i:s'),
        );
        $data_formats = array('%d','%s','%s','%s','%d','%s');
        $where = array('product_id' => $product_id,'outlet_id'=>$outlet_id);

        // Check if the data exists in the table
        $data_exists = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM $db_table_name WHERE product_id = %d AND outlet_id = %s", 
                $where['product_id'],
                $where['outlet_id']
            )
        );

        if ($data_exists) {
            $wpdb->update($db_table_name, $data_to_insert, $where, $data_formats);
        } else {
            $wpdb->insert($db_table_name, $data_to_insert, $data_formats);
        }
    }

    public function dpl_vend_stocks_get_all_inventory($page = ""){     
        
        if ( empty(self::$_endpoint_url) ) {
            return;
        }
        
        // Initialize cURL session
        $page_number_param = "";
        if ( $page != "" ) {
            $page_number_param = "?page=".$page;
        }

        $ch = curl_init(self::$_endpoint_url.$page_number_param);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . self::$_token,
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        $array_data = json_decode($response);
        $products_array = $array_data->products;
        
        foreach($products_array as $key=>$product) {

            $sku = $products_array[$key]->sku;
            $product_id = $this->dpl_vend_stocks_get_product_id_by_sku($sku);
            $product_vend_id = $products_array[$key]->id;

            if ( !empty($product_id) ) {
                foreach($products_array[$key]->inventory as $invt) {
                    $outlet_id = $invt->outlet_id;
                    $outlet_name = $invt->outlet_name;
                    $stock_count = $invt->count;
    
                    $this->dpl_vend_stocks_save_product_stocks(
                        $product_id, 
                        $product_vend_id,
                        $outlet_id,
                        $outlet_name,
                        $stock_count
                    );
                }
            }           
        }        
    }

    public function dpl_vend_stocks_get_pages(){

        if ( empty(self::$_endpoint_url) ) {
            return;
        }
        
        // Initialize cURL session
        $ch = curl_init(self::$_endpoint_url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . self::$_token,
        ]);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            echo 'Curl error: ' . curl_error($ch);
        }

        // Close cURL session
        curl_close($ch);

        $array_data = json_decode($response);
        $pages = $array_data->pagination->pages;

        return $pages > 0 ? $pages : 0;
    }

    public function dpl_vend_stocks_get_product_id_by_sku($sku){

        global $wpdb;

        $db_table_name = $wpdb->prefix . 'postmeta';

        $sql = "SELECT post_id FROM `$db_table_name` WHERE meta_key = '_sku' AND meta_value = '%s'";
        $q = $wpdb->prepare($sql,$sku);
        $results = $wpdb->get_results($q,ARRAY_A);

        return !empty($results[0]['post_id']) ? $results[0]['post_id'] : "";

    }

    public function dpl_vend_stocks_get_product_vend_id_by_wc_prod_id($product_id){

        global $wpdb;

        $db_table_name = $wpdb->prefix . self::$dpl_vend_stocks_table_name;

        $sql = "SELECT product_vend_id FROM `$db_table_name` WHERE product_id = %d";
        $q = $wpdb->prepare($sql,$sku);
        $results = $wpdb->get_results($q,ARRAY_A);

        return !empty($results[0]['product_vend_id']) ? $results[0]['product_vend_id'] : "";

    }

}