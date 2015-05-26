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
//add_filter( 'pre_get_posts', array('Connections_ConnectionCustomPostType', 'alter_connections_query') );
add_filter( 'views_edit-connection', array( 'Connections_ConnectionCustomPostType', 'all_connections_add_synch_button' ) );
add_filter( 'manage_edit-connection_columns', array( 'Connections_ConnectionCustomPostType', 'all_connections_columns_key' ) );
add_action( 'manage_connection_posts_custom_column', array( 'Connections_ConnectionCustomPostType', 'all_connections_columns_value' ), 10, 2 );


class Connections_ConnectionCustomPostType
{

	protected static $_settings = null;

	/**
	 * Constructor.
	 * Private.  Class only has static members.
	 * TODO: look up PHP abstract class implementation.
	 */
	private function __construct() { }


	public static function change_slugs() {}

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
//		apl_print($settings, 'db setting');
//		delete_option( CONNECTIONS_HUB_OPTIONS );

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
			foreach( $default['name'] as $section_name => &$section )
			{
				if( !isset($settings['name'][$section_name]) )
				{
					$settings['name'][$section_name] = $section;
					continue;
				}
				
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
				
		self::$_settings = $settings;
//		apl_print(self::$_settings, 'complete settings');

		return self::$_settings;
	}

	/**
	 * Creates the custom Connection post type.
	 */	
	public static function create_custom_post()
	{
		$settings = self::get_settings();
//  	apl_print($settings);
		
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

		extract( $settings['name']['group'] );

		// Add new taxonomy, make it hierarchical (like categories)
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
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'rewrite'           => array( 'slug' => $slug ),
		);

		register_taxonomy( 'connection-group', 'connection', $args );

		extract( $settings['name']['link'] );

		// Add new taxonomy, NOT hierarchical (like tags)
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
	 * Writes the HTML code used to create the contents of the Event meta box.
	 * @param WP_Post The current post being displayed.
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
			
		?>
		<label for="connections-sort-title">Sort Title</label><br/>
		<input type="text" id="connections-sort-title" name="connections-sort-title" value="<?php echo esc_attr($sort_title); ?>" style="width:100%" /><br/>

		<label for="connections-name">Username</label><br/>
		<input type="text" id="connections-username" name="connections-username" value="<?php echo esc_attr($username); ?>" style="width:100%" /><br/>

		<?php if( $entry_method == 'synch' ): ?>
		
		<label for="connections-url">URL</label><br/>
		<input type="text" id="connections-url" name="connections-url" value="<?php echo esc_attr($url); ?>" style="width:100%" /><br/>

		<label for="connections-site-type">Site Type</label><br/>
		<select name="connections-site-type">
			<?php foreach( $site_types as $name => $value ): ?>
				<option value="<?php echo $name; ?>" <?php echo ($site_type == $name ? 'selected' : ''); ?>><?php echo $value; ?></option>
			<?php endforeach; ?>
		</select>
		
		<?php endif; ?>
		
		<?php
	}
	
	
	/**
	 * 
	 */
	public static function info_box_imported_content( $post )
	{
		wp_nonce_field( CONNECTIONS_HUB_PLUGIN_PATH, 'connection-custom-post-type-entry-form' );

		$entry_method = self::get_entry_method( $post->ID );
	
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
	
	
	public static function info_box_contact_info( $post )
	{
		$entry_method = self::get_entry_method( $post->ID );

		if( $entry_method == 'synch' )
		{
			$contact_info = get_post_meta($post->ID, 'contact-info', true);
			if( empty($contact_info) ) $contact_info = 'No contact info.';

			?>
			<div class="contact-info"><?php echo $contact_info; ?></div>
			<?php
		}
		else
		{
			$contact_phone = get_post_meta($post->ID, 'contact-phone', true);
			$contact_email = get_post_meta($post->ID, 'contact-email', true);
			$contact_location = get_post_meta($post->ID, 'contact-location', true);
			
			?>
			<div class="contact-info">
			<label for="connections-contact-location">Location</label><br/>
			<input type="text" id="connections-contact-location" name="connections-contact-location" value="<?php echo esc_attr($contact_location); ?>" style="width:100%" /><br/>
			<label for="connections-contact-phone">Phone</label><br/>
			<input type="text" id="connections-contact-phone" name="connections-contact-phone" value="<?php echo esc_attr($contact_phone); ?>" style="width:100%" /><br/>
			<label for="connections-contact-email">Email</label><br/>
			<input type="text" id="connections-contact-email" name="connections-contact-email" value="<?php echo esc_attr($contact_email); ?>" style="width:100%" /><br/>
			</div>
			<?php
		}
		?>

		
		<?php



	}
	
	
	/**
	 * Writes the HTML code used to create the contents of the Event meta box.
	 * @param WP_Post The current post being displayed.
	 */
	public static function info_box_synch_data( $post )
	{
		$entry_method = self::get_entry_method( $post->ID );
		?>

		<div style="display:inline;margin-right:10px">
			<input type="radio" name="connection-entry-method-type" value="manual" <?php echo ($entry_method == 'manual' ? 'checked' : ''); ?> />
			Manual
		</div>
		<div style="display:inline;margin-right:10px">
			<input type="radio" name="connection-entry-method-type" value="synch" <?php echo ($entry_method == 'synch' ? 'checked' : ''); ?> />
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

		if( !wp_verify_nonce($_POST['connection-custom-post-type-entry-form'], CONNECTIONS_HUB_PLUGIN_PATH) )
			return;
		
		$post_data = $_POST;
		unset($_POST); // prevent looping...

		$entry_method = self::get_entry_method( $post_id );

		//
		// Save data
		//
		switch( $entry_method )
		{
			case( 'synch' ):
				// save sort title, username, url, site type
				// save entry method
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
				// create search content and save
				// save sort title and username
				// save contact phone, email, location
				// save entry method
				self::save_meta_data(
					$post_id,
					$post_data['connections-sort-title'],
					$post_data['connections-username'], 
					null,
					null,
					$post_data['connection-entry-method-type']
				);
				self::save_contact_info(
					$post_id,
					$post_data['connections-contact-phone'],
					$post_data['connections-contact-email'],
					$post_data['connections-contact-location']
				);
				$search_content = self::generate_search_data( $post_data['content'] );
				update_post_meta( $post_id, 'search-content', $search_content );
				break;
		}
		
		
		//
		// Synch content
		//
		if( !isset($post_data['synch']) ) return;
		
		require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/model/synch-model.php' );
		$synch_model = ConnectionsHub_SynchModel::get_instance();
		$synch_data = $synch_model->get_data($post_id);
		if( $synch_data !== false )
			$synch_model->synch($post_id, $synch_data);
	}


	/**
	 * 
	 */
	public static function save_meta_data( $post_id, $sort_title, $username, $url = null, $site_type = null, $entry_method = null )
	{
		//
		// Get current data
		//
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
		
		// set author
		if( $user = get_user_by('login', $username) )
		{
			wp_update_post( array('ID' => $post_id, 'post_author' => $user->ID) );
		}
	}
	
	
	public static function save_contact_info( $post_id, $phone, $email, $location )
	{
		// Save data
		if( $phone !== null )
			update_post_meta( $post_id, 'contact-phone', $phone );
		if( $email !== null )
			update_post_meta( $post_id, 'contact-email', $email );
		if( $location !== null )
			update_post_meta( $post_id, 'contact-location', $location );
	}
	

	public static function alter_connection_query( $wp_query )
	{
		if( !is_admin() ) return;
		if( !$wp_query->is_main_query() ) return;

		$screen = get_current_screen();
		if( $screen->base != 'edit' ) return;

		if( $wp_query->post_type !== 'connection' ) return;
		
		$wp_query->set( 'posts_per_page', get_user_option('edit_connection_per_page') );

// 		if( ($wp_query->get('orderby')) && ($wp_query->get('orderby') != 'datetime') ) return;
// 		
// 		$wp_query->set( 'meta_key', 'datetime' );
// 		$wp_query->set( 'orderby', 'meta_value' );
	}
	
		
	/**
	 * 
	 */
	public static function all_connections_add_synch_button( $views )
	{
		$views['connections-synch'] = '
			<a href="edit.php?post_type=connection&page=synch-connections" title="Synch Connections" style="font-weight:bold">Synch Connections &raquo;</a>
		';
		return $views;
	}


	/**
	 * 
	 */
	public static function all_connections_columns_key( $columns )
	{
// 		unset($columns['taxonomy-connection-link']);

		$columns['url'] = 'Source';
		//$columns['synch'] = 'Synch Data';

		/*
		-connections links
		-email address
		-phone number
		-location
		*/
		
// 		$columns['taxonomy-connection-link'] = 'Connection Links';
		$columns['email'] = 'Email';
		$columns['phone'] = 'Phone';
		$columns['location'] = 'Location';
		
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
				switch( self::get_entry_method($post_id) )
				{
					case( 'synch' ):
						echo '<div class="synch-entry">';
						
						$url = get_post_meta( $post_id, 'url', true );
// 						$url = connections_fix_url( $url );
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
			
			case 'email':
				$contact_email = get_post_meta($post_id, 'contact-email', true);
				echo $contact_email;
				break;
			
			case 'phone':
				$contact_phone = get_post_meta($post_id, 'contact-phone', true);
				echo $contact_phone;
				break;
			
			case 'location':
				$contact_location = get_post_meta($post_id, 'contact-location', true);
				echo $contact_location;
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
	
	
	public static function get_entry_method( $post_id )
	{
		$entry_method = get_post_meta( $post_id, 'entry-method', true );
		if( $entry_method === '' ) $entry_method = 'manual';
		return $entry_method;
	}


	public static function generate_search_data( $content )
	{
// 		$tags = array('<p>','</p>','<br />','<br/>','<br>','</li>','</ol>','</ul>','<hr />','<hr/>','<hr>','</h1>','</h2>','</h3>','</h4>','</h5>','</h6>');
// 		$search_content = str_replace( $tags, "\n", $content);
		$search_content = strip_tags($content);
		$search_content = html_entity_decode($search_content, ENT_COMPAT, 'UTF-8');
// 		$search_content = preg_replace( "/[^A-Za-z0-9\n ]/", ' ', $search_content );
		$search_content = preg_replace( '/  /', ' ', $search_content );
		$search_content = preg_replace( '/\n\n+/', "\n", $search_content );
		$search_content = preg_replace( '/(\r\n)(\r\n)+/', "\n", $search_content );
// 		$search_content = explode( '\n', $search_content );
// 		for( $i = 0; $i < count($search_content); $i++ )
// 		{
// 			$search_content[$i] = trim($search_content[$i]);
// 			if( empty($search_content[$i]) )
// 			{
// 				$search_content[$i] = 'need to remove';
// 				//array_splice( $search_content, $i, 1 );
// 				//$i--;
// 			}
// 		}
// 		$search_content = implode( '\n', $search_content );
// 		$search_content = htmlentities2utf8( $content );

		return $search_content;
	}



}


