<?php

/**
 * ConnectionsHub_SynchModel
 * 
 * The synch model for the Connections Hub plugin.
 * 
 * @package    connections-hub
 * @subpackage classes/model
 * @author     Crystal Barton <cbarto11@uncc.edu>
 */

if( !class_exists('ConnectionsHub_SynchModel') ):
class ConnectionsHub_SynchModel
{
	
	private static $instance = null;	// The only instance of this class.

	public $model = null;				// The main model.
	
	public $last_error = null;			// The error logged by a model.
	protected $write_log = true;		// 
	
	
	/**
	 * Private Constructor.  Needed for a Singleton class.
	 * Creates an ConnectionsHub_SynchModel object.
	 */
	protected function __construct()
	{
		$this->model = ConnectionsHub_Model::get_instance();
	}
	

	/**
	 * Get the only instance of this class.
	 * @return  ConnectionsHub_Model  A singleton instance of the model class.
	 */
	public static function get_instance()
	{
		if( self::$instance	=== null )
		{
			self::$instance = new ConnectionsHub_SynchModel();
		}
		return self::$instance;
	}
	
	
	/**
	 * Write a line to a log file.
	 * @param  string  $text      The line of text to insert into the log.
	 * @param  bool    $newline   True if a new line character should be inserted after
	 *                            the line, otherwise False.
	 */
	public function write_to_log( $text = '', $newline = true )
	{
		if( !$this->write_log ) return;
		$this->model->write_to_log( $text, $newline );
	}


	/**
	 * Synch all Connections that are have a synch entry method.
	 * @param   bool  $write_to_log  True if the a log file should be written.
	 */
	public function synch_all_connections( $write_to_log = false )
	{
		$this->write_log = $write_to_log;
		
		$this->write_to_log( '-----------------------------------------------------' );
		$this->write_to_log( ' START SYNCHING CONNECTIONS   '.date('m-d-Y h:i:s A') );
		$this->write_to_log( '-----------------------------------------------------' );
		$this->write_to_log();
		$this->write_to_log();

		$this->write_to_log( 'Retreiving all Connections...', false );
		$connections = $this->get_synching_connections();
		$this->write_to_log( 'done.' );
		$this->write_to_log( count($connections).' Connections found.' );
		$this->write_to_log();
		
		foreach( $connections as $connection )
		{
			$this->write_to_log( $connection['post-id']." - ".$connection['name'] );
			$this->write_to_log( 'Synching...', false );
			
			$data = $this->get_data( $connection['post-id'] );
			
			if( $data === false )
			{
				$this->write_to_log( 'ERROR.' );
				$this->write_to_log( $this->last_error );
				$this->write_to_log();
				continue;
			}
			
			$this->synch( $connection['post-id'], $data );
			
			$this->write_to_log( 'done.' );
			$this->write_to_log();
		}
		
		$this->write_to_log();
		$this->write_to_log( '-----------------------------------------------------' );
		$this->write_to_log( ' DONE SYNCHING CONNECTIONS    '.date('m-d-Y h:i:s A') );
		$this->write_to_log( '-----------------------------------------------------' );
	}
	
	
	/**
	 * Gets all Connections that have a synch entry method.
	 * @return  array  All synch connections.
	 */
	public function get_synching_connections()
	{
		$connections = array();
		
		$wpquery = new WP_Query(
			array(
				'post_type'			=> 'connection',
				'post_status'		=> 'publish',
				'posts_per_page'	=> -1,
				'meta_key'			=> 'entry-method',
				'meta_value'		=> 'synch',
			)
		);
		
		while( $wpquery->have_posts() )
		{
			$wpquery->the_post();
			$post = get_post();
			
			$connections[] = array(
				'name'       => $post->post_title,
// 				'url'        => connections_fix_url( get_post_meta( $post->ID, 'url', true ) ),
				'url'        => get_post_meta( $post->ID, 'url', true ),
				'site-type'  => get_post_meta( $post->ID, 'site-type', true ),
				'synch-data' => get_post_meta( $post->ID, 'synch-data', true ),
				'post-id'    => $post->ID,
			);
		}
		
		wp_reset_query();
		
		return $connections;
	}


	/**
	 * Get the complete data for a Connectios post.
	 * @param   int         $connections_post_id  The id of the Connections post.
	 * @retrun  array|bool  The connections post's data on success, otherwise false.
	 */
	public function get_data( $connection_post_id )
	{
		$connections_post = get_post( $connection_post_id );
		
		if( empty($connections_post) )
		{
			$this->last_error = 'Unable to retrieve Connections Post #'.$connection_post_id.'.';
			return false;
		}
		
		$url = get_post_meta( $connection_post_id, 'url', true );
		
		if( filter_var($url, FILTER_VALIDATE_URL) === false )
		{
			$this->last_error = 'Not a valid URL.';
			return false;
		}
		
		//TODO....
		//$url = connections_fix_url( $url );
		$site_type = get_post_meta( $connection_post_id, 'site-type', true );

		$actions = array();
		switch( $site_type )
		{
			case 'wp':
				$actions['wp_plugin'] = 'WP Plugin';
				$actions['wp_local_post'] = 'WP Local Post';
				break;
			case 'rss':
				$actions['rss_feed'] = 'RSS Feed';
				break;
			default:
				$this->last_error = 'Unknown site type: "'.$site_type.'"';
				return false;
				break;
		}
		
		$result = false;
		foreach( $actions as $action => $name )
		{
			$result = call_user_func_array( 
				array( $this, 'get_'.$action.'_data' ),
				array( $connection_post_id, $url )
			);
			
			if( $result !== false ) 
			{
				$result['update-type'] = $name;
				$result['update-date'] = date('Y-m-d H:i:s');
				
				if( isset($result['plugin-version']) )
				{
					$result['update-type'] .= ' v'.$result['plugin-version'];
					unset($result['plugin-version']);
				}
				break;
			}
		}
		
		if( $result === false )
		{
			$this->last_error = 'Unable to contact site.';
			return false;
		}
		
		return $result;
	}
	
	
	/**
	 * Update the Connections post with the new data.
	 * @param   int    $connection_post_id  The Connections post id.
	 * @param   array  $data                The new data.
	 */
	public function synch( $connection_post_id, &$data )
	{
		// generate search data based on content.
		$search_content = '';
		$content = '';
		if( isset($data['content']) )
		{
			$search_content = Connections_ConnectionCustomPostType::generate_search_data( $data['content'] );
			$content = $data['content'];
			unset($data['content']);
		}
		
		// merge data with default data.
		$default_data = array(
			'blog-id' => ( !empty($data['blog-id']) ? $data['blog-id'] : 'n/a' ),
			'post-id' => ( !empty($data['post-id']) ? $data['post-id'] : 'not specified' ),
			'last-modified' => ( !empty($data['last-modified']) ? $data['last-modified'] : 'not specified' ),
			'last-author' => ( !empty($data['last-author']) ? $data['last-author'] : 'not specified' ),
			'view-url' => ( !empty($data['view-url']) ? $data['view-url'] : '' ),
			'update-date' => ( !empty($data['update-date']) ? $data['update-date'] : date('Y-m-d H:i:s') ),
			'update-type' => ( !empty($data['update-type']) ? $data['update-type'] : '' ),
		);
		$data = array_merge( $default_data, $data );
		
		// seperate out the contact information.
		$contact_info = null;
		if( isset($data['contact-info']) )
		{
			$contact_info = $data['contact-info'];
			unset($data['contact-info']);
		}
		
		// update the Connection post.
		wp_update_post( array( 'ID' => $connection_post_id, 'post_content' => $content ) );
		update_post_meta( $connection_post_id, 'search-content', $search_content );
		update_post_meta( $connection_post_id, 'synch-data', $data );
		
		// update the Connection post's contact information, if necessary.
		if( $contact_info !== null )
			update_post_meta( $connection_post_id, 'contact-info', $contact_info );
	}


	/**
	 * Contact a WordPress site's Connection Spoke plugin and get synch data.
	 * @param   int     $id   The Connections post's id.
	 * @param   string  $url  The url of the Wordpress site.
	 * @return  string  The output of the plugin.
	 */
	public function get_wp_plugin_data( $id, $url )
	{
		// determine the Connections Spoke's url.
		if( substr($url,-1) !== '/' ) $url .= '/';
		$plugin_page_url = $url . '?connections-spoke-api=get-site';
		
		// contact site for Connections Spoke data.
		$context = stream_context_create(
			array(
				'http' => array(
					'method' => 'GET',
					'header' => "Accept-language: en\r\n",
					'timeout' => 5,
				)
			)
		);
		
		// get data.
		$page_contents = @file_get_contents( $plugin_page_url, false, $context );
		if( empty($page_contents) ) return false;
		
		// parse data.
		$site_data = json_decode( $page_contents, true );
		if( $site_data === false ) return false;
		
		// check for unsuccessful status.
		if( (!isset($site_data['status'])) || ($site_data['status'] !== true) )
			return false;
		
		return $site_data['output'];
	}
	
	
	/**
	 * Gets the contents of the page/post of a local WordPress site.
	 * @param   int     $id   The Connections post's id.
	 * @param   string  $url  The url of the Wordpress site.
	 * @return  string  The content of the page/post of the site.
	 */
	public function get_wp_local_post_data( $id, $url )
	{
		global $wpdb;
		$blog_id = -1;
		
		// parse the host and path from the url.
		$host = parse_url($url, PHP_URL_HOST);
		$path = parse_url($url, PHP_URL_PATH);
		$path_parts = array_filter( explode('/', $path), 'strlen' );
		$path = implode( '/', $path_parts );
		
		// search blogs table for site.
		$sql = "SELECT blog_id FROM $wpdb->blogs WHERE domain = %s AND path = %s";

		if( strlen($path) == 0 )
		{
			$query = $wpdb->prepare( $sql, $host, '/' );
			$blog_id = $wpdb->get_var( $query, 0, 0 );
			
			if( $blog_id == null ) $blog_id = -1;
		}
		else
		{
			while( strlen($path) > 0 )
			{
				$query = $wpdb->prepare( $sql, $host, '/'.$path.'/' );
				$blog_id = $wpdb->get_var( $query, 0, 0 );

				if( $blog_id == null ) $blog_id = -1;
				else break;
				
				array_pop( $path_parts );
				$path = implode( '/', $path_parts );
			}
		}
		
		// blog not found.
		if( $blog_id == -1 ) return false;
		
		switch_to_blog( $blog_id );
		
		$wppost = null;
		$post_id = url_to_postid($url);
		
		if( !empty($post_id) )
		{
			$wppost = get_post($post_id);
		}
		else
		{
			switch( get_option('show_on_front') )
			{
				case 'page':
					$id = get_option('page_on_front');
					if( !empty($id) ) $wppost = get_post( $id );
					break;

				case 'posts':
				default:
					$query = new WP_Query(
						array(
							'post_type' => 'post',
							'post_status' => 'publish',
							'posts_per_page' => 1
						)
					);
					if( $query->have_posts() )
					{
						$query->the_post(); $wppost = get_post();
					}
					break;
			}
		}
		
		$synch_data = null;
		if( !empty($wppost) )
		{
			$post_id = $wppost->ID;
			
			$last_author = '';
			if( $last_id = get_post_meta( $post_id, '_edit_last', true) )
			{
				$last_user = get_userdata( $last_id );
				if( $last_user )
					$last_author = apply_filters('the_modified_author', $last_user->display_name);
			}
			
			$synch_data = array(
				'blog-id' => $blog_id,
				'post-id' => $wppost->ID,
				'content' => $wppost->post_content,
				'last-modified' => $wppost->post_modified,
				'last-author' => $last_author,
				'view-url' => get_permalink($post_id),
				'contact-info' => self::get_contact_me_contents(),
			);
		}
		
		restore_current_blog();

		if( empty($wppost) ) return false;

		return $synch_data;
	}
	
	
	/**
	 * Parse the content of the widget titled "Contact Me".
	 * @return  string|null  The string of the "Contact Me" text on success, otherwise null.
	 */
	public function get_contact_me_contents()
	{
		global $wpdb;
		
		$widgets = get_option( 'widget_text', null );
		if( (!$widgets) || !is_array($widgets) ) return null;

		$text = null;
		foreach( $widgets as $widget )
		{
			if( !is_array($widget) ) break;;
			
			if( (isset($widget['title'])) && ($widget['title'] == 'Contact Me') )
			{
				$text = $widget['text'];
				
				if( $widget['filter'] )
					$text = wpautop($text);

				break;
			}
		}
		
		return $text;
	}	
	
	
	/**
	 * Gets the contents of the main post of an RSS feed.
	 * @param   int     $id   The Connections post's id.
	 * @param   string  $url  The url of the RSS feed.
	 * @return  string  The content of the main RSS post.
	 */
	public function get_rss_feed_data( $id, $url )
	{
		return false;
	}
	
	
} // class ConnectionsHub_SynchModel
endif; // if( !class_exists('ConnectionsHub_SynchModel') ):

