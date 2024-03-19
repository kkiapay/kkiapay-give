<?php

/**
 * Plugin Name: KKiapay For Give 
 * Plugin URI: https://wordpress.org/plugins/kkiapay-for-give
 * Description: KKiapay Give est une exension permettant de recevoir des dons par mobile money, carte de crédit et compte bancaire en toute sécurité
 * Author: Kkipay Developer Team ❤️
 * Author URI: https://kkiapay.me/
 * License: GPLv3
 * Version: 0.1.0
 * Requires at least: 6.0
 * Tested up to: 6.4.3
 * WC requires at least: 6.0
 * WC tested up to: 6.4.3
 * Text Domain: kkiapay-give
 * Domain Path: /languages
 */


// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('GIVEWP_KKIAPAY_VERSION', '0.1.0');

function invalid_version_notice()
{

?>
    <div class="error notice">
        <p><?php echo 'Veuillez installez une version de give superieur ou egale a la 2.5.2' ?></p>
    </div>
<?php
}


require_once(ABSPATH . '/wp-content/plugins/give/give.php');

//@Todo
require_once(plugin_dir_path(__FILE__) . 'includes/admin/kkiapay-give-admin.php');
/**
 * Load tanslation files
 */
function kkiapay_give_load_plugin_textdomain()
{
    load_plugin_textdomain('kkiapay-give', false, basename(dirname(__FILE__)) . '/languages/');
}

add_action('init', 'init_plugin');



function init_plugin()
{

    $filename = 'give/give.php';
    $path = plugin_dir_path(__DIR__) . $filename;
    $plugin_information = get_plugin_data($path);

    // Make sure Give is active
    if (!in_array($filename, apply_filters('active_plugins', get_option('active_plugins'))))
        return;

    // Make the installed Give version is more than 2.5.1 is active
    if (version_compare($plugin_information['Version'], '2.5.2', '>=') == false) {
        add_action('admin_notices', 'invalid_version_notice');
        return;
    }

    require_once(plugin_dir_path(__FILE__) . 'includes/class-give-kkiapay.php');
    add_action('plugins_loaded', 'kkiapay_give_load_plugin_textdomain');
    new Kkiapay_Give();
}
