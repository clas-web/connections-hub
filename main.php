<?php
/*
Plugin Name: Connections Hub
Plugin URI: https://github.com/clas-web/connections-hub
Description: The Connections Hub connects people through their Connection's Connection Links.  A person's Connections post can be maintained manually or synched through a local WordPress site, remote WordPress site using the Connections Spoke plugin, or RSS feed.
Version: 2.8.0
Author: Crystal Barton
Author URI: https://www.linkedin.com/in/crystalbarton
GitHub Plugin URI: https://github.com/clas-web/connections-hub
*/


if( !defined('CONNECTIONS_HUB') ):

/**
 * The full title of the Connections Hub plugin.
 * @var  string
 */
define( 'CONNECTIONS_HUB', 'Connections Hub' );

/**
 * True if debug is active, otherwise False.
 * @var  bool
 */
define( 'CONNECTIONS_HUB_DEBUG', false );

/**
 * The path to the plugin.
 * @var  string
 */
define( 'CONNECTIONS_HUB_PLUGIN_PATH', __DIR__ );

/**
 * The url to the plugin.
 * @var  string
 */
define( 'CONNECTIONS_HUB_PLUGIN_URL', plugins_url('', __FILE__) );

/**
 * The version of the plugin.
 * @var  string
 */
define( 'CONNECTIONS_HUB_VERSION', '2.5.0' );

/**
 * The database version of the plugin.
 * @var  string
 */
define( 'CONNECTIONS_HUB_DB_VERSION', '1.0' );

/**
 * The database options key for the Connections Hub version.
 * @var  string
 */
define( 'CONNECTIONS_HUB_VERSION_OPTION', 'connections-hub-version' );

/**
 * The database options key for the Connections Hub database version.
 * @var  string
 */
define( 'CONNECTIONS_HUB_DB_VERSION_OPTION', 'connections-hub-db-version' );

/**
 * The database options key for the Connections Hub options.
 * @var  string
 */
define( 'CONNECTIONS_HUB_OPTIONS', 'connections-hub-options' );

/**
 * The full path to the log file used to log a synch.
 * @var  string
 */
define( 'CONNECTIONS_HUB_LOG_FILE', __DIR__.'/logs/'.date('Ymd-His').'.txt' );

endif;


register_activation_hook( __FILE__, 'conhub_activate_plugin' );
require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/functions.php' );
require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/model/model.php' );
require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/model/synch-model.php' );
require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/custom-post-type/connection.php' );
require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/random-spotlight/main.php' );
require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/search/main.php' );


add_filter( 'query_vars', 'conhub_query_vars' );
add_action( 'parse_request', 'conhub_parse_request' );
add_action( 'wp_enqueue_scripts', 'conhub_enqueue_scripts' );

if( is_admin() )
{
	add_action( 'wp_loaded', 'conhub_load' );
	add_action( 'admin_menu', 'conhub_update', 5 );
}


/**
 * Prevent activation if the Admin Page Library is not loaded or is not the correct version.
 */
if( !function_exists('conhub_activate_plugin') ):
function conhub_activate_plugin()
{
	if( !defined('APL') || !defined('APL_VERSION') )
	{
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			'The '.CONNECTIONS_HUB.' plugin requires the APL library.<br/>'.
			'<a href="'.admin_url('plugins.php?deactivate=true').'">Return to Plugins</a>'
		);
	}
	
	if( version_compare(APL_VERSION, '1.0') < 0 )
	{
		deactivate_plugins( plugin_basename( __FILE__ ) );
		wp_die(
			'The '.CONNECTIONS_HUB.' plugin requires version 1.0 or greater of the APL library.<br/>'.
			'<a href="'.admin_url('plugins.php?deactivate=true').'">Return to Plugins</a>'
		);
	}
}
endif;


/**
 * Setup the admin pages.
 */
if( !function_exists('conhub_load') ):
function conhub_load()
{
	require_once( __DIR__.'/admin-pages/require.php' );
	
	$conhub_pages = new APL_Handler( false );

	$conhub_pages->add_page( new ConnectionsHub_ImportConnectionsAdminPage, 'edit.php?post_type=connection' );
	$conhub_pages->add_page( new ConnectionsHub_ExportConnectionsAdminPage, 'edit.php?post_type=connection' );
	$conhub_pages->add_page( new ConnectionsHub_SynchConnectionsAdminPage, 'edit.php?post_type=connection' );
	$conhub_pages->add_page( new ConnectionsHub_SettingsAdminPage, 'edit.php?post_type=connection' );
	$conhub_pages->setup();

	if( $conhub_pages->controller )
	{
		add_action( 'admin_enqueue_scripts', 'conhub_admin_enqueue_scripts' );
	}
}
endif;


/**
 * Update the database if a version change.
 */
if( !function_exists('conhub_update') ):
function conhub_update()
{
	$version = get_option( CONNECTIONS_HUB_DB_VERSION_OPTION );
 	if( $version !== CONNECTIONS_HUB_DB_VERSION )
 	{
		// Put in changes to database here.
		// $model = ConnectionsHub_Model::get_instance();
	}
		
	update_option( CONNECTIONS_HUB_VERSION_OPTION, CONNECTIONS_HUB_VERSION );
	update_option( CONNECTIONS_HUB_DB_VERSION_OPTION, CONNECTIONS_HUB_DB_VERSION );
}
endif;


/**
 * Enqueue the admin CSS styles.
 */
if( !function_exists('conhub_admin_enqueue_scripts') ):
function conhub_admin_enqueue_scripts()
{
	wp_enqueue_style( 'connection-hub-main', plugins_url('admin-pages/styles/style.css', __FILE__) );
}
endif;


/**
 * Enqueue the frontend CSS styles.
 */
if( !function_exists('conhub_enqueue_scripts') ):
function conhub_enqueue_scripts()
{
	wp_enqueue_style( 'connection-hub-main', plugins_url('style.css', __FILE__) );
}
endif;


/**
 * Add filtering keys to the query vars.
 * @param  Array  $query_vars  The query vars.
 * @return  Array  The altered query vars.
 */
if( !function_exists('conhub_query_vars') ):
function conhub_query_vars( $query_vars )
{
	$query_vars[] = 'synch-connections';
	$query_vars[] = 'generate-connections-url';
	return $query_vars;
}
endif;


/**
 * Parse the request to search for filtering keys.
 * @param  WP  $wp  The WP object.
 */
if( !function_exists('conhub_parse_request') ):
function conhub_parse_request( &$wp )
{
	if( array_key_exists('synch-connections', $wp->query_vars) )
	{
		echo "\nSynching Connections...";
			require_once( CONNECTIONS_HUB_PLUGIN_PATH . '/classes/model/synch-model.php' );
		$synch_model = ConnectionsHub_SynchModel::get_instance();
			$synch_model->synch_all_connections( true );
			echo "done.\n\n";
		exit();
	}
	elseif( array_key_exists('generate-connections-url', $wp->query_vars) )
	{
		require_once( CONNECTIONS_HUB_PLUGIN_PATH . '/classes/custom-post-type/connection.php' );
		$id = intval( $wp->query_vars['generate-connections-url'] );
		echo get_permalink( $id );
		exit();
	}
}
endif;

