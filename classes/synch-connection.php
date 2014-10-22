<?php


class ConnectionsHub_SynchConnection
{
	public static $last_error = '';


	/* */
	private function __construct() { }
	

	
	
	public static function get_connections()
	{
		$connections = array();
		
		$wpquery = new WP_Query(
			array(
				'post_type'   => 'connection',
				'post_status' => 'publish',
				'posts_per_page' => -1,
				'meta_key' => 'entry-method',
				'meta_value' => 'synch',
			)
		);
		
		while( $wpquery->have_posts() )
		{
			$wpquery->the_post();
			$post = get_post();
			
			$connections[] = array(
				'name'       => $post->post_title,
				'url'        => connections_fix_url( get_post_meta( $post->ID, 'url', true ) ),
				'site-type'  => get_post_meta( $post->ID, 'site-type', true ),
				'synch-data' => get_post_meta( $post->ID, 'synch-data', true ),
				'post-id'    => $post->ID,
			);
		}
		
		return $connections;
	}
	
	
	public static function get_data( $connection_post_id )
	{
		$connections_post = get_post( $connection_post_id );
		
		if( empty($connections_post) )
		{
			self::$last_error = 'Unable to retrieve Connections Post #'.$connection_post_id.'.';
			return false;
		}
		
		$url = get_post_meta( $connection_post_id, 'url', true );
		
		if( filter_var($url, FILTER_VALIDATE_URL) === false )
		{
			self::$last_error = 'Not a valid URL.';
			return false;
		}
		
		$url = connections_fix_url( $url );
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
				self::$last_error = 'Unknown site type: "'.$site_type.'"';
				return false;
				break;
		}
		
		$result = false;
		foreach( $actions as $action => $name )
		{
			$result = call_user_func_array( 
				array( 'ConnectionsHub_SynchConnection', 'get_'.$action.'_data' ),
				array( $connection_post_id, $url, $update_connection )
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
			self::$last_error = 'Unable to contact site.';
			return false;
		}
		
		return $result;
	}
	
	
	public static function synch( $connection_post_id, &$data )
	{
		$content = ( !empty($data['content']) ? $data['content'] : '' );
		$search_content = Connections_ConnectionCustomPostType::generate_search_data( $content );

		$synch_data = array(
			'blog-id' => ( !empty($data['blog-id']) ? $data['blog-id'] : 'n/a' ),
			'post-id' => ( !empty($data['post-id']) ? $data['post-id'] : 'not specified' ),
			'last-modified' => ( !empty($data['last-modified']) ? $data['last-modified'] : 'not specified' ),
			'last-author' => ( !empty($data['last-author']) ? $data['last-author'] : 'not specified' ),
			'view-url' => ( !empty($data['view-url']) ? $data['view-url'] : '' ),
			'update-date' => ( !empty($data['update-date']) ? $data['update-date'] : date('Y-m-d H:i:s') ),
			'update-type' => ( !empty($data['update-type']) ? $data['update-type'] : '' ),
		);
		
		$data = array_merge( $synch_data, $data );

		if( isset($data['content']) )
		{
			unset($data['content']);
		}
		
		$contact_info = null;
		if( isset($data['contact-info']) )
		{
			$contact_info = $data['contact-info'];
			unset($data['contact-info']);
		}

		wp_update_post( array( 'ID' => $connection_post_id, 'post_content' => $content ) );
		update_post_meta( $connection_post_id, 'search-content', $search_content );
		update_post_meta( $connection_post_id, 'synch-data', $data );

		if( $contact_info !== null )
			update_post_meta( $connection_post_id, 'contact-info', $contact_info );
	}


	/**
	 *
	 */
	private static function get_wp_plugin_data( $id, $url )
	{
		// Test URL
		// fix url?
		// $plugin_page_url = 'http://uncc:emergencybread@clas-incubator-wp.uncc.edu/felix-germain/?connections-site-synchonizer-api=get-site';
		if( substr($url,-1) !== '/' ) $url .= '/';
		$plugin_page_url = $url . '?connections-spoke-api=get-site';

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

		$site_data = json_decode( $page_contents, true );

		if( $site_data === false ) return false;

		if( !isset($site_data['status']) ) return false;
		if( $site_data['status'] !== true ) return false;

		return $site_data['output'];
	}


	private static function printpre( $text )
	{
		echo "<pre>$text</pre>";
	}
	
	
	/**
	 * 
	 */
	private static function get_wp_local_post_data( $id, $url )
	{
		global $wpdb;
		$blog_id = -1;
		
		$host = parse_url($url, PHP_URL_HOST);
		$path = parse_url($url, PHP_URL_PATH);
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
				$last_user = get_userdata($last_id);
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


	private static function get_contact_me_contents()
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
	 *
	 */
	private static function get_rss_feed_data( $id, $url )
	{
		return false;
	}

}



function chr_utf8($code) 
    { 
        if ($code < 0) return false; 
        elseif ($code < 128) return chr($code); 
        elseif ($code < 160) // Remove Windows Illegals Cars 
        { 
            if ($code==128) $code=8364; 
            elseif ($code==129) $code=160; // not affected 
            elseif ($code==130) $code=8218; 
            elseif ($code==131) $code=402; 
            elseif ($code==132) $code=8222; 
            elseif ($code==133) $code=8230; 
            elseif ($code==134) $code=8224; 
            elseif ($code==135) $code=8225; 
            elseif ($code==136) $code=710; 
            elseif ($code==137) $code=8240; 
            elseif ($code==138) $code=352; 
            elseif ($code==139) $code=8249; 
            elseif ($code==140) $code=338; 
            elseif ($code==141) $code=160; // not affected 
            elseif ($code==142) $code=381; 
            elseif ($code==143) $code=160; // not affected 
            elseif ($code==144) $code=160; // not affected 
            elseif ($code==145) $code=8216; 
            elseif ($code==146) $code=8217; 
            elseif ($code==147) $code=8220; 
            elseif ($code==148) $code=8221; 
            elseif ($code==149) $code=8226; 
            elseif ($code==150) $code=8211; 
            elseif ($code==151) $code=8212; 
            elseif ($code==152) $code=732; 
            elseif ($code==153) $code=8482; 
            elseif ($code==154) $code=353; 
            elseif ($code==155) $code=8250; 
            elseif ($code==156) $code=339; 
            elseif ($code==157) $code=160; // not affected 
            elseif ($code==158) $code=382; 
            elseif ($code==159) $code=376; 
        } 
        if ($code < 2048) return chr(192 | ($code >> 6)) . chr(128 | ($code & 63)); 
        elseif ($code < 65536) return chr(224 | ($code >> 12)) . chr(128 | (($code >> 6) & 63)) . chr(128 | ($code & 63)); 
        else return chr(240 | ($code >> 18)) . chr(128 | (($code >> 12) & 63)) . chr(128 | (($code >> 6) & 63)) . chr(128 | ($code & 63)); 
    } 

    // Callback for preg_replace_callback('~&(#(x?))?([^;]+);~', 'html_entity_replace', $str); 
    function html_entity_replace($matches) 
    { 
        if ($matches[2]) 
        { 
            return chr_utf8(hexdec($matches[3])); 
        } elseif ($matches[1]) 
        { 
            return chr_utf8($matches[3]); 
        } 
        switch ($matches[3]) 
        { 
            case "nbsp": return chr_utf8(160); 
            case "iexcl": return chr_utf8(161); 
            case "cent": return chr_utf8(162); 
            case "pound": return chr_utf8(163); 
            case "curren": return chr_utf8(164); 
            case "yen": return chr_utf8(165); 
            //... etc with all named HTML entities 
        } 
        return false; 
    } 
    
    function htmlentities2utf8 ($string) // because of the html_entity_decode() bug with UTF-8 
    { 
        $string = preg_replace_callback('~&(#(x?))?([^;]+);~', 'html_entity_replace', $string); 
        return $string; 
    }

