<?php
/*
Plugin Name: Connections Hub
Plugin URI: 
Description: 
Version: 2.0.0
Author: Crystal Barton
Author URI: http://www.crystalbarton.com
*/


require_once( dirname(__FILE__).'/config.php' );
require_once( CONNECTIONS_PLUGIN_PATH.'/util.php' );
require_once( CONNECTIONS_PLUGIN_PATH.'/custom-post-type/connection.php' );
require_once( CONNECTIONS_PLUGIN_PATH.'/random-spotlight-connections-widget.php' );

add_filter( 'query_vars', array('ConnectionsHub_Main', 'query_vars') );
add_action( 'parse_request', array('ConnectionsHub_Main', 'parse_request') );

if( is_admin() )
{
	add_action( 'admin_init', array('ConnectionsHub_Main', 'setup_actions') );
	add_action( 'admin_menu', array('ConnectionsHub_Main', 'setup_admin_pages') );
	
	add_action("wp_ajax_connections-synch", array('ConnectionsHub_Main', 'show_admin_ajax_page'));
}



/**
 * The main class for the "Connections Hub" plugin.
 */
class ConnectionsHub_Main
{

	/**
	 * Adds the main admin page to the admin menu.
	 */
	public static function setup_admin_pages()
	{
	    add_submenu_page(
	    	'edit.php?post_type=connection', 
	    	'Connections Import Page', 
	    	'Import',
	    	'administrator', 
	    	'connections-import-connections', 
	    	array('ConnectionsHub_Main', 'show_admin_page')
	    );

	    add_submenu_page(
	    	'edit.php?post_type=connection', 
	    	'Connections Synch Page', 
	    	'Synch',
	    	'administrator', 
	    	'connections-synch-connections', 
	    	array('ConnectionsHub_Main', 'show_admin_page')
	    );

	    add_submenu_page(
	    	'edit.php?post_type=connection', 
	    	'Connections Settings Page', 
	    	'Settings',
	    	'administrator', 
	    	'connections-settings', 
	    	array('ConnectionsHub_Main', 'show_admin_page')
	    );
	}


	/**
	 * Shows the admin page for the plugin.
	 */
	public static function show_admin_page()
	{
		require_once( CONNECTIONS_PLUGIN_PATH.'/admin-page.php' );
		ConnectionsHub_AdminPage::init();
		ConnectionsHub_AdminPage::show_page();
	}
	
	
	/**
	 * Processes AJAX requests from the plugin.
	 */
	public static function show_admin_ajax_page()
	{
		require_once( CONNECTIONS_PLUGIN_PATH.'/admin-ajax-page.php' );
		ConnectionsHub_AdminAjaxPage::init();
		ConnectionsHub_AdminAjaxPage::process();
		ConnectionsHub_AdminAjaxPage::output();
		exit();
	}


	/**
	 * Adds the needed JavaScript and CSS files needed for the plugin.
	 */	
	public static function setup_actions()
	{
		require_once( CONNECTIONS_PLUGIN_PATH.'/admin-page.php' );
		ConnectionsHub_AdminPage::init();
		ConnectionsHub_AdminPage::setup_actions();
	}
	
	
	
	/**
	 * Adds "synch-connections" to the list of parseable query variables.
	 */
	public static function query_vars( $query_vars )
	{
		$query_vars[] = 'synch-connections';
		return $query_vars;
	}


	/**
	 * Check for the plugin's tag and if found, then process the mobile post data
	 * from the Android device.
	 */
	public static function parse_request( &$wp )
	{
		global $wp;
		if( array_key_exists('synch-connections', $wp->query_vars) )
		{
			echo "\nSynching Connections...";
 			require_once( CONNECTIONS_PLUGIN_PATH . '/classes/synch-connection.php' );
 			ConnectionsHub_SynchConnection::synch_all_connections( true );
 			echo "done.\n\n";
			exit();
		}
		return;
	}

}

