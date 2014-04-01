<?php

add_action('widgets_init',
     create_function('', 'return register_widget("ConnectionsHub_RandomSpotlightConnectionsWidget");')
);

class ConnectionsHub_RandomSpotlightConnectionsWidget extends WP_Widget
{

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct()
	{
		// widget actual processes
		
		//ns_print('construct');
		
		parent::__construct(
			'connections-hub_random-connections-spotlight-widget',
			'Random Connections Spotlight',
			array( 
				'description' => 'Display random Connections by tag.', 
			)
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget( $args, $instance )
	{
		echo $args['before_widget'];

		if( !empty($instance['title']) )
			echo $args['before_title'].$instance['title'].$args['after_title'];

		$tags = get_terms( 'connection-link', array('orderby' => 'count', 'order' => 'DESC') );
		$number_of_spotlights = intval($instance['number-of-spotlights']);
		$spotlight_tags = array();
		
		for( $i = 0; $i < $number_of_spotlights; $i++ )
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
		
		?>
		<div class="spotlight-connections-widget">
		<?php
		
		$count = 0;
		foreach( $spotlight_tags as $tag )
		{
			$this->print_spotlight( $tag );
			$count++;
		}
		
		while( $count < $number_of_spotlights )
		{
			echo 'No spotlights available.';
			$count++;
		}

		?>
		</div>
		<?php

		echo $args['after_widget'];
	}

	/**
	 * Ouputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form( $instance )
	{
		// outputs the options form on admin

		//ns_print('options of the widget');
		
		if( isset($instance['title']) )
			$title = $instance['title'];
			
		if( isset($instance['number-of-spotlights']) )
			$number_of_spotlights = $instance['number-of-spotlights'];
		else
			$number_of_spotlights = 2;
			
		$nums = array( 1, 2, 4, 6, 8, 10 );
		?>
		
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>

		<p>
		<label for="<?php echo $this->get_field_id( 'number-of-spotlights' ); ?>"><?php _e( 'Number of Spotlights:' ); ?></label> 
		<select name="<?php echo $this->get_field_name( 'number-of-spotlights' ); ?>">
			<?php foreach( $nums as $n ): ?>
				<option value="<?php echo $n; ?>" <?php echo ($number_of_spotlights == $n ? 'selected' : ''); ?>><?php echo $n; ?></option>
			<?php endforeach; ?>
		</select>
		</p>
		
		<?php
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update( $new_instance, $old_instance )
	{
		// processes widget options to be saved
		
		//ns_print($new_instance);
		//ns_print($old_instance);
		
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['number-of-spotlights'] = ( ! empty( $new_instance['number-of-spotlights'] ) ) ? strip_tags( intval($new_instance['number-of-spotlights']) ) : 2;

		return $instance;		
	}


	private function print_spotlight( $tag )
	{
		$settings = Connections_ConnectionCustomPostType::get_settings();
		$connection_links_name = $settings['name']['link']['full_plural'];

		// get all posts with the tag.
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
		
		// pick 2 random posts.
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
		
		<h2><a href="<?php echo get_term_link($tag->slug, 'connection-link'); ?>" title="<?php echo $tag->name; ?>"><?php echo $tag->name; ?></a></h2>
		
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
		
			$story = array();
			$story['title'] = $p->post_title;
			$story['post-content'] = apply_filters( 'get_the_content', $p->post_content );
			$story['contact-info'] = get_post_meta( $p->ID, 'contact-info', true );
			$story['groups'] = $connection_groups;
			$story['links'] = $connection_links;
			$story['link'] = get_permalink($p->ID);
			$story['site-link'] = $site_link;

			$links = array(
				 array_slice( $connection_links, 0, ceil(count($connection_links) / 2) ),
				 array_slice( $connection_links, ceil(count($connection_links) / 2) )
			);
			?>
			
			<div class="story clearfix">

				<h3><?php echo $story['title']; ?></h3>
	
				<div class="connection-groups">
					<?php foreach( $story['groups'] as $group ): ?>
					<div><?php echo '<a href="'.$group['link'].'" title="'.$group['name'].'">'.$group['name'].'</a>'; ?></div>
					<?php endforeach; ?>
				</div><!-- .connection-groups -->
	
				<div class="details clearfix">
	
					<div class="column column-1">
		
						<div class="links">
							<?php echo '<a href="'.$story['link'].'" title="View Summary">Summary</a>'; ?>
							<?php if( $story['site-link'] !== null ): ?>
							|
							<?php echo '<a href="'.$story['site-link'].'" title="View Full Profile">Full Profile</a>'; ?>
							<?php endif; ?>
						</div><!-- .links -->
			
						<div class="contact-info">
							<?php echo $story['contact-info']; ?>
						</div><!-- .contact-info -->
		
					</div><!-- .column-1 -->
		
					<div class="column column-2">
		
						<?php $count = 1; ?>
						<div class="connection-links columns-<?php echo count($links); ?> clearfix">
							<h5><?php echo $connection_links_name; ?></h5>
							<?php foreach( $links as $link_column ): ?>
							<div class="column column-<?php echo $count; ?>">
							<?php foreach( $link_column as $link ): ?>
							<div><?php echo '<a href="'.$link['link'].'" title="'.$link['name'].'">'.$link['name'].'</a>'; ?></div>
							<?php endforeach; ?>
							</div>
							<?php $count++; ?>
							<?php endforeach; ?>
						</div><!-- .connection-links -->
		
					</div><!-- .column-2 -->
	
				</div><!-- .details -->
	
			</a>
			</div><!-- .story -->
			
		<?php endforeach; ?>
		
		<div class="find-more"><a href="<?php echo get_term_link($tag->slug, 'connection-link'); ?>" title="<?php echo $tag->name; ?>">Find more...</a></div>
		
		</div>
		<?php
	}
	
}


