<?php


class ConnectionsMainSite_AdminPage_SynchConnections
{

	/* */
	public static $error_messages;
	public static $notice_messages;


	/* Default private constructor. */
	private function __construct() { }
	
	
	/**
	 *
	 */	
	public static function init()
	{
		self::$error_messages = array();
		self::$notice_messages = array();
	}


	/**
	 *
	 */	
	public static function display_messages()
	{
		foreach( self::$error_messages as $message )
		{
			?><div class="error"><?php echo $message; ?></div><?php
		}
		
		foreach( self::$notice_messages as $message )
		{
			?><div class="updated"><?php echo $message; ?></div><?php
		}
	}


	/**
	 * 
	 */
	public static function enqueue_scripts()
	{
		wp_deregister_script('jquery');
		wp_enqueue_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js');
		
		wp_enqueue_script('syncher', CONNECTIONS_PLUGIN_URL.'/scripts/jquery.ajax.syncher.js');
	}
	
	
	/**
	 * 
	 */
	public static function add_head_script()
	{
		?>
		<style>
		
			
		
		</style>
  		<script type="text/javascript">
			jQuery(document).ready( function()
			{
				
				
				
			});
		</script>
		<?php
	}
	

	/**
	 *
	 */	
	public static function show_page()
	{
		self::init();
		require_once( CONNECTIONS_PLUGIN_PATH.'/classes/synch-list-table.php' );
		$synch_list_table = new ConnectionsMainSite_AdminPage_SynchListTable();
		$synch_list_table->prepare_items(); 

		?>		

		<div class="wrap">
		
		<h2>Synch Connections</h2>
		<div class="instructions">Some instruction go here</div>

		<div class="admin-page-container clearfix">
		
			<button class="button-primary synch-connections">Synch Connections</button>
			<?php $synch_list_table->display(); ?>
		
		</div>
		
		</div>
		
		<?php
	}
		
}

