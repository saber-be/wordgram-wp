<?php


namespace WordgramPlugin;


use WordgramPlugin\Admin\Admin;

final class WordgramPlugin {
	private static $instance = null;

	public static function instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	private function __construct() {
		$this->init_hooks();
	}

	private function init_hooks() {
		register_activation_hook( WORDGRAM_PLUGIN_FILE, [ WordgramPluginInstall::class, 'activate' ] );
		register_uninstall_hook( WORDGRAM_PLUGIN_FILE, [ WordgramPluginInstall::class, 'uninstall' ] );
		Admin::init_hooks();
	}
}