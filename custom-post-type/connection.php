<?php
/**
 * 
 */



add_action( 'init', array( 'Connections_ConnectionCustomPostType', 'create_custom_post' ) );
add_filter( 'post_updated_messages', array( 'Connections_ConnectionCustomPostType', 'update_messages' ) );

// Add New / Edit Connections changes
add_action( 'add_meta_boxes', array( 'Connections_ConnectionCustomPostType', 'add_meta_boxes' ) );
add_action( 'save_post_connection', array( 'Connections_ConnectionCustomPostType', 'save_post_entry_form' ), 9999, 3 );

// All Connections changes
add_filter( 'views_edit-connection', array( 'Connections_ConnectionCustomPostType', 'all_connections_add_synch_button' ) );
add_filter( 'manage_edit-connection_columns', array( 'Connections_ConnectionCustomPostType', 'all_connections_columns_key' ) );
add_action( 'manage_connection_posts_custom_column', array( 'Connections_ConnectionCustomPostType', 'all_connections_columns_value' ), 10, 2 );



class Connections_ConnectionCustomPostType
{

	/**
	 * Constructor.
	 * Private.  Class only has static members.
	 * TODO: look up PHP abstract class implementation.
	 */
	private function __construct() { }


	/**
	 * Creates the custom Connection post type.
	 */	
	public static function create_custom_post()
	{
		$labels = array(
			'name'               => 'Connections',
			'singular_name'      => 'Connection',
			'add_new'            => 'Add New',
			'add_new_item'       => 'Add New Connection',
			'edit_item'          => 'Edit Connection',
			'new_item'           => 'New Connection',
			'all_items'          => 'All Connection',
			'view_item'          => 'View Connection',
			'search_items'       => 'Search Connections',
			'not_found'          => 'No Connections found',
			'not_found_in_trash' => 'No Connections found in the Trash',
			'parent_item_colon'  => '',
			'menu_name'          => 'Connections'
		);
		
		$args = array(
			'labels'        => $labels,
			'description'   => 'Holds our Connections data',
			'public'        => true,
			'menu_position' => 5,
			'supports'      => array( 'title' ),
			'taxonomies'    => array( 'category', 'post_tag' ),
			'has_archive'   => true,
		);
		
		register_post_type( 'connection', $args );	
	}
	
	
	/**
	 * Updates the messages displayed by the custom Event post type.
	 */
	public static function update_messages( $messages )
	{
		global $post, $post_ID;
		$messages['connection'] = array(
			0 => '', 
			1 => sprintf( __('Connection updated. <a href="%s">View Connection</a>'), esc_url( get_permalink($post_ID) ) ),
			2 => __('Custom field updated.'),
			3 => __('Custom field deleted.'),
			4 => __('Connection updated.'),
			5 => isset($_GET['revision']) ? sprintf( __('Connection restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
			6 => sprintf( __('Connection published. <a href="%s">View Connection</a>'), esc_url( get_permalink($post_ID) ) ),
			7 => __('Connection saved.'),
			8 => sprintf( __('Connection submitted. <a target="_blank" href="%s">Preview Connection</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
			9 => sprintf( __('Connection scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview Connection</a>'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ), esc_url( get_permalink($post_ID) ) ),
			10 => sprintf( __('Connection draft updated. <a target="_blank" href="%s">Preview Connection</a>'), esc_url( add_query_arg( 'preview', 'true', get_permalink($post_ID) ) ) ),
		);
		return $messages;
	}
	
	
	/**
	 * Sets up the custom meta box with special Event meta data tags.
	 */
	public static function add_meta_boxes()
	{
		wp_nonce_field( CONNECTIONS_PLUGIN_PATH, 'connection-custom-post-type-entry-form' );

		add_meta_box(
			'connections_info_box_imported_content',
			'Imported Content',
			array( 'Connections_ConnectionCustomPostType', 'info_box_imported_content' ),
			'connection',
			'normal',
			'high'
		);
		add_meta_box( 
			'connections_info_box_connections_info',
			'Connection Info',
			array( 'Connections_ConnectionCustomPostType', 'info_box_connections_info' ),
			'connection',
			'normal',
			'high'
		);
		add_meta_box( 
			'connections_info_box_synch_data',
			'Synch Data',
			array( 'Connections_ConnectionCustomPostType', 'info_box_synch_data' ),
			'connection',
			'side',
			'high'
		);
	}
	
	
	/**
	 * 
	 */
	public static function info_box_imported_content( $post )
	{
		$search_content = get_post_meta( $post->ID, 'search-content', true );

		?>
		<textarea id="connections-imported-content" readonly style="width:100%;height:200px;"><?php echo $post->post_content; ?></textarea>

		<label for="connections-search-content">Search Content</label><br/>
		<textarea id="connections-search-content" readonly style="width:100%;height:50px;"><?php echo esc_attr($search_content); ?></textarea><br/>
		<?php
	}
	
	
	/**
	 * Writes the HTML code used to create the contents of the Event meta box.
	 * @param WP_Post The current post being displayed.
	 */
	public static function info_box_connections_info( $post )
	{
		$username = get_post_meta( $post->ID, 'username', true );
		$url = get_post_meta( $post->ID, 'url', true );
		$site_type = get_post_meta( $post->ID, 'site-type', true );
		
		$site_types = array( 'wp' => 'WordPress site', 'rss' => 'RSS feed' );
		if( !array_key_exists($site_type, $site_types) )
			$site_type = 'wp';
		
		?>
		<label for="connections-name">username</label><br/>
		<input type="text" id="connections-username" name="connections-username" value="<?php echo esc_attr($username); ?>" style="width:100%" /><br/>

		<label for="connections-url">URL</label><br/>
		<input type="text" id="connections-url" name="connections-url" value="<?php echo esc_attr($url); ?>" style="width:100%" /><br/>

		<label for="connections-site-type">Site Type</label><br/>
		<select name="connections-site-type">
			<?php foreach( $site_types as $name => $value ): ?>
				<option value="<?php echo $name; ?>" <?php echo ($site_type == $name ? 'selected' : ''); ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		<?php
	}
	
	
	/**
	 * Writes the HTML code used to create the contents of the Event meta box.
	 * @param WP_Post The current post being displayed.
	 */
	public static function info_box_synch_data( $post )
	{
		$needs_synch = get_post_meta( $post->ID, 'needs-synch', true );
		if( empty($needs_synch) ) $needs_synch = 'true';
		$synch_data = get_post_meta( $post->ID, 'synch-data' );
		
		if( !empty($synch_data) )
		{
			$sd = '<br/>';
			foreach( $synch_data as $key => $value )
			{
				$sd .= $key.': '.$value."<br/>";
			}
		}
		else
		{
			$sd = 'Never synched';
		}
		
		?>
		Needs Synch: <?php echo $needs_synch; ?><br/>
		Synch Data: <?php echo $sd; ?>


		<div id="major-publishing-actions" style="margin:-12px;margin-top:10px;">

			<?php if( $post->post_status !== 'publish' ): ?>
				<input name="synch" type="submit" class="button button-primary button-large" id="synch" value="Publish & Synch" style="float:right;">
			<?php else: ?>
				<input name="synch" type="submit" class="button button-primary button-large" id="synch" value="Update & Synch" style="float:right;">
			<?php endif; ?>

			<div class="clear"></div>
		</div>
		
		<?php
	}
	
	
	/**
	 * Saves the Event's custom meta data.
	 * @param int The current post's id.
	 */
	public static function save_post_entry_form( $post_id, $post, $updated )
	{
		if( empty($_POST['connection-custom-post-type-entry-form']) )
			return;
		
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return;

		if ( !current_user_can('edit_page', $post_id) )
			return;

		if( !wp_verify_nonce($_POST['connection-custom-post-type-entry-form'], CONNECTIONS_PLUGIN_PATH) )
			return;
		

		//var_dump($_POST);


		//
		// Save data
		//
		self::save_meta_data(
			$_POST['connections-url'], 
			$_POST['connections-username'], 
			$_POST['connections-site-type']
		);
		
		//
		// Synch content
		//
		if( !isset($_POST['sync']) ) return;
		
		require_once( CONNECTIONS_PLUGIN_PATH.'/classes/synch-connection.php' );
		$synch_data = ConnectionsMainSite_SynchConnection::get_data($post_id);
		ConnectionsMainSite_SynchConnection::synch($post_id, $synch_data);
	}


	/**
	 * 
	 */
	public static function save_meta_data( $post_id, $url, $username, $site_type )
	{
		//
		// Get current data
		//
		$meta_username = get_post_meta( $post_id, 'username', true );
		$meta_url = get_post_meta( $post_id, 'url', true );
		$meta_site_type = get_post_meta( $post_id, 'site-type', true );
		$meta_needs_synch = get_post_meta( $post_id, 'needs-synch', true );
		if( empty($meta_needs_synch) ) $meta_needs_synch = true;
		else $meta_needs_synch = ($meta_needs_synch == 'true' ? true : false);

		
		// Save data
		update_post_meta( $post_id, 'username', $username );
		update_post_meta( $post_id, 'url', $url );
		update_post_meta( $post_id, 'site-type', $site_type );
	
		
		if( ($meta_needs_synch) || ($meta_url !== $url) || ($meta_site_type !== $site_type) )
		{
			update_post_meta( $post_id, 'needs-synch', 'true' );
		}
		else
		{
			update_post_meta( $post_id, 'needs-synch', 'false' );
		}
	}
	
	
	/**
	 * 
	 */
	public static function all_connections_add_synch_button( $views )
	{
		$views['connections-synch'] = '
			<a href="edit.php?post_type=connection&page=connections-synch-connections" title="Synch Connections" style="font-weight:bold">Synch Connections &raquo;</a>
		';
		return $views;
	}


	/**
	 * 
	 */
	public static function all_connections_columns_key( $columns )
	{
		$columns['url'] = 'Source';
		$columns['synch'] = 'Synch Data';
		return $columns;
	}
	

	/**
	 * 
	 */
	public static function all_connections_columns_value( $column_name, $post_id )
	{
		switch( $column_name )
		{
			case 'url':
				$url = get_post_meta( $post_id, 'url', true );
				if( ($url = connections_fix_url($url)) == '' )
				{
					echo 'Invalid URL<br/>';
				}
				else
				{
					echo $url.'<br/>';
				}
				$site_type = get_post_meta( $post_id, 'site-type', true );
				switch($site_type)
				{
					case 'rss': echo 'RSS Feed'; break;
					case 'wp': echo 'WordPress Site'; break;
					default: echo 'Unknown'; break;
				}
				break;

			case 'synch':
				$synch_data = get_post_meta( $post_id, 'site-data' );
				if( empty($synch_data) )
				{
					echo 'Never synched';
				}
				else
				{
					foreach( $synch_data as $key => $value )
					{
						echo $key.': '.$value.'<br/>';
					}
				}
				break;
		}
	}

}


