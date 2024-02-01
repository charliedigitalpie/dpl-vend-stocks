<?php

namespace Dpl_Vend_Stocks;

class Access_Token {

    private static $_endpoint_url;

    public function __construct(){

        $domain_prefix = get_option('dpl_vend_stocks_domain_prefix',false);

        self::$_endpoint_url = 'https://'.$domain_prefix.'.vendhq.com/api/1.0/token';        
        
    }   

    public function dpl_vend_stocks_request_access_token(){

        // Checkpoint : if by any chance the user reloads the settings page with code parameter in the url and already has a valid token
        if ( !$this->dpl_vend_stocks_token_is_expired() && isset( $_GET['code'] ) ) {
            return;
        }

        // Get option details of the Authorization Code
        $dpl_vend_stocks_redirect_uri = get_option('dpl_vend_stocks_redirect_uri',false);
        $dpl_vend_stocks_code = get_option('dpl_vend_stocks_code',false);
        $dpl_vend_stocks_client_id = get_option('dpl_vend_stocks_client_id',false);
        $dpl_vend_stocks_client_secret = get_option('dpl_vend_stocks_client_secret',false);

        if ( empty( $dpl_vend_stocks_code ) ) {
            return;
        }

        $url = self::$_endpoint_url;        

        // Data to be sent in the POST request
        $post_data = array(
            'code' => $dpl_vend_stocks_code,
            'client_id' => $dpl_vend_stocks_client_id,
            'client_secret' => $dpl_vend_stocks_client_secret,
            'grant_type' => 'authorization_code',
            'redirect_uri' => $dpl_vend_stocks_redirect_uri
        );

        // Initialize cURL session
        $ch = curl_init($url);

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, 1); // Set to POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); // Set POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response instead of outputting it

        // Execute cURL session and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);             
        }

        // Close cURL session
        curl_close($ch);

        // get the response
        $array_data = json_decode($response, true);

        // update token details
        $this->dpl_vend_stocks_update_token_details($array_data);

        // set expiry date at the end
        $this->dpl_vend_stocks_token_set_expiry_date();       
        
    }

    public function dpl_vend_stocks_update_token_details($array_data){

        $access_token = !empty($array_data['access_token']) ? $array_data['access_token'] : "";
        $token_type = !empty($array_data['token_type']) ? $array_data['token_type'] : "";
        $expires = !empty($array_data['expires']) ? $array_data['expires'] : "";
        $expires_in = !empty($array_data['expires_in']) ? $array_data['expires_in'] : "";
        $refresh_token = !empty($array_data['refresh_token']) ? $array_data['refresh_token'] : "";
        $domain_prefix = !empty($array_data['domain_prefix']) ? $array_data['domain_prefix'] : "";

        if ( empty($access_token) ) {
            return;
        }

        update_option('dpl_vend_stocks_access_token',$access_token);
        update_option('dpl_vend_stocks_token_type',$token_type);
        update_option('dpl_vend_stocks_token_expires',$expires);
        update_option('dpl_vend_stocks_token_expires_in',$expires_in);
        update_option('dpl_vend_stocks_token_refresh_token',$refresh_token);
        update_option('dpl_vend_stocks_domain_prefix',$domain_prefix); 
    }

    public function dpl_vend_stocks_curl_request_new_token(){

        $url = self::$_endpoint_url;

        $dpl_vend_stocks_token_refresh_token = get_option('dpl_vend_stocks_token_refresh_token',false);
        $dpl_vend_stocks_client_id = get_option('dpl_vend_stocks_client_id',false);
        $dpl_vend_stocks_client_secret = get_option('dpl_vend_stocks_client_secret',false);

        // Data to be sent in the POST request
        $post_data = array(
            'refresh_token' => $dpl_vend_stocks_token_refresh_token,
            'client_id' => $dpl_vend_stocks_client_id,
            'client_secret' => $dpl_vend_stocks_client_secret,
            'grant_type' => 'refresh_token'
        );

        // Initialize cURL session
        $ch = curl_init($url);        

        // Set cURL options
        curl_setopt($ch, CURLOPT_POST, 1); // Set to POST method
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data); // Set POST data
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response instead of outputting it

        // Execute cURL session and get the response
        $response = curl_exec($ch);

        // Check for cURL errors
        if (curl_errno($ch)) {
            echo 'cURL Error: ' . curl_error($ch);             
        }
        
        // Close cURL session
        curl_close($ch);

        // get the response
        $array_data = json_decode($response, true);

        // update token details
        $this->dpl_vend_stocks_update_token_details($array_data);

        // set expiry date at the end
        $this->dpl_vend_stocks_token_set_expiry_date();

    }

    public function dpl_vend_stocks_token_is_expired(){

        $current_token_expiry_date = get_option('dpl_vend_stocks_access_token_expiry_date',false);
        $date_now = date('Y-m-d H:i:s');
        
        if( $current_token_expiry_date < $date_now ) {
            return true;
        }
        return false;
    }

    public function dpl_vend_stocks_token_set_expiry_date(){

        $dpl_vend_stocks_token_expires = get_option('dpl_vend_stocks_token_expires',false);

        if( !empty($dpl_vend_stocks_token_expires) ) {

            $epoch = $dpl_vend_stocks_token_expires;
            $expire = date( 'Y-m-d H:i:s',$epoch );
            update_option('dpl_vend_stocks_access_token_expiry_date',$expire);
        }
    }

    public function dpl_vend_stocks_is_connected(){

        if( !$this->dpl_vend_stocks_token_set_expiry_date() && !empty(get_option('dpl_vend_stocks_access_token',false)) ) {
            return true;
        }
        return false;
    }

}