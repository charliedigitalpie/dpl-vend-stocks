<?php

namespace Dpl_Vend_Stocks;

class Settings {

    public function __construct(){

        add_action('admin_menu',array($this, 'dpl_vend_stocks_settings_page'));
        add_action('admin_enqueue_scripts',array($this, 'dpl_vend_stocks_settings_css'));

        add_action('dpl_vend_stocks_connection_status',function(){            

            $dpl_vend_stocks_access_token = new Access_Token;

            if ( $dpl_vend_stocks_access_token->dpl_vend_stocks_is_connected() ) {

                if(isset($_GET['code'])){
                    return;
                }

                echo __("<div class=\"dpl-vend-stocks-connected-info notice notice-success settings-error is-dismissible\">
                            <p>You are connected to your Lightspeed Retail account.</p>
                        </div>",
                        'dpl-vend-stocks');
            }
         });
         
    }

    public function dpl_vend_stocks_settings_page(){

        add_menu_page(
            'Dpl Vend Stocks Settings',
            'Dpl Vend Stocks',
            'manage_options',
            'dpl-vend-stocks-settings',
            array($this, 'dpl_vend_stocks_settings_render_admin_page')
        );
    }

    public function dpl_vend_stocks_settings_render_admin_page(){

        $dpl_vend_stocks_client_id = !empty($_POST['dpl_vend_stocks_client_id']) ? $_POST['dpl_vend_stocks_client_id'] : "";
        $dpl_vend_stocks_client_secret = !empty($_POST['dpl_vend_stocks_client_secret']) ? $_POST['dpl_vend_stocks_client_secret'] : "";
        $dpl_vend_stocks_state = isset($_POST['dpl_vend_stocks_state']) ? $_POST['dpl_vend_stocks_state'] : "";
        $dpl_vend_stocks_redirect_uri = isset($_POST['dpl_vend_stocks_redirect_uri']) ? $_POST['dpl_vend_stocks_redirect_uri'] : "";

        if ( isset($_POST['submit']) ) {
            if ( !empty($dpl_vend_stocks_client_id) && !empty($dpl_vend_stocks_client_secret) && !empty($dpl_vend_stocks_state) ) {
    
                // Set options
                update_option('dpl_vend_stocks_client_id',$dpl_vend_stocks_client_id);
                update_option('dpl_vend_stocks_client_secret',$dpl_vend_stocks_client_secret);
                update_option('dpl_vend_stocks_state',$dpl_vend_stocks_state);
                update_option('dpl_vend_stocks_redirect_uri',$dpl_vend_stocks_redirect_uri);
    
                // Redirect for approval
                wp_redirect("https://secure.vendhq.com/connect?response_type=code&client_id=".$dpl_vend_stocks_client_id."&redirect_uri=".$dpl_vend_stocks_redirect_uri."&state=".$dpl_vend_stocks_state);
                exit();
            }        
        }

        ?>
        <div class="wrap dpl-vend-stocks-settings-wrapper">
            <h2>Dpl Vend Stocks Settings</h2>
            <?php do_action('dpl_vend_stocks_display_auth_result'); ?>
            <?php do_action('dpl_vend_stocks_connection_status'); ?>
            <form method="post">
                <p><span>Client ID:</span><input type="text" class="dpl_vend_stocks_input_settings" name="dpl_vend_stocks_client_id" placeholder="Enter your client id" value="<?php echo get_option('dpl_vend_stocks_client_id',false) ? get_option('dpl_vend_stocks_client_id',false) : "";?>"/></p>
                <p><span>Client Secret:</span><input type="password" class="dpl_vend_stocks_input_settings" name="dpl_vend_stocks_client_secret" placeholder="Enter your client secret" value="<?php echo get_option('dpl_vend_stocks_client_secret',false) ? get_option('dpl_vend_stocks_client_secret',false) : "";?>"/></p>
                <p style="display:none;"><input type="text" class="dpl_vend_stocks_input_settings" name="dpl_vend_stocks_redirect_uri" value="<?php echo admin_url('admin.php?page=dpl-vend-stocks-settings');?>" /></p>
                <p style="display:none;"><input type="text" class="dpl_vend_stocks_input_settings" name="dpl_vend_stocks_state" value="<?php self::dpl_vend_stocks_settings_state();?>" /></p>
                <p><input type="submit" name="submit" class="button button-primary" value="Save & Connect" /></p>
            </form>
        </div>
        <?php

    }

    private static function dpl_vend_stocks_settings_state(){

        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';

        for ($i = 0; $i < 80; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        echo $randomString;
    }

    public static function dpl_vend_stocks_settings_css(){

        if ( isset($_GET['page']) && $_GET['page'] != "dpl-vend-stocks-settings" ) {
            return;        
        }
        
        wp_register_style( 'dpl_vend_stocks_input_settings_css', false );
        wp_enqueue_style( 'dpl_vend_stocks_input_settings_css' );
        wp_add_inline_style( 'dpl_vend_stocks_input_settings_css',
           '.dpl_vend_stocks_input_settings{
              max-width: 500px;
              width: 100%;
           }.dpl-vend-stocks-settings-wrapper form span{
            max-width:100px;
            width:100%;
            display:inline-block;
           }',0 );
        
     }
}