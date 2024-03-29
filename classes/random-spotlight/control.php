<?php

/**
 * Controls the setup and display of the Random Spotlight widget and shortcode.
 * 
 * Shortcode Example:
 * [random_spotlight title="My Random Spotlight" items="1"]
 * 
 * @package    connections-hub
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ConnectionsHubRandomSpotlight_WidgetShortcodeControl') ):
class ConnectionsHubRandomSpotlight_WidgetShortcodeControl extends WidgetShortcodeControl
{
	/**
	 * The minimum number of connection-link pairs for the random spotlight.
	 * @var  int
	 */	
	private static $MIN_ITEMS = 1;

	/**
	 * The maximum number of connection-link pairs for the random spotlight.
	 * @var  int
	 */
	private static $MAX_ITEMS = 20;
	
	
	/**
	 * Constructor.
	 */
	public function __construct()
	{
		$widget_ops = array(
			'description'	=> 'Display random Connections by tag.',
		);
		
		parent::__construct( 'random-spotlight', 'Connections Spotlight', $widget_ops );
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
		<label for="<?php echo $this->get_field_id( 'items' ); ?>"><?php _e( 'Number of Spotlights:' ); ?></label> 
		<select name="<?php echo $this->get_field_name( 'items' ); ?>">
			<?php for( $i = self::$MIN_ITEMS; $i < self::$MAX_ITEMS+1; $i++ ): ?>
				<option value="<?php echo $i; ?>" <?php selected($i, $items); ?>><?php echo $i; ?></option>
			<?php endfor; ?>
		</select>
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
		$defaults['items'] = 2;
		
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
		
		// Convert items to an integer
		$options['items'] = intval( $options['items'] );
		
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
		echo '<div id="random-spotlight-control-'.self::$index.'" class="wscontrol random-spotlight-control">';
		
		if( !empty($options['title']) )
			echo $args['before_title'].$options['title'].$args['after_title'];
		
		$spotlight_tags = $this->get_spotlight_events( $options['items'] );
		
		$count = 0;
		foreach( $spotlight_tags as $tag )
		{
			$this->print_spotlight( $tag, $args );
			$count++;
		}
		
		while( $count < $options['items'] )
		{
			echo 'No spotlights available.';
			$count++;
		}
		
		echo '</div>';
		echo $args['after_widget'];	
	}
	
	
	/**
	 * Get an array of tags that have two more associated Connections.
	 * @param  int  $num_items  The number of pairs of Spotlight Connections to retrieve.
	 * @return  array  An array of tags that have two or more associated Connections.
	 */
	private function get_spotlight_events( $num_items )
	{
		$tags = get_terms(
			'connection-link',
			array('orderby' => 'count', 'order' => 'DESC')
		);
		$num_items = intval( $num_items );
		$spotlight_tags = array();
		
		for( $i = 0; $i < $num_items; $i++ )
		{
			if( count($tags) === 0 ) break;

			while( count($spotlight_tags) == $i )
			{
				if( count($tags) === 0 ) break;

				$rand = rand( 0, count($tags)-1 );
				$tag = $tags[$rand];
				
				if( intval($tag->count) > 2 )
					$spotlight_tags[] = $tag;

				array_splice($tags, $rand, 1);
			}
		}
		
		return $spotlight_tags;
	}
	
	
	/**
	 * Echo a pair of Connection posts that have a matching connection-link.
	 * @param  string  $tag  The connection-link to use when searching for Connection posts.
	 * @param  array  $args  The display arguments.
	 */
	private function print_spotlight( $tag, $args )
	{
		$settings = Connections_ConnectionCustomPostType::get_settings();
		$connection_links_name = $settings['name']['link']['full_plural'];


		// Get all posts with the tag.
		$posts = get_posts(
			array(
				'post_type' => 'connection',
				'tax_query' => array(
					array(
						'taxonomy' => 'connection-link',
						'field' => 'slug',
						'terms' => $tag->slug,
					),
				),
			)
		);
		

		// Pick 2 random posts.
		if( count($posts) < 3 )
		{
			$spotlight_posts = $posts;
		}
		else
		{
			$spotlight_posts = array();
			$rand = rand( 0, count($posts)-1 );
			$spotlight_posts[] = $posts[$rand];
			array_splice($posts, $rand, 1);
			$rand = rand( 0, count($posts)-1 );
			$spotlight_posts[] = $posts[$rand];
		}
		?>
		
		<div class="spotlight-connections">

		<?php echo $args['before_title']; ?>
		<a href="<?php echo get_term_link($tag->slug, 'connection-link'); ?>" title="<?php echo $tag->name; ?>"><?php echo $tag->name; ?></a>
		<?php echo $args['after_title']; ?>
		
		<?php foreach( $spotlight_posts as $p ): ?>
		
			<?php
			$connection_groups = array();
			$groups = wp_get_post_terms( $p->ID, 'connection-group' );
			foreach( $groups as $group )
			{
				$connection_groups[] = array(
					'name' => $group->name,
					'link' => get_term_link( $group, 'connection-group' ),
				);
			}
			
			$connection_links = array();
			$links = wp_get_post_terms( $p->ID, 'connection-link' );
			foreach( $links as $link )
			{
				$connection_links[] = array(
					'name' => $link->name,
					'link' => get_term_link( $link, 'connection-link' ),
				);
			}

			$site_link = get_post_meta( $p->ID, 'url', true );
		
			if( filter_var($site_link, FILTER_VALIDATE_URL) === false )
				$site_link = null;
		
			$conn = array();
			$conn['ID'] = $p->ID;
			$conn['title'] = $p->post_title;
			$conn['post-content'] = apply_filters( 'get_the_content', $p->post_content );
			$conn['contact-info'] = get_post_meta( $p->ID, 'contact-info', true );
			$conn['groups'] = $connection_groups;
			$conn['links'] = $connection_links;
			$conn['link'] = get_permalink($p->ID);
			$conn['site-link'] = $site_link;

/* 
			$links = array(
				 array_slice( $connection_links, 0, ceil(count($connection_links) / 2) ),
				 array_slice( $connection_links, ceil(count($connection_links) / 2) )
			);
 */
			
			$links = $connection_links;
			?>

			<div class="post connection">

				<h2 class="entry-title"><a href="<?php echo get_permalink($conn['ID']); ?>"><?php echo $conn['title']; ?></a></h2>
	
				<div class="description">
				<div class="entry-content-container">
				<div class="entry-content">
								
				<div class="connection-groups">
					<?php foreach( $conn['groups'] as $group ): ?>
					<div><?php echo '<a href="'.$group['link'].'" title="'.$group['name'].'">'.$group['name'].'</a>'; ?></div>
					<?php endforeach; ?>
				</div><!-- .connection-groups -->

				<div class="connection-links">
					<?php foreach( $conn['links'] as $link ): ?>
					<div><?php echo '<a href="'.$link['link'].'" title="'.$link['name'].'">'.$link['name'].'</a>'; ?></div>
					<?php endforeach; ?>
				</div><!-- .connection-groups -->
				<div class="summary">
				<a href="<?php echo get_permalink($conn['ID']); ?>">Read More...</a>
				</div>

				</div><!-- .entry-content -->
				</div><!-- .entry-content-container -->
	
			</a>
			</div><!-- .post -->
			
		<?php endforeach; ?>
		
<!-- 		<div class="find-more"><a href="<?php echo get_term_link($tag->slug, 'connection-link'); ?>" title="<?php echo $tag->name; ?>">Find more...</a></div> -->
		
		</div>
		<?php
	}
}
endif;

