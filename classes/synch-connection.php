<?php


class ConnectionsHub_SynchConnection
{
	public static $last_error = '';


	/* */
	private function __construct() { }
	

	public static function get_data( $connection_post_id )
	{
		$connections_post = get_post( $connection_post_id );
		
		if( empty($connections_post) )
		{
			self::$last_error = 'Unable to retrieve Connections Post #'.$connection_post_id.'.';
			return false;
		}
		
		$url = get_post_meta( $connection_post_id, 'url', true );
		$site_type = get_post_meta( $connection_post_id, 'site-type', true );

		$actions = array();
		switch( $site_type )
		{
			case 'wp':
				$actions[] = 'wp_plugin';
				$actions[] = 'wp_local_post';
				break;
			case 'rss':
				$actions[] = 'rss_feed';
				break;
			default:
				self::$last_error = 'Unknown site type: "'.$site_type.'"';
				return false;
				break;
		}
		
		$result = false;
		foreach( $actions as $action )
		{
			$result = call_user_func_array( 
				array( 'ConnectionsMainSite_SynchConnection', 'get_'.$action.'_data' ),
				array( $connection_post_id, $url, $update_connection )
			);
			
			if( $result === false ) break;
		}
		
		if( $result !== null )
		{
			self::$last_error = 'Unable to contact site.';
			return false;
		}
		
		return $result;
	}
	
	
	public static function synch( $connection_post_id, &$data )
	{
		$content = ( !empty($data['content']) ? $data['content'] : '' );
		$search_content = preg_replace( "/[^A-Za-z0-9 ]/", '', $content );

		$synch_data = array(
			'post_id' => ( !empty($data['post_id']) ? $data['post_id'] : 'Not specified' ),
			'post_type' => ( !empty($data['post_type']) ? $data['post_type'] : 'Not specified' ),
			'last_update' => ( !empty($data['last_update']) ? $data['last_update'] : 'Not specified' ),
			'last_author' => ( !empty($data['last_author']) ? $data['last_author'] : 'Not specified' ),
			'view_url' => ( !empty($data['view_url']) ? $data['view_url'] : '' ),
		);

		$synch_data = array_merge( $synch_data, $data );
		if( isset($synch_data['content']) ) unset($synch_data['content']);

		wp_update_post( array( 'ID' => $connection_post_id, 'post_content' => $content ) );
		update_post_meta( $id, 'search-content', $search_content );
		update_post_meta( $id, 'synch-data', $synch_data );
	}


	/**
	 *
	 */
	private static function get_wp_plugin_data( $id, $url )
	{
		// Test URL
		// fix url?
		// $plugin_page_url = 'http://uncc:emergencybread@clas-incubator-wp.uncc.edu/felix-germain/?connections-site-synchonizer-api=get-site';
		$plugin_page_url = $url . '?connections-site-synchonizer-api=get-site';
		
		$context = stream_context_create(
			array(
				'http' => array(
					'method' => 'GET',
					'header' => "Accept-language: en\r\n",
					'timeout' => 5,
				)
			)
		);

		$page_contents = @file_get_contents( $plugin_page_url, false, $context );
		if( empty($page_contents) ) return false;

		$site_data = json_decode( $page_contents );
		if( $site_data === false ) return false;

		if( !isset($site_data['post_id']) ) return false;
		return $site_data;
	}


	/**
	 * 
	 */
	private static function get_wp_local_post_data( $id, $url )
	{
		global $wpdb;
		$blog_id = -1;
		
		$host = parse_url($this->url, PHP_URL_HOST);
		$path = parse_url($this->url, PHP_URL_PATH);
		$path_parts = array_filter( explode('/', $path), 'strlen' );
		$path = implode( '/', $path_parts );

		$sql = "SELECT blog_id FROM $wpdb->blogs WHERE domain = %s AND path = %s";

		if( strlen($path) == 0 )
		{
			$query = $wpdb->prepare( $sql, $host, '/'.$path.'/' );
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
		
		if( $blog_id == -1 ) return false;
		
		switch_to_blog( $blog_id );
				
		$wppost = null;
		$post_id = url_to_postid($this->url);
		
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
				$last_user = get_userdata($last_id);
				$last_author = apply_filters('the_modified_author', $last_user->display_name);
			}

			$synch_data = array(
				'blog_id' => $blog_id,
				'post_id' => $wppost->ID,
				'content' => $wppost->post_content,
				'last_update' => $wppost->post_modified,
				'last_author' => $last_author,
				'view_url' => $view_url,
			);
		}
		
		restore_current_blog();

		if( !empty($wppost) ) return false;

		return true;
	}


	/**
	 *
	 */
	private static function get_rss_feed_data( $id, $url )
	{
		return false;
	}

}

