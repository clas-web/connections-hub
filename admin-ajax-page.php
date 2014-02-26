<?php


class ConnectionsMainSite_AdminAjaxPage
{
	private static $_output;

	/* */
	private function __construct() { }
	

	/**
	 * 
	 */
	public static function init()
	{
		self::$_output = array();
	}
	

	/**
	 * 
	 */
	public static function process()
	{
		if( empty($_POST['ajax-action']) ) return;
		
		switch( $_POST['ajax-action'] )
		{
			case 'check-site':
				self::check_site();
				break;
				
			case 'synch-site':
				self::synch_site();
				break;
		}
	}
	

	/**
	 * 
	 */
	public static function output()
	{
		echo json_encode(
			self::$_output
		);
	}
	
	
	/**
	 * 
	 */
	private static function check_site( $return_data = false )
	{
		if( (empty($_POST['nonce'])) || 
		    (!wp_verify_nonce($_POST['nonce'], CONNECTIONS_PLUGIN_PATH)) )
		{
			self:$_output = array(
				'status' => false,
				'message' => 'Invalid',
			);
			return false;
		}
		
		if( !isset($_POST['id']) && !isset($_POST['url']) )
		{
			self::$_output = array(
				'status' => false,
				'message' => 'Invalid',
			);
			return false;
		}
		
		require_once( CONNECTIONS_PLUGIN_PATH.'/classes/synch-connection.php' );
		$synch_data = ConnectionsMainSite_SynchConnection::get_data( $_POST['id'] );
		if( $synch_data === false )
		{
			self::$_output = array(
				'status' => false,
				'message' => ConnectionsMainSite_SynchConnection::$last_error,
			);
			return false;
		}
		
		self::$_output = array(
			'status' => true,
			'message' => '',
		);
		
		if( $return_data ) return $synch_data;
		return true;
	}

	
	/**
	 * 
	 */
	private static function synch_site()
	{
		$result = self::check_site( true );
		if( $result === false ) return;
		
		ConnectionsMainSite_SynchConnection::synch( $_POST['id'], $result );
	}

}

