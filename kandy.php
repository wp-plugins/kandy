<?php
/**
 * Plugin Name: Kandy
 * Plugin URI: https://github.com/Kandy-IO/kandy-wordpress
 * Description: Kandy Plugin is a full-service cloud platform that enables real-time communications for business applications.
 * Version: 2.2.1
 * Text Domain: kandy
 * Author: Kandy-IO
 * Author URI: https://github.com/Kandy-IO
 * License: GPL2
 */
$pluginURL = is_ssl() ? str_replace("http://", "https://", WP_PLUGIN_URL) : WP_PLUGIN_URL;
define("KANDY_PLUGIN_VERSION", "2.2.1");
define("KANDY_PLUGIN_PREFIX", "kandy");
define("KANDY_PLUGIN_URL", $pluginURL . "/" . plugin_basename(dirname(__FILE__)));
define('KANDY_PLUGIN_DIR', dirname(__FILE__));
define('KANDY_API_BASE_URL', 'https://api.kandy.io/v1.2/');
define('KANDY_JS_URL', site_url() . "/wp-content/plugins/kandy/js/kandy-2.2.1.js");
define('KANDY_FCS_URL', site_url() . "/wp-content/plugins/kandy/js/fcs-3.0.4.js");

define('KANDY_JQUERY', "https://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js");
define('KANDY_JQUERY_RELOAD', false);
define('KANDY_SSL_VERIFY', false);
define('KANDY_USER_TABLE', 'kandy_users');
define('KANDY_API_KEY', '');
define('KANDY_DOMAIN_SECRET_KEY', '');
define('KANDY_DOMAIN_NAME', '');

define('KANDY_VIDEO_WRAPPER_CLASS_DEFAULT', 'kandyVideoWrapper');
define('KANDY_VIDEO_STYLE_DEFAULT', 'width: 340px; height: 250px;background-color: darkslategray;');
define('KANDY_VIDEO_MY_TITLE_DEFAULT', 'me');
define('KANDY_VIDEO_THEIR_TITLE_DEFAULT', 'their');

define('KANDY_UN_ASSIGN_USER', 'kandy-un-assign-user');

define('KANDY_PSTN_TYPE', 'PSTN');

require_once dirname(__FILE__) . '/kandy-admin-class.php';
require_once dirname(__FILE__) . '/kandy-shortcode.php';
require_once dirname(__FILE__) . '/api/kandy-api-class.php';
if (is_admin()) {
    $kandy_admin = new KandyAdmin();
}
KandyShortcode::init();

//active plugin
register_activation_hook( __FILE__, 'kandy_install' );
//uninstall plugin
register_uninstall_hook( __FILE__, 'kandy_uninstall' );

/**
 * Kandy Install Hook.
 */
function kandy_install() {
    global $wpdb;
    $kandyDbVersion = KANDY_PLUGIN_VERSION;

    $table_name = $wpdb->prefix . 'kandy_users';
    $installed_ver = get_option( "kandy_db_version" );

    if ( $installed_ver != $kandyDbVersion ) {
        $sql = "CREATE TABLE IF NOT EXISTS `".$table_name."` (
                  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
                  `user_id` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `first_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `last_name` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `password` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `email` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `domain_name` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `api_key` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `api_secret` varchar(255) COLLATE utf8_unicode_ci NOT NULL,
                  `main_user_id` varchar(255) COLLATE utf8_unicode_ci DEFAULT NULL,
                  `created_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                  `updated_at` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00',
                  PRIMARY KEY (`id`)
                )";

        require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        dbDelta( $sql );
        delete_option( "kandy_fcs_url" );
        delete_option( "kandy_js_url" );
        update_option( 'kandy_db_version', $kandyDbVersion );
    }
}

/**
 * Kandy Uninstall Hook.
 */
function kandy_uninstall(){

    //drop a custom db table
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kandy_users" );

    delete_option( "kandy_db_version" );
    delete_option( "kandy_api_key" );
    delete_option( "kandy_domain_name" );
    delete_option( "kandy_domain_secret_key" );
    delete_option( "kandy_fcs_url" );
    delete_option( "kandy_jquery_reload" );
    delete_option( "kandy_js_url" );
}
