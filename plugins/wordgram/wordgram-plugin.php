<?php
/**
 * Plugin Name: Wordgram Plugin
 * Plugin URI: https://wordpress.org/plugins/wordgram
 * Description: WooCommerce integration for Wordgram.
 * Version: 1.0.0
 * Author: Wordgram.com
 * Author URI: http://wordgram.com/
 * Text Domain: wordgram
 * Domain Path: /languages
 * License: GPL v3 or later.
 *
 * WC requires at least: 4.0.0
 *
 */

use WordgramPlugin\Admin\Admin;
use WordgramPlugin\WordgramPlugin;

defined( 'ABSPATH' ) || exit;

define( 'WORDGRAM_PLUGIN_FILE', __FILE__ );

if( function_exists('getenv_docker') ) {
	define( 'WORDGRAM_SERVICE_URL', getenv_docker('WORDGRAM_SERVICE_URL', 'https://z4m.ir') );
} else {
	define( 'WORDGRAM_SERVICE_URL', 'https://z4m.ir');
}

require_once __DIR__ . '/vendor/autoload.php';

if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	WordgramPlugin::instance();
} else {
	add_action( 'admin_notices', [ Admin::class, 'render_missing_or_outdated_wc_notice' ] );
}