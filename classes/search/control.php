<?php

require_once( dirname( __DIR__ ) . '/widget-shortcode-control.php' );


/**
 * Controls the setup and display of the Connections Search widget and shortcode.
 * 
 * Shortcode Example:
 * [connections_search title="My Connections Search" placeholder="Search Connections..."]
 * 
 * @package    connections-hub
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ConnectionsHubSearch_WidgetShortcodeControl') ):
class ConnectionsHubSearch_WidgetShortcodeControl extends WidgetShortcodeControl
{
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$widget_ops = array(
			'description'	=> 'Display Connections search form.',
		);
		
		parent::__construct( 'connections-search', 'Connections Search', $widget_ops );
	}
	
	
	/**
	 * Output the widget form in the admin.  Use this function instead of form.
	 * @param  array  $options  The current settings for the widget.
	 */
	public function print_widget_form( $options )
	{
		$options = $this->merge_options( $options );
		extract( $options );
		?>
		
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" class="widefat">
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'placeholder' ); ?>"><?php _e( 'Placeholder:' ); ?></label> 
		<input id="<?php echo $this->get_field_id( 'placeholder' ); ?>" name="<?php echo $this->get_field_name( 'placeholder' ); ?>" type="text" value="<?php echo esc_attr( $placeholder ); ?>" class="widefat">
		</p>
		
		<?php
	}
	
	
	/**
	 * Get the default settings for the widget or shortcode.
	 * @return  array  The default settings.
	 */
	public function get_default_options()
	{
		$defaults = array();
		$defaults['title'] = '';
		$defaults['placeholder'] = 'Search Connections...';
		
		return $defaults;
	}
	
	
	/**
	 * Process options from the database or shortcode.
	 * Designed to convert options from strings or sanitize output.
	 * @param  array  $options  The current settings for the widget or shortcode.
	 * @return  array  The processed settings.
	 */
	public function process_options( $options )
	{
		// Trim strings
		foreach( $options as $k => &$v )
		{
			if( is_string($v) ) $v = trim( $v );
		}
		
		return $options;
	}
	
	
	/**
	 * Echo the widget or shortcode contents.
	 * @param  array  $options  The current settings for the control.
	 * @param  array  $args  The display arguments.
	 */
	public function print_control( $options, $args = null )
	{
		$options = $this->merge_options( $options );
		if( !$args ) $args = $this->get_args();
		
		extract( $options );
		
		echo $args['before_widget'];
		echo '<div id="connections-search-control-'.self::$index.'" class="wscontrol connections-search-control">';
		
		if( !empty($options['title']) )
			echo $args['before_title'].$options['title'].$args['after_title'];
		?>
		
		<form role="search" method="get" class="searchform" action="<?php echo esc_attr( site_url() ); ?>">
			<div>
				<label class="screen-reader-text" for="s">Search for:</label>
				<input type="hidden" name="post_type" value="connection">
				<div class="textbox_wrapper">
					<input name="s" type="text" value="" placeholder="<?php echo esc_attr( $placeholder ); ?>" class="ui-autocomplete-input" autocomplete="off">
				</div>
				<input type="submit" id="searchsubmit" value="Search">
			</div>
		</form>

		<?php
		echo '</div>';
		echo $args['after_widget'];	
	}
}
endif;

