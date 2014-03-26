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

	protected static $_category_name = 'connection-group';
	protected static $_tag_name = 'connection-link';
	
	protected static $_category_slug = 'connection-group';
	protected static $_tag_slug = 'connection-link';

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
			'all_items'          => 'All Connections',
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
			'taxonomies'    => array(), //array( 'category', 'post_tag' ),
			'has_archive'   => true,
		);
		
		register_post_type( 'connection', $args );

		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => 'Connection Groups',
			'singular_name'     => 'Connection Group',
			'search_items'      => 'Search Connection Groups',
			'all_items'         => 'All Connection Groups',
			'parent_item'       => 'Parent Group',
			'parent_item_colon' => 'Parent Group:',
			'edit_item'         => 'Edit Group',
			'update_item'       => 'Update Group',
			'add_new_item'      => 'Add New Group',
			'new_item_name'     => 'New Group Name',
			'menu_name'         => 'Groups',
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => self::$_category_slug ),
		);

		register_taxonomy( self::$_category_name, array( 'connection' ), $args );

		// Add new taxonomy, NOT hierarchical (like tags)
		$labels = array(
			'name'                       => 'Connection Links',
			'singular_name'              => 'Connection Link',
			'search_items'               => 'Search Connection Links',
			'popular_items'              => 'Popular Connection Links',
			'all_items'                  => 'All Connection Links',
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => 'Edit Link',
			'update_item'                => 'Update Link',
			'add_new_item'               => 'Add New Link',
			'new_item_name'              => 'New Link Name',
			'separate_items_with_commas' => 'Separate Link with commas',
			'add_or_remove_items'        => 'Add or remove Link',
			'choose_from_most_used'      => 'Choose from the most used Link',
			'not_found'                  => 'No Link found.',
			'menu_name'                  => 'Links',
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => self::$_tag_slug ),
		);

		register_taxonomy( self::$_tag_name, 'connection', $args );

		/*				
		// Add new taxonomy, make it hierarchical (like categories)
		$labels = array(
			'name'              => 'Connection Categories',
			'singular_name'     => 'Connection Category',
			'search_items'      => 'Search Connection Categories',
			'all_items'         => 'All Connection Categories',
			'parent_item'       => 'Parent Connection Category',
			'parent_item_colon' => 'Parent Connection Category:',
			'edit_item'         => 'Edit Connection Category',
			'update_item'       => 'Update Connection Category',
			'add_new_item'      => 'Add New Connection Category',
			'new_item_name'     => 'New Connection Category Name',
			'menu_name'         => 'Connection Categories',
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => 'connection-category' ),
		);

		register_taxonomy( 'connection-category', array( 'connection' ), $args );

		// Add new taxonomy, NOT hierarchical (like tags)
		$labels = array(
			'name'                       => 'Connection Tag',
			'singular_name'              => 'Connection Tags',
			'search_items'               => 'Search Connection Tags',
			'popular_items'              => 'Popular Connection Tags',
			'all_items'                  => 'All Connection Tags',
			'parent_item'                => 'Parent Connection Tag',
			'parent_item_colon'          => 'Parent Connection Tag:',
			'edit_item'                  => 'Edit Connection Tag',
			'update_item'                => 'Update Connection Tag',
			'add_new_item'               => 'Add New Connection Tag',
			'new_item_name'              => 'New Connection Tag Name',
			'separate_items_with_commas' => 'Separate Connection Tags with commas',
			'add_or_remove_items'        => 'Add or remove Connection Tags',
			'choose_from_most_used'      => 'Choose from the most used Connection Tags',
			'not_found'                  => 'No Connection Tags found.',
			'menu_name'                  => 'Connection Tags',
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => 'connection-tag' ),
		);

		register_taxonomy( 'connetions-tag', 'connection', $args );
		*/

		flush_rewrite_rules(false);
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
			'default'
		);
		add_meta_box(
			'connections_info_box_contact_info',
			'Contact Info',
			array( 'Connections_ConnectionCustomPostType', 'info_box_contact_info' ),
			'connection',
			'side',
			'default'
		);
	}
	
	
	/**
	 * 
	 */
	public static function info_box_imported_content( $post )
	{
		wp_nonce_field( CONNECTIONS_PLUGIN_PATH, 'connection-custom-post-type-entry-form' );

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
		$synch_data = Connections_ConnectionCustomPostType::format_synch_data( get_post_meta($post->ID, 'synch-data', true) );
		echo $synch_data; 
		?>

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
	
	
	public static function info_box_contact_info( $post )
	{
		$contact_info = get_post_meta($post->ID, 'contact-info', true);
		
		if( empty($contact_info) )
			$contact_info = 'No contact info.';
			
		echo $contact_info;
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
		
		$post_data = $_POST;
		unset($_POST); // prevent looping...

		//
		// Save data
		//
		self::save_meta_data(
			$post_id,
			$post_data['connections-url'], 
			$post_data['connections-username'], 
			$post_data['connections-site-type']
		);
		
		//
		// Synch content
		//
		//echo '<pre>'; var_dump($_POST); echo '</pre>'; 
		if( !isset($post_data['synch']) ) return;
		
		require_once( CONNECTIONS_PLUGIN_PATH.'/classes/synch-connection.php' );
		$synch_data = ConnectionsHub_SynchConnection::get_data($post_id);
		if( $synch_data !== false )
			ConnectionsHub_SynchConnection::synch($post_id, $synch_data);
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
		
		// set author
		if( get_userdatabylogin($username) )
		{
			$user = get_user_by( 'slug', $username );
			wp_update_post( array('ID' => $post_id, 'post_author' => $user->ID) );
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
		//$columns['synch'] = 'Synch Data';
		unset($columns['taxonomy-'.self::$_tag_name]);
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
					echo '<a href="'.$url.'" target="_blank">'.$url.'</a><br/>';
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
				echo Connections_ConnectionCustomPostType::format_synch_data( get_post_meta($post_id, 'synch-data', true) );
				break;
		}
	}
	
	
	
	public static function format_synch_data( $data )
	{
		$sd = '';
		
		if( empty($data) )
		{
			$sd = 'Never synched.';
		}
		else
		{
			foreach( $data as $key => $value )
			{
				if( ($key === 'view-url') && (!empty($value)) )
					$value = '<a href="'.$value.'" target="_blank">'.$value.'</a>';
				$key = ucwords( str_replace('-', ' ', $key) );
				$sd .= '<strong>'.$key.':</strong> '.$value.'<br/>';
			}
		}
		
		return $sd;
	}

}


