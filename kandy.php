<?php
/**
 * Plugin Name: kandy
 * Plugin URI: https://github.com/kodeplusdev/kandy-wordpress
 * Description: Kandy for wordpress.
 * Version: 1.4
 * Text Domain: kandy
 * Author: KodePlus
 * Author URI: https://github.com/kodeplusdev
 * License: GPL2
 */
$pluginURL = is_ssl() ? str_replace("http://", "https://", WP_PLUGIN_URL) : WP_PLUGIN_URL;
define("KANDY_PLUGIN_VERSION", "1.4");
define("KANDY_PLUGIN_PREFIX", "kandy");
define("KANDY_PLUGIN_URL", $pluginURL . "/" . plugin_basename(dirname(__FILE__)));
define('KANDY_PLUGIN_DIR', dirname(__FILE__));
define('KANDY_API_BASE_URL', 'https://api.kandy.io/v1.1/');
define('KANDY_JS_URL', "https://kandy-portal.s3.amazonaws.com/public/javascript/kandy/1.1.4/kandy.js");
define('KANDY_FCS_URL', "https://kandy-portal.s3.amazonaws.com/public/javascript/fcs/3.0.0/fcs.js");
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
 * Kandy Installer
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

        update_option( 'kandy_db_version', $kandyDbVersion );
    }
}
function kandy_uninstall(){
    delete_option( "kandy_db_version" );
    //drop a custom db table
    global $wpdb;
    $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}kandy_users" );

    delete_option( "kandy_db_version" );
}
