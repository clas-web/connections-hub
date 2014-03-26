<?php


/**
 * Processes, generates, and displays the plugin's admin page.
 */
class ConnectionsHub_AdminPage
{

	private static $_page;
	private static $_class;


	/**
	 * Default Constructor.
	 */	
	private function __construct() {}

	
	/**
	 * 
	 */	
	public static function init()
	{
		if( self::$_page !== null ) return;
		
		if( !empty($_GET['page']) )
		{		
			switch( $_GET['page'] )
			{
				case 'connections-synch-connections':
				case 'connections-import-connections':
					self::$_page = substr($_GET['page'], 12); break;

				default:
					self::$_page = 'all-connections'; break;
			}
		}
		else
		{
			self::$_page = 'all-connections';
		}
		
		self::$_class = str_replace( '-', '', ucfirst(self::$_page) );
		self::$_class = 'ConnectionsHub_AdminPage_'.self::$_class;
	}
	

	/**
	 * 
	 */	
	public static function show_page()
	{
		call_user_func( array(self::$_class, 'show_page') );
	}
	
	
	/**
	 *
	 */	
	public static function setup_actions()
	{
		if( !file_exists(CONNECTIONS_PLUGIN_PATH.'/admin-page/'.self::$_page.'.php') )
			return;

		require_once( CONNECTIONS_PLUGIN_PATH.'/admin-page/'.self::$_page.'.php' );
		$class_name = str_replace(' ', '', ucwords( str_replace('-', ' ', self::$_page) ) );
		$class_name = 'ConnectionsHub_AdminPage_'.$class_name;
		add_action( 'admin_enqueue_scripts', array($class_name, 'enqueue_scripts') );
		add_action( 'admin_head', array($class_name, 'add_head_script') );
	}

}

