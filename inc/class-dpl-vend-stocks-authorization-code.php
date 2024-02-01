<?php

namespace Dpl_Vend_Stocks;

class Authorization_Code {

    public function dpl_vend_stocks_get_auth_response(){
        
        if  (!isset($_GET['code'])) {
            return;
        }

        $dpl_vend_stocks_code = isset($_GET['code']) ? $_GET['code'] : "";
        $dpl_vend_stocks_domain_prefix = isset($_GET['domain_prefix']) ? $_GET['domain_prefix'] : "";

        update_option('dpl_vend_stocks_code',$dpl_vend_stocks_code);
        update_option('dpl_vend_stocks_domain_prefix',$dpl_vend_stocks_domain_prefix);

    }

    public function dpl_vend_stocks_auth_has_return_error(){
        return isset($_GET['error']) ? true : false;
    }

    public function dpl_vend_stocks_auth_has_valid_code(){
        return isset($_GET['code']) ? true : false;
    }

}