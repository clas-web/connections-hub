<?php

add_action( 'init', array( 'Connections_ConnectionCustomPostType', 'create_custom_post' ) );
add_filter( 'post_updated_messages', array( 'Connections_ConnectionCustomPostType', 'update_messages' ) );

// Add New / Edit Connections changes
add_action( 'add_meta_boxes', array( 'Connections_ConnectionCustomPostType', 'add_meta_boxes' ) );
add_action( 'save_post_connection', array( 'Connections_ConnectionCustomPostType', 'save_post_entry_form' ), 9999, 3 );

// All Connections changes
add_filter( 'views_edit-connection', array( 'Connections_ConnectionCustomPostType', 'all_connections_add_synch_button' ) );
add_filter( 'manage_edit-connection_columns', array( 'Connections_ConnectionCustomPostType', 'all_connections_columns_key' ) );
add_action( 'manage_connection_posts_custom_column', array( 'Connections_ConnectionCustomPostType', 'all_connections_columns_value' ), 10, 2 );


/**
 * The main class that controls the Connections custom post type.
 * 
 * @package    connections-hub
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('Connections_ConnectionCustomPostType') ):
class Connections_ConnectionCustomPostType
{
	/**
	 * The stored settings.
	 * @var  Array
	 */
	protected static $_settings = null;
	
	
	/**
	 * Private Constructor.  Class only has static members.
	 */
	private function __construct() { }
	
	
	/**
	 * Get the custom post type and custom taxonomy settings.
	 * @param  bool  $refresh  True to refresh the settings even if previously stored.
	 * @return  Array  The settings for the Labs custom post type.
	 */
	public static function get_settings( $refresh = false )
	{
		if( self::$_settings !== null && !$refresh ) return self::$_settings;
		
		$default = array(
			'name' => array(
				'connection' => array(
					'full_single' => 'Connection',
					'full_plural' => 'Connections',
					'short_single' => 'Connection',
					'short_plural' => 'Connections',
					'slug' => 'connection',
				),
				'group' => array(
					'full_single' => 'Connection Group',
					'full_plural' => 'Connection Groups',
					'short_single' => 'Group',
					'short_plural' => 'Groups',
					'slug' => 'connection-group',
				),
				'link' => array(
					'full_single' => 'Connection Link',
					'full_plural' => 'Connection Links',
					'short_single' => 'Link',
					'short_plural' => 'Links',
					'slug' => 'connection-link',
				),
			),
		);

		$settings = get_option( CONNECTIONS_HUB_OPTIONS, false );

		if( $settings === false )
		{
			$settings = $default;
		}
		elseif( !isset($settings['name']) )
		{
			$settings['name'] = $default['name'];
		}
		else
		{
			// Make sure each setting is set.
			foreach( $default['name'] as $section_name => &$section )
			{
				if( !isset($settings['name'][$section_name]) )
				{
					$settings['name'][$section_name] = $section;
					continue;
				}
				
				// If any short version of a setting is missing, then use full version.
				foreach( $section as $sk => $sv )
				{
					switch( $sk )
					{
						case 'short_single':
							if( empty($settings['name'][$section_name]['short_single']) )
							{
								if( !empty($settings['name'][$section_name]['full_single']) )
									$settings['name'][$section_name]['short_single'] = $settings['name'][$section_name]['full_single'];
								else
									$settings['name'][$section_name]['short_single'] = $default['name'][$section_name]['short_single'];
							}
							break;
						
						case 'short_plural':
							if( empty($settings['name'][$section_name]['short_plural']) )
							{
								if( !empty($settings['name'][$section_name]['full_plural']) )
									$settings['name'][$section_name]['short_plural'] = $settings['name'][$section_name]['full_plural'];
								else
									$settings['name'][$section_name]['short_plural'] = $default['name'][$section_name]['short_plural'];
							}
							break;
					}
					
					if( empty($settings['name'][$section_name][$sk]) )
						$settings['name'][$section_name][$sk] = $default['name'][$section_name][$sk];
				}
			}
		}
		
		// Save settings and return.
		self::$_settings = $settings;
		return self::$_settings;
	}
	
	
	/**
	 * Creates the custom Connection post type.
	 */	
	public static function create_custom_post()
	{
		$settings = self::get_settings();
		

		// Setup Connections post type.
		extract( $settings['name']['connection'] );
		
		$labels = array(
			'name'               => $full_plural,
			'singular_name'      => $full_single,
			'add_new'            => 'Add New',
			'add_new_item'       => "Add New $full_single",
			'edit_item'          => "Edit $full_single",
			'new_item'           => "New $full_single",
			'all_items'          => "All $full_plural",
			'view_item'          => "View $full_single",
			'search_items'       => "Search $full_plural",
			'not_found'          => "No $full_plural found",
			'not_found_in_trash' => "No $full_plural found in the Trash",
			'parent_item_colon'  => '',
			'menu_name'          => $short_plural,
		);
		
		$args = array(
			'labels'        => $labels,
			'description'   => "Holds our $full_plural data",
			'public'        => true,
			'menu_position' => 5,
			'supports'      => array( 'title', 'author' ),
			'taxonomies'    => array(),
			'rewrite'       => array( 'slug' => $slug ),
			'has_archive'   => true,
		);
		
		register_post_type( 'connection', $args );

		
		// Setup Connections Group taxonomy.
		extract( $settings['name']['group'] );
		
		$labels = array(
			'name'              => $full_plural,
			'singular_name'     => $full_single,
			'search_items'      => "Search $full_plural",
			'all_items'         => "All $full_plural",
			'parent_item'       => "Parent $short_single",
			'parent_item_colon' => "Parent $short_single:",
			'edit_item'         => "Edit $short_single",
			'update_item'       => "Update $short_single",
			'add_new_item'      => "Add New $short_single",
			'new_item_name'     => "New $short_single Name",
			'menu_name'         => $short_plural,
		);

		$args = array(
			'hierarchical'      => true,
			'labels'            => $labels,
			'show_ui'           => current_user_can( 'customize' ),
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => $slug ),
		);

		register_taxonomy( 'connection-group', 'connection', $args );


		// Setup Connections Link taxonomy.
		extract( $settings['name']['link'] );
		
		$labels = array(
			'name'                       => $full_plural,
			'singular_name'              => $full_single,
			'search_items'               => "Search $full_plural",
			'popular_items'              => "Popular $full_plural",
			'all_items'                  => "All $full_plural",
			'parent_item'                => null,
			'parent_item_colon'          => null,
			'edit_item'                  => "Edit $short_single",
			'update_item'                => "Update $short_single",
			'add_new_item'               => "Add New $short_single",
			'new_item_name'              => "New $short_single Name",
			'separate_items_with_commas' => "Separate $short_single with commas",
			'add_or_remove_items'        => "Add or remove $short_single",
			'choose_from_most_used'      => "Choose from the most used $short_single",
			'not_found'                  => "No $short_single found.",
			'menu_name'                  => $short_plural,
		);

		$args = array(
			'hierarchical'          => false,
			'labels'                => $labels,
			'show_ui'               => true,
			'show_admin_column'     => true,
			'update_count_callback' => '_update_post_term_count',
			'query_var'             => true,
			'rewrite'               => array( 'slug' => $slug ),
		);

		register_taxonomy( 'connection-link', 'connection', $args );


		flush_rewrite_rules();
	}
	
	
	/**
	 * Updates the messages displayed by the custom post type.
	 * @param  Array  $messages  The messages list.
	 * @return  Array  The altered messages list.
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
	 * Setup the custom meta boxes.
	 */
	public static function add_meta_boxes()
	{
		add_meta_box( 
				'connections_info_box_connections_info',
				'Connection Info',
				array( 'Connections_ConnectionCustomPostType', 'info_box_connections_info' ),
				'connection',
				'normal',
				'high'
		);
		add_meta_box(
			'connections_info_box_imported_content',
			'Content',
			array( 'Connections_ConnectionCustomPostType', 'info_box_imported_content' ),
			'connection',
			'normal',
			'high'
		);
		add_meta_box(
			'connections_info_box_contact_info',
			'Contact Info',
			array( 'Connections_ConnectionCustomPostType', 'info_box_contact_info' ),
			'connection',
			'normal',
			'high'
		);
		add_meta_box( 
				'connections_info_box_synch_data',
				'Connection Type',
				array( 'Connections_ConnectionCustomPostType', 'info_box_synch_data' ),
				'connection',
				'side',
				'high'
		);
	}
	
	
	/**
	 * Writes the HTML code used to create the contents of the Connections Info meta box.
	 * @param  WP_Post  $post  The current post being displayed.
	 */
	public static function info_box_connections_info( $post )
	{
		$entry_method = self::get_entry_method( $post->ID );

		$sort_title = get_post_meta( $post->ID, 'sort-title', true );
		$username = get_post_meta( $post->ID, 'username', true );
		$url = get_post_meta( $post->ID, 'url', true );
		$site_type = get_post_meta( $post->ID, 'site-type', true );
		
		$site_types = array( 'wp' => 'WordPress site', 'rss' => 'RSS feed' );
		if( !array_key_exists($site_type, $site_types) )
			$site_type = 'wp';
			
		if (!current_user_can( "customize" ) ) {
			$access = " readonly";
			$options = " disabled";
		}
			
		?>
		<label for="connections-sort-title">Sort Title</label><br/>
		<input type="text" id="connections-sort-title" name="connections-sort-title" value="<?php echo esc_attr($sort_title); ?>" <?php print $access ?> style="width:100%" /><br/>

		<label for="connections-name">Username</label><br/>
		<input type="text" id="connections-username" name="connections-username" value="<?php echo esc_attr($username); ?>" <?php print $access ?> style="width:100%" /><br/>

		<?php if( $entry_method == 'synch' ): ?>
		
		<label for="connections-url">URL</label><br/>
		<input type="text" id="connections-url" name="connections-url" value="<?php echo esc_attr($url); ?>" <?php print $access ?> style="width:100%" /><br/>

		<label for="connections-site-type">Site Type</label><br/>
		<select name="connections-site-type">
			<?php foreach( $site_types as $name => $value ): ?>
				<option value="<?php echo $name; ?>" <?php echo ($site_type == $name ? 'selected' : ''); ?><?php print $options ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		
		<?php endif; ?>
		
		<?php
	}
	
	
	/**
	 * Writes the HTML code used to create the contents of the Imported Content meta box.
	 * @param  WP_Post  $post  The current post being displayed.
	 */
	public static function info_box_imported_content( $post )
	{
		wp_nonce_field( CONNECTIONS_HUB_PLUGIN_PATH, 'connection-custom-post-type-entry-form' );

		$entry_method = self::get_entry_method( $post->ID );
		$url = get_post_meta( $post->ID, 'url', true );
	
		$search_content = get_post_meta( $post->ID, 'search-content', true );

		if( $entry_method == 'synch' ): ?>
		<textarea id="connections-imported-content" readonly style="width:100%;height:200px;"><?php echo $post->post_content; ?></textarea>
		<?php else: ?>
		<?php wp_editor( $post->post_content, 'content' ); ?>
		<?php endif; ?>
		
		<label for="connections-search-content">Search Content</label><br/>
		<textarea id="connections-search-content" readonly style="width:100%;height:50px;"><?php echo esc_attr($search_content); ?></textarea><br/>
		<?php
	}
	
	
	/**
	 * Writes the HTML code used to create the contents of the Contact Info meta box.
	 * @param  WP_Post  $post  The current post being displayed.
	 */
	public static function info_box_contact_info( $post )
	{
		$entry_method = self::get_entry_method( $post->ID );
		$contact_info = get_post_meta($post->ID, 'contact-info', true);
		$contact_info_filter = get_post_meta($post->ID, 'contact-info-filter', true);
		$url = get_post_meta( $post->ID, 'url', true );
		
		$disabled = '';
		if( $entry_method != 'manual' ) $disabled = 'disabled';

		echo '<div class="contact-info">';
		
		if( $entry_method == 'synch' && empty($contact_info) ) 
			$contact_info = 'No contact info.';

		?>
		<textarea name="connections-contact-info" style="width:100%;height:200px;" <?php echo $disabled; ?>><?php echo $contact_info; ?></textarea>
		<input type="hidden" name="connections-contact-info-filter" value="no" />
		<input type="checkbox" name="connections-contact-info-filter" value="yes" <?php checked( 'yes', $contact_info_filter ); ?> <?php echo $disabled; ?> />
		Automatically add paragraphs
		<?php
		
		echo '</div>';
	}
	
	
	/**
	 * Writes the HTML code used to create the contents of the Synch Data meta box.
	 * @param  WP_Post  $post  The current post being displayed.
	 */
	public static function info_box_synch_data( $post )
	{
		$entry_method = self::get_entry_method( $post->ID );
		if (!current_user_can( "customize" ) ) 
			$options = " disabled";

		?>

		<div style="display:inline;margin-right:10px">
			<input type="radio" name="connection-entry-method-type" value="manual" <?php echo ($entry_method == 'manual' ? 'checked' : $options); ?> />
			Manual
		</div>
		<div style="display:inline;margin-right:10px">
			<input type="radio" name="connection-entry-method-type" value="synch" <?php echo ($entry_method == 'synch' ? 'checked' : $options); ?> />
			Synch
		</div>

		<?php if( $entry_method == 'synch' ): ?>
		<div class="synch-data" style="border:solid 1px #ddd;padding:5px 10px;margin-top:5px;background-color:#fafafa;">
			<?php
			echo Connections_ConnectionCustomPostType::format_synch_data(
				get_post_meta($post->ID, 'synch-data', true)
			);
			?>
		</div>
		<div id="major-publishing-actions" style="margin:-12px;margin-top:10px;">

			<?php if( $post->post_status !== 'publish' ): ?>
				<input name="synch" type="submit" class="button button-primary button-large" id="synch" value="Publish & Synch" style="float:right;">
			<?php else: ?>
				<input name="synch" type="submit" class="button button-primary button-large" id="synch" value="Update & Synch" style="float:right;">
			<?php endif; ?>

			<div class="clear"></div>
		</div>
		<?php endif; ?>
		
		<?php
	}
	
	
	/**
	 * Save the data from the custom meta boxes.
	 * @param  int  $post_id  The post id.
	 * @param  WP_Post  $post  The current post being edited.
	 * @param  bool  $updated  True if updated, otherwise False.
	 */
	public static function save_post_entry_form( $post_id, $post, $updated )
	{
		if( empty($_POST['connection-custom-post-type-entry-form']) )
			return;
		
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
			return;

		if ( !current_user_can('edit_post', $post_id) )
			return;

		if( !wp_verify_nonce($_POST['connection-custom-post-type-entry-form'], CONNECTIONS_HUB_PLUGIN_PATH) )
			return;
		
		$post_data = $_POST;
		$_POST['connection-custom-post-type-entry-form'] = NULL; // Prevent looping

		$entry_method = self::get_entry_method( $post_id );

		
		// Save data
		switch( $entry_method )
		{
			case( 'synch' ):
				self::save_meta_data(
					$post_id,
					$post_data['connections-sort-title'],
					$post_data['connections-username'], 
					$post_data['connections-url'], 
					$post_data['connections-site-type'],
					$post_data['connection-entry-method-type']
				);
				break;
				
			case( 'manual' ):
			default:
				self::save_meta_data(
					$post_id,
					$post_data['connections-sort-title'],
					$post_data['connections-username'], 
					null,
					null,
					$post_data['connection-entry-method-type']
				);
				update_post_meta( $post_id, 'contact-info', $post_data['connections-contact-info'] );
				update_post_meta( $post_id, 'contact-info-filter', $post_data['connections-contact-info-filter'] );
				$search_content = self::generate_search_data( $post_data['content'] );
				update_post_meta( $post_id, 'search-content', $search_content );
				break;
		}
		
		
		// Synch content
		if( !isset($post_data['synch']) ) return;
		
		require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/model/synch-model.php' );
		$synch_model = ConnectionsHub_SynchModel::get_instance();
		$synch_data = $synch_model->get_data($post_id);
		if( $synch_data !== false )
			$synch_model->synch($post_id, $synch_data);
	}


	/**
	 * Save meta data.
	 * @param  int  $post_id  The connections post id.
	 * @param  string  $sort_title  The sort title.
	 * @param  string  $username  The username for connections post.
	 * @param  string  $url  The url for the user's profile site.
	 * @param  string  $site_type  The type of the site.
	 * @param  string  $entry_method  The entry method for the connections post.
	 */
	public static function save_meta_data( $post_id, $sort_title, $username, $url = null, $site_type = null, $entry_method = null )
	{
		// Get current data
		$meta_sort_title = get_post_meta( $post_id, 'sort-title', true );
		$meta_username = get_post_meta( $post_id, 'username', true );
		$meta_auto_synch = get_post_meta( $post_id, 'auto-synch', true );
		$meta_url = get_post_meta( $post_id, 'url', true );
		$meta_site_type = get_post_meta( $post_id, 'site-type', true );
		$meta_entry_method = self::get_entry_method( $post_id );
		

		// Save data
		update_post_meta( $post_id, 'sort-title', $sort_title );
		update_post_meta( $post_id, 'username', $username );
	
		if( $url !== null )
			update_post_meta( $post_id, 'url', $url );
		if( $site_type !== null )
			update_post_meta( $post_id, 'site-type', $site_type );
		if( $entry_method !== null )
			update_post_meta( $post_id, 'entry-method', $entry_method );
		

		// Set author
		if( $user = get_user_by('login', $username) )
		{
			wp_update_post( array('ID' => $post_id, 'post_author' => $user->ID) );
		}
	}
	
		
	/**
	 * Add the link to the 'Synch Connections' in the views portion of the Connections table list.
	 * @param  Array  $views  The list of views links.
	 * @return  Array  The altered list of views links.
	 */
	public static function all_connections_add_synch_button( $views )
	{
		$views['connections-synch'] = '
			<a href="edit.php?post_type=connection&page=synch-connections" title="Synch Connections" style="font-weight:bold">Synch Connections &raquo;</a>
		';
		return $views;
	}


	/**
	 * Add columns to the Connections table list.
	 * @param  Array  $columns  The list of columns in the Connections table list.
	 * @return  Array  The alterd list of columns.
	 */
	public static function all_connections_columns_key( $columns )
	{
		$columns['url'] = 'Source';
		$columns['contact-info'] = 'Contact Info';
		
		return $columns;
	}
	

	/**
	 * Print the content of a cell in the Connections table list.
	 * @param  string  $column_name  The name of the column.
	 * @param  int  $post_id  The post id.
	 */
	public static function all_connections_columns_value( $column_name, $post_id )
	{
		switch( $column_name )
		{
			case 'url':
				switch( self::get_entry_method($post_id) )
				{
					case( 'synch' ):
						echo '<div class="synch-entry">';
						
						$url = get_post_meta( $post_id, 'url', true );
						if( $url === '' ) echo 'Invalid URL<br/>';
						else echo '<a href="'.$url.'" target="_blank">'.$url.'</a><br/>';
						
						$site_type = get_post_meta( $post_id, 'site-type', true );
						switch($site_type)
						{
							case 'rss': echo 'RSS Feed'; break;
							case 'wp': echo 'WordPress Site'; break;
							default: echo 'Unknown'; break;
						}
						echo '</div>';
						break;
					
					case( 'manual' ):
					default:
						echo '<div class="manual-entry">Manual Entry</div>';
						break;
				}
				break;
			
			case 'contact-info':
				$contact_info = get_post_meta($post_id, 'contact-info', true);
				
				if( get_post_meta($post_id, 'contact-info-filter', true) == 'yes' )
					$contact_info = wpautop( $contact_info );
				
				echo $contact_info;
				break;
		}
	}
	
	
	/**
	 * Format the synch data for display.
	 * @param  Array  $data  The synch data.
	 * @return  string  The formatted synch data.
	 */
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
				if( !is_string($value) )
					$value = print_r($value, true);
				
				if( ($key === 'view-url') && (!empty($value)) )
					$value = '<a href="'.$value.'" target="_blank">'.$value.'</a>';
				
				$key = ucwords( str_replace('-', ' ', $key) );
				
				$sd .= '<strong>'.$key.':</strong> '.$value.'<br/>';
			}
		}
		
		return $sd;
	}
	
	
	/**
	 * Get the entry method of the Connections post.
	 * @param  int  $post_id  The post id.
	 * @return  string  The entry method for the Connections post.
	 */
	public static function get_entry_method( $post_id )
	{
		$entry_method = get_post_meta( $post_id, 'entry-method', true );
		if( $entry_method === '' ) $entry_method = 'manual';
		return $entry_method;
	}


	/**
	 * Generate search content without special and extra characters.
	 * @param  string  $content  The post content.
	 * @return  string  The generated search content.
	 */
	public static function generate_search_data( $content )
	{
		$search_content = strip_tags($content);
		$search_content = html_entity_decode($search_content, ENT_COMPAT, 'UTF-8');
		$search_content = preg_replace( '/  /', ' ', $search_content );
		$search_content = preg_replace( '/\n\n+/', "\n", $search_content );
		$search_content = preg_replace( '/(\r\n)(\r\n)+/', "\n", $search_content );

		return $search_content;
	}
}
endif;

