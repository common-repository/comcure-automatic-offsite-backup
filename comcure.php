<?php

/**
 * @package Comcure
 * @version 1.0.2
 */
/*
  Plugin Name: Comcure Automatic Offsite Backup
  Plugin URI: https://www.comcure.com/wordpress-plugin.html
  Description: Daily offsite website and database backups.
  Version: 1.0.2
  Author: Comcure Team
  Author URI: http://www.comcure.com
 */

if (!function_exists('add_action')) {
    echo 'Hi there!  I\'m just a plugin, not much I can do when called directly.';
    exit;
}

define('COMCURE_VERSION', '1.0.2');
define('COMCURE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRODUCTION', true);

if (is_admin()) {
    if (!extension_loaded('curl')) {
        echo "cURL extension is not available on your server.";
        exit;
    }
    require_once dirname(__FILE__) . "/classes/Comcure.php";
    require_once dirname(__FILE__) . "/classes/Curl.php";
    require_once dirname(__FILE__) . '/admin.php';
}
