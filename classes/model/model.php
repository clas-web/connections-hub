<?php

/**
 * ConnectionsHub_Model
 * 
 * The main model for the Connections Hub plugin.
 * 
 * @package    connections-hub
 * @subpackage classes/model
 * @author     Crystal Barton <cbarto11@uncc.edu>
 */

if( !class_exists('ConnectionsHub_Model') ):
class ConnectionsHub_Model
{
	
	private static $instance = null;	// The only instance of this class.

	public $synch = null;				// The synch model.
	
	public $last_error = null;			// The error logged by a model.
	
	
	
	/**
	 * Private Constructor.  Needed for a Singleton class.
	 * Creates an ConnectionsHub_Model object.
	 */
	protected function __construct()
	{
		
	}


	/**
	 * Sets up the "children" models used by this model.
	 */
	protected function setup_models()
	{
		$this->synch = ConnectionsHub_SynchModel::get_instance();
	}
	

	/**
	 * Get the only instance of this class.
	 * @return  ConnectionsHub_Model  A singleton instance of the model class.
	 */
	public static function get_instance()
	{
		if( self::$instance	=== null )
		{
			self::$instance = new ConnectionsHub_Model();
			self::$instance->setup_models();
		}
		return self::$instance;
	}



//========================================================================================
//========================================================================= Log file =====


	/**
	 * Clear the log.
	 */
	public function clear_log()
	{
		file_put_contents( CONNECTIONS_HUB_LOG_FILE );
	}
	

	/**
	 * Write a line to a log file.
	 * @param  string  $text      The line of text to insert into the log.
	 * @param  bool    $newline   True if a new line character should be inserted after
	 *                            the line, otherwise False.
	 */
	public function write_to_log( $text = '', $newline = true )
	{
		$text = print_r( $text, true );
		if( $newline ) $text .= "\n";
		file_put_contents( CONNECTIONS_HUB_LOG_FILE, $text, FILE_APPEND );
	}
	
	
	
//========================================================================================
//=============================================== Import / Updating Connection posts =====
	
	
	/**
	 * Adds or updates a Connection post for a user.
	 * @param   string  $username  The username of the user.
	 * @param   array   $urows     The rows of the uploaded file that are associated with the user.
	 * @return  bool    True if the user was added/updated successfully, otherwise false.
	 */
	public function add_connection( $username, &$urows )
	{
		$urow = $urows[0];
		
		$urow['slug'] = ( isset($urow['slug']) ? $urow['slug'] : sanitize_title($urow['title']) );
		$urow['content'] = ( isset($urow['content']) ? $urow['content'] : '' );
		
		// set defaults for the Connection post.
		$connections_post = array(
			'post_title'   => $urow['title'],
			'post_name'    => $urow['slug'],
			'post_type'    => 'connection',
			'post_status'  => 'publish',
		);
		
		if( isset($urow['content']) )
			$connections_post['post_content'] = $urow['content'];
		
		// get author information by username, if it exists.
		if( $user = get_user_by( 'login', $username ) )
		{
			$connections_post['post_author'] = $user->ID;
		}

		// set the Connection groups and links.
		$taxonomy_terms = array();
		foreach( $urows as $ur )
		{
			$taxonomies = array();
			
			if( array_key_exists('connection-group', $ur) )
			{
				$taxonomies['connection-group'] = $ur['connection-group'];
			}
			
			if( array_key_exists('connection-link', $ur) )
			{
				$taxonomies['connection-link'] = $ur['connection-link'];
			}
			
			$taxonomy_terms = array_merge_recursive( $taxonomy_terms, $this->get_taxonomies($taxonomies) );
		}
		
		if( !empty($taxonomy_terms) ) $connections_post['tax_input'] = $taxonomy_terms;
		
		// determine if post for user already exists, then insert or update the post.
		$wpquery = new WP_Query(
			array(
				'post_type'  => 'connection',
				'meta_key'   => 'username',
				'meta_value' => $username,
				'posts_per_page' => 1,
			)
		);
		
//		apl_print($connections_post, 'connections_post');
		
		// update if post exists, otherwise insert.
		if( $wpquery->have_posts() )
		{
//			apl_print('updating post');
			$wpquery->the_post();
			$post = get_post();
			$connections_post['ID'] = $post->ID;
			$connections_post['post_content'] = $post->post_content;
			$post_id = $post->ID;
			$result = wp_update_post( $connections_post, true );

			if( is_wp_error($result) )
			{
				$this->model->last_error = 'Unable to update connection "'.$urow['title'].'". '.$result->get_error_message();
				return false;
			}
		}
		else
		{
//			apl_print('creating post');
			$result = wp_insert_post( $connections_post, true );
			
			if( is_wp_error($result) )
			{
				$this->model->last_error = 'Unable to insert connection "'.$urow['title'].'". '.$result->get_error_message();
				return false;
			}
			$post_id = $result;
		}
		
		wp_reset_query();
		
		// save the Connections meta data ( sort-title, url, username, site-type ).
		Connections_ConnectionCustomPostType::save_meta_data( $post_id, $urow['sort-title'], $username, $urow['url'], $urow['site-type'], $urow['entry-method'] );
		if( empty($urow['phone']) ) $urow['phone'] = null;
		if( empty($urow['email']) ) $urow['email'] = null;
		if( empty($urow['location']) ) $urow['location'] = null;
		Connections_ConnectionCustomPostType::save_contact_info( $post_id, $urow['phone'], $urow['email'], $urow['location'] );
		
		return true;
	}
	
	
	/**
	 * Remove all the Connections posts and associated Connection Groups and Links.
	 */
	public function clear_connections()
	{
		global $wpdb;
		$wpdb->delete( $wpdb->posts, array('post_type' => 'connection') );
		
		$terms = get_terms( 'connection-group' );
		foreach( $terms as $term )
		{
			wp_delete_term( $term->term_id, 'connection-group' );
		}

		$terms = get_terms( 'connection-link' );
		foreach( $terms as $term )
		{
			wp_delete_term( $term->term_id, 'connection-link' );
		}
	}
	


//========================================================================================
//=================================================================== Util Functions =====
	
	
	/**
	 * Gets a list of taxonomies with matching terms or ids (for heirarchical taxonomies).
	 * Creates any terms that do not currently exist.
	 * @param   array       $taxonomies     An array of taxonomies with terms in 
	 *                                      comma-seperated string form.
	 * @param   bool        $supports_null  True if null should be returned on failure or 
	 *                                      taxonomy list is empty.
	 * @return  array|null  An array of taxonomies with their terms or ids on success, 
	 *                      otherwise null or empty array (based on supports_null).
	 */
	protected function get_taxonomies( $taxonomies, $supports_null = false )
	{
		if( $taxonomies === '' && $supports_null ) return null;
		
		$new_taxonomies = array();
		
		foreach( $taxonomies as $taxname => $terms )
		{
			if( !taxonomy_exists($taxname) )
			{
				// TODO: error.
				continue;
			}
			
			$new_taxonomies[$taxname] = array();
			$term_list = str_getcsv( $terms, ",", '"', "\\" );
			
			if( !is_taxonomy_hierarchical($taxname) )
			{
				$new_taxonomies[$taxname] = $term_list;
			}
			else
			{
				$term_ids = array();
			
				foreach( $term_list as $term )
				{
					$heirarchy = array_map( 'trim', explode('>', $term) );
					
					$parent = null;
					for( $i = 0; $i < count($heirarchy); $i++ )
					{
						if( !term_exists($heirarchy[$i], $taxname, $parent) )
						{
							$args = array();
							if( $parent ) $args['parent'] = $parent;
					
							$result = wp_insert_term( $heirarchy[$i], $taxname, $args );
							if( is_wp_error($result) )
							{
								//TODO: error: 'Unable to insert '.$taxonomy_name.'term: '.$heirarchy[$i];
								break;
							}
						}
						
						$termobject = get_term_by( 'name', $heirarchy[$i], $taxname );
						if( is_wp_error($termobject) )
						{
							//TODO: error: 'Invalid '.$taxonomy_name.'term: '.$heirarchy[$i];
							break;
						}
						
						$parent = $termobject->term_id;
					}
					
					if( isset($termobject) && !is_wp_error($termobject) )
						$term_ids[] = $termobject->term_id;
				}
				
				$new_taxonomies[$taxname] = $term_ids;
			}
		}
		
		return $new_taxonomies;
	}
	
	
	public function get_all_connections()
	{
		$connections = get_posts(
			array(
				'posts_per_page'	=> -1,
				'post_type'			=> 'connection',
			)
		);
		
		$conns = array();
		$i = 0;
		foreach( $connections as $cp )
		{
			$connection_groups = wp_get_post_terms( $cp->ID, 'connection-group' );
			$connection_links = wp_get_post_terms( $cp->ID, 'connection-link' );
			
			if( is_wp_error($connection_groups) || count($connection_groups) == 0 )
				$connection_groups = array( '' );
			
			foreach( $connection_links as &$link )
			{
				$link = $link->name;
			}

			foreach( $connection_groups as $group )
			{
				$conns[$i]['username'] = get_post_meta( $cp->ID, 'username', true );

				$conns[$i]['title'] = $cp->post_title;
				$conns[$i]['sort-title'] = get_post_meta( $cp->ID, 'sort-title', true );
				$conns[$i]['slug'] = $cp->post_name;
				
//				$conns[$i]['content'] = $cp->post_content;
//				$conns[$i]['search-content'] = get_post_meta( $cp->ID, 'search-content', true );
				
				$conns[$i]['entry-method'] = get_post_meta( $cp->ID, 'entry-method', true );
				$conns[$i]['site-type'] = get_post_meta( $cp->ID, 'site-type', true );
				$conns[$i]['url'] = get_post_meta( $cp->ID, 'url', true );
				
				$conns[$i]['phone'] = get_post_meta( $cp->ID, 'contact-phone', true );
				$conns[$i]['email'] = get_post_meta( $cp->ID, 'contact-email', true );
				$conns[$i]['location'] = get_post_meta( $cp->ID, 'contact-location', true );

				$conns[$i]['connection-group'] = ( is_string($group) ? $group : $group->name );
				$conns[$i]['connection-link'] = implode( ',', $connection_links );
				
				$i++;
			}
		}
		
		return $conns;
	}


	/**
	 * Exports a list of Connections to a CSV.
	 * @param   array   $filter       An array of filter name and values.
	 * @param   array   $search       An array of search columns and phrases.
	 * @param   bool    $only_errors  True if filter out OrgHub users with errors.
	 * @param   string  $orderby      The column to orderby.
	 */
	public function csv_export()
	{
		$connections = $this->get_all_connections();
		
		$headers = array(
			'username',

			'title',
			'sort-title',
			'slug',
			
//			'content',
//			'search-content',
			
			'entry-method',
			'site-type',
			'url',
			
			'phone',
			'email',
			'location',

			'connection-group',
			'connection-link',
		);

		PHPUtil_CsvHandler::export( 'connections', $headers, $connections );
		exit;
	}

} // class ConnectionsHub_Model
endif; // if( !class_exists('ConnectionsHub_Model') ):

