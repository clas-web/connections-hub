<?php
/*
Plugin Name: Connections Hub
Plugin URI: 
Description: 
Version: 2.5.0
Author: Crystal Barton
Author URI: http://www.crystalbarton.com
*/


if( !defined('CONNECTIONS_HUB') ):

define( 'CONNECTIONS_HUB', 'Connections Hub' );

define( 'CONNECTIONS_HUB_DEBUG', true );

define( 'CONNECTIONS_HUB_PLUGIN_PATH', dirname(__FILE__) );
define( 'CONNECTIONS_HUB_PLUGIN_URL', plugins_url('', __FILE__) );

define( 'CONNECTIONS_HUB_VERSION', '2.5.0' );
define( 'CONNECTIONS_HUB_DB_VERSION', '1.0' );

define( 'CONNECTIONS_HUB_VERSION_OPTION', 'connections-hub-version' );
define( 'CONNECTIONS_HUB_DB_VERSION_OPTION', 'connections-hub-db-version' );

define( 'CONNECTIONS_HUB_OPTIONS', 'connections-hub-options' );
define( 'CONNECTIONS_HUB_LOG_FILE', dirname(__FILE__).'/logs/'.date('Ymd-His').'.txt' );

endif;


register_activation_hook( __FILE__, array('ConnectionsHub_Main', 'activate_plugin') );


require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/model/model.php' );
require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/model/synch-model.php' );
require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/custom-post-type/connection.php' );
require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/random-spotlight/main.php' );


add_filter( 'query_vars', array('ConnectionsHub_Main', 'query_vars') );
add_action( 'parse_request', array('ConnectionsHub_Main', 'parse_request') );

add_action( 'wp_enqueue_scripts', array('ConnectionsHub_Main', 'enqueue_scripts') );

if( is_admin() )
{
	add_action( 'admin_enqueue_scripts', array('ConnectionsHub_Main', 'admin_enqueue_scripts') );
	add_action( 'wp_loaded', array('ConnectionsHub_Main', 'load') );
	add_action( 'admin_menu', array('ConnectionsHub_Main', 'update'), 5 );
}


/**
 * The main class for the "Connections Hub" plugin.
 */
class ConnectionsHub_Main
{

	/**
	 * 
	 */
	public static function activate_plugin()
	{
		if( !defined('APL') || !defined('APL_VERSION') )
		{
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( 'The '.CONNECTIONS_HUB.' plugin requires the APL library.' );
		}
		
		if( version_compare(APL_VERSION, '1.0') < 0 )
		{
			deactivate_plugins( plugin_basename( __FILE__ ) );
			wp_die( 'The '.CONNECTIONS_HUB.' plugin requires version 1.0 or greater of the APL library.' );
		}
	}
	
	
	/**
	 * 
	 */
	public static function load()
	{
		require_once( dirname(__FILE__).'/admin-pages/require.php' );
		
		// Site admin page.
		$connhub_pages = new APL_Handler( false );

		$connhub_pages->add_page( new ConnectionsHub_ImportConnectionsAdminPage, 'edit.php?post_type=connection' );
		$connhub_pages->add_page( new ConnectionsHub_ExportConnectionsAdminPage, 'edit.php?post_type=connection' );
		$connhub_pages->add_page( new ConnectionsHub_SynchConnectionsAdminPage, 'edit.php?post_type=connection' );
		$connhub_pages->add_page( new ConnectionsHub_SettingsAdminPage, 'edit.php?post_type=connection' );
		$connhub_pages->setup();
	}
	
	
	/**
	 * 
	 */
	public static function update()
	{
//		$version = get_option( CONNECTIONS_HUB_DB_VERSION_OPTION );
//  	if( $version !== CONNECTIONS_HUB_DB_VERSION )
//  	{
 			$model = ConnectionsHub_Model::get_instance();
//  			$model->create_tables();
//  	}
 		
 		update_option( CONNECTIONS_HUB_VERSION_OPTION, CONNECTIONS_HUB_VERSION );
 		update_option( CONNECTIONS_HUB_DB_VERSION_OPTION, CONNECTIONS_HUB_DB_VERSION );
	}
	
	
	/**
	 * 
	 */
	public static function admin_enqueue_scripts()
	{
		wp_enqueue_style( 'connection-hub-main', plugins_url('admin-pages/styles/style.css', __FILE__) );
	}
	
	
	/**
	 * 
	 */
	public static function enqueue_scripts()
	{
		wp_enqueue_style( 'connection-hub-main', plugins_url('style.css', __FILE__) );
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
 			require_once( CONNECTIONS_HUB_PLUGIN_PATH . '/classes/model/synch-model.php' );
			$synch_model = ConnectionsHub_SynchModel::get_instance();
 			$synch_model->synch_all_connections( true );
 			echo "done.\n\n";
			exit();
		}
		return;
	}

}

