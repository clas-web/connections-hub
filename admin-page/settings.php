<?php


class ConnectionsHub_AdminPage_Settings
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
		wp_enqueue_script('jquery', 'https://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js');
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
		self::process_post();
		
		$settings = Connections_ConnectionCustomPostType::get_settings( true );
//  	ns_print($settings, 'settings');
		?>		

		<div class="wrap">
		
		<h2>Settings</h2>

		<?php self::display_messages(); ?>
		<div class="instructions">Some instruction go here</div>

		<div class="admin-page-container clearfix">
		
			<form action="" method="post">
				<?php settings_fields( 'connections-settings' ); ?>

				<input type="hidden" name="action" value="save-settings" />

				<?php extract( $settings['name']['connection'] ); ?>
				<label for="file">Connections:</label><br/>
				<span>Full Single:</span><input type="text" value="<?php echo $full_single; ?>" name="settings[name][connection][full_single]" id="connection-single-name"><br>
				<span>Full Plural:</span><input type="text" value="<?php echo $full_plural; ?>" name="settings[name][connection][full_plural]" id="connection-plural-name"><br>
				<span>Short Single:</span><input type="text" value="<?php echo $short_single; ?>" name="settings[name][connection][short_single]" id="connection-single-name"><br>
				<span>Short Plural:</span><input type="text" value="<?php echo $short_plural; ?>" name="settings[name][connection][short_plural]" id="connection-plural-name"><br>
				<span>Slug:</span><input type="text" value="<?php echo $slug; ?>" name="settings[name][connection][slug]" id="connection-slug"><br>
				<br/>

				<?php extract( $settings['name']['group'] ); ?>
				<label for="file">Connection Groups:</label><br/>
				<span>Full Single:</span><input type="text" value="<?php echo $full_single; ?>" name="settings[name][group][full_single]" id="connection-group-full-single-name"><br>
				<span>Full Plural:</span><input type="text" value="<?php echo $full_plural; ?>" name="settings[name][group][full_plural]" id="connection-group-full-plural-name"><br>
				<span>Short Single:</span><input type="text" value="<?php echo $short_single; ?>" name="settings[name][group][short_single]" id="connection-group-short-single-name"><br>
				<span>Short Plural:</span><input type="text" value="<?php echo $short_plural; ?>" name="settings[name][group][short_plural]" id="connection-group-short-plural-name"><br>
				<span>Slug:</span><input type="text" value="<?php echo $slug; ?>" name="settings[name][group][slug]" id="connection-group-slug"><br>
				<br/>
				
				<?php extract( $settings['name']['link'] ); ?>
				<label for="file">Connection Links:</label><br/>
				<span>Full Single:</span><input type="text" value="<?php echo $full_single; ?>" name="settings[name][link][full_single]" id="connection-link-full-single-name"><br>
				<span>Full Plural:</span><input type="text" value="<?php echo $full_plural; ?>" name="settings[name][link][full_plural]" id="connection-link-full-plural-name"><br>
				<span>Short Single:</span><input type="text" value="<?php echo $short_single; ?>" name="settings[name][link][short_single]" id="connection-link-short-single-name"><br>
				<span>Short Plural:</span><input type="text" value="<?php echo $short_plural; ?>" name="settings[name][link][short_plural]" id="connection-link-short-plural-name"><br>
				<span>Slug:</span><input type="text" value="<?php echo $slug; ?>" name="settings[name][link][slug]" id="connection-link-slug"><br>
				<br/>

				<?php submit_button( 'Save Settings' ); ?>
			</form>
			
		</div>
		
		</div>
		
		<?php
	}
	
	
	/**
	 * 
	 */
	private static function process_post()
	{
		if( !isset($_POST) || !isset($_POST['action']) ) return;
		
		switch( $_POST['action'] )
		{
			case 'save-settings':
				self::save_settings();
				break;
		}
	}

	
	/**
	 * 
	 */
	private static function save_settings()
	{
		if( !isset($_POST['settings']) ) return;
//  	ns_print($_POST['settings'], 'post');
		update_option( 'connections_hub_settings', $_POST['settings'] );
	}

}


/*  */
