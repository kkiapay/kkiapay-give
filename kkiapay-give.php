<?php
/**
 * Plugin Name: KKiapay-Give 
 * Plugin URI: https://wordpress.org/plugins/kkiapay-give-plugin
 * Description: KKiapay-Give est une exension permettant de recevoir des dons
 * Author: Kkipay Developer Team ❤️
 * Author URI: https://kkiapay.me/
 * License: GPLv2
 * Version: 1.0.0
 * Requires at least: 4.4
 * Tested up to: 5.0
 * WC requires at least: 2.6
 * Text Domain: kkiapay-give
 * Domain Path: /languages
*/


include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
require_once(plugin_dir_path(__DIR__) . 'give-kkiapay/vendor/autoload.php');

function invalid_version_notice() {
    ?>
    <div class="error notice">
        <p><?= 'Veuillez installez une version de give superieur ou egale a la 2.5.2' ?></p>
    </div>
    <?php
}


require_once(ABSPATH . '/wp-content/plugins/give/give.php');

//@Todo
require_once(plugin_dir_path(__FILE__) . 'include/admin/kkiapay-give-admin.php');
/**
 * Load tanslation files
 */
function kkiapay_give_load_plugin_textdomain() {
    load_plugin_textdomain( 'kkiapay-give', FALSE, basename( dirname( __FILE__ ) ) . '/languages/' );
}


add_action('init','init_plugin');



function init_plugin(){
    $filename='give/give.php';
    $path=plugin_dir_path(__DIR__).$filename;
    $plugin_information = get_plugin_data($path);
    if (is_plugin_active('give/give.php') && version_compare($plugin_information['Version'], '2.5.2', '>=')==true) {
        require_once(plugin_dir_path(__FILE__) . 'include/kkiapay-give-class.php');
        add_action( 'plugins_loaded', 'kkiapay_give_load_plugin_textdomain' );
        new Kkiapay_Give();
    }
    else {
        add_action( 'admin_notices', 'invalid_version_notice' );
    }

}
