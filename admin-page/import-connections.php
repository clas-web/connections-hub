<?php


class ConnectionsHub_AdminPage_ImportConnections
{

	/* */
	public static $error_messages;
	public static $notice_messages;


	/* Default private constructor. */
	private function __construct() { }
	
	
	/**
	 *
	 */	
	public static function init()
	{
		self::$error_messages = array();
		self::$notice_messages = array();
	}


	/**
	 *
	 */	
	public static function display_messages()
	{
		foreach( self::$error_messages as $message )
		{
			?><div class="error"><?php echo $message; ?></div><?php
		}
		
		foreach( self::$notice_messages as $message )
		{
			?><div class="updated"><?php echo $message; ?></div><?php
		}
	}


	/**
	 * 
	 */
	public static function enqueue_scripts()
	{
		wp_deregister_script('jquery');
		wp_enqueue_script('jquery', 'http://ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js');
	}
	
	
	/**
	 * 
	 */
	public static function add_head_script()
	{
		?>
		<style>
		
			
		
		</style>
  		<script type="text/javascript">
			jQuery(document).ready( function()
			{
				
				
				
			});
		</script>
		<?php
	}
	

	/**
	 *
	 */	
	public static function show_page()
	{
		self::init();
		self::process_post();
		?>		

		<div class="wrap">
		
		<h2>Import Connections</h2>

		<?php self::display_messages(); ?>
		<div class="instructions">Some instruction go here</div>

		<div class="admin-page-container clearfix">
		
			<form action="" method="post" enctype="multipart/form-data">
				<?php settings_fields( 'connections-import-connections' ); ?>

				<input type="hidden" name="action" value="import-file" />

				<label for="file">Filename:</label>
				<input type="file" name="csv-file" id="csv-file"><br>
		
				<?php submit_button( 'Import' ); ?>
			</form>
			
			<form action="" method="post" enctype="multipart/form-data">
				<input type="hidden" name="action" value="clear-connections" />
				<?php submit_button( 'Clear Connections' ); ?>
			</form>
			
		</div>
		
		</div>
		
		<?php
	}
	
	
	/**
	 * 
	 */
	private static function process_post()
	{
		if( !isset($_POST) || !isset($_POST['action']) ) return;
		
		switch( $_POST['action'] )
		{
			case 'import-file':
				self::import_file();
				break;
				
			case 'clear-connections':
				self::clear_connections();
				break;
		}
	}

	
	/**
	 * 
	 */
	private static function clear_connections()
	{
		global $wpdb;
		$wpdb->delete( $wpdb->posts, array('post_type' => 'connection') );
		$terms = get_terms( 'connection-group' );
		foreach( $terms as $term )
		{
			wp_delete_term( $term->term_id, 'connection-group' );
		}
	}
	
	
	/**
	 * 
	 */
	private static function import_file()
	{
		if( empty($_FILES['csv-file']) ) return;
		
		//var_dump($_FILES['csv-file']['name']);     // original filename
		//var_dump($_FILES['csv-file']['type']);     // should be "text/csv"
		//var_dump($_FILES['csv-file']['tmp_name']); // path to file on server
		//var_dump($_FILES['csv-file']['error']);    // should be 0
		//var_dump($_FILES['csv-file']['size']);     // size in bytes

		$filename = $_FILES['csv-file']['name'];

		//
		// Check for file / upload errors.
		//
		if( $_FILES['csv-file']['error'] > 0 )
		{
			self::$error_messages[] = 'Error uploading file: "'.$filename.'".  Return Code: '.$_FILES['csv-file']['error'].'.';
			return;
		}

		if( $_FILES['csv-file']['type'] !== 'text/csv' )
		{
			self::$error_messages[] = 'Error uploading file: "'.$filename.'".  Unsupported filetype: "'.$_FILES['csv-file']['type'].'".';
			return;
		}

		//
		// Parse the csv.
		//		
		$rows = null;
		require_once( CONNECTIONS_PLUGIN_PATH.'/classes/csv-parser.php' );
		$status = Connections_CSVImporter::import($_FILES['csv-file']['tmp_name'], $rows);
		
		if( $status == false )
		{
			self:$error_messages[] = Connections_CSVImporter::$last_error;
			return;
		}
		
		//
		// Organize the rows into an associated array with the username as key.
		//
		self::organize_by_username( $rows );
		
		//
		// Insert or update each user in csv.
		//
		foreach( $rows as $username => $urows )
		{
			$urow = $urows[0];

			//connections_print( $urow, 'UROW' );
			
			//
			// Set defaults for the Connection post.
			//
			$connections_post = array(
				'post_title'   => $urow['title'],
				'post_name'    => $urow['slug'],
				'post_type'    => 'connection',
				'post_status'  => 'publish',
			);
			
			//
			// Get author information by username, if it exists.
			//
			if( get_userdatabylogin($username) )
			{
				$user = get_user_by( 'slug', $username );
				$connections_post['post_author'] = $user->ID;
			}
			
			//
			// Determine if post for user already exists, then insert or update the post.
			//
			$wpquery = new WP_Query(
				array(
					'post_type'  => 'connection',
					'meta_key'   => 'username',
					'meta_value' => $username,
				)
			);

			//
			// Set the Connection groups and links.
			//
			$taxonomy_terms = array();
			foreach( $urows as $ur )
			{
				$taxonomy_terms = array_merge_recursive( $taxonomy_terms, self::get_taxonomies($ur) );
			}
			
			if( !empty($taxonomy_terms) )
				$connections_post['tax_input'] = $taxonomy_terms;
			
			if( $wpquery->have_posts() )
			{
				$wpquery->the_post();
				$post = get_post();
				$connections_post['ID'] = $post->ID;
				$post_id = wp_update_post( $connections_post );
			}
			else
			{
				$post_id = wp_insert_post( $connections_post );
			}
			
			wp_reset_query();

			//
			// Error during insert or update of the post.
			//
			if( $post_id === 0 )
			{
				echo 'Unable to import site: '.$username;
				self::$error_messages[] = 'Unable to import site: '.$username;
				continue;
			}
			
			//
			// Save the Connections meta data ( sort-title, url, username, site-type ).
			//
			Connections_ConnectionCustomPostType::save_meta_data( $post_id, $urow['sort-title'], $urow['url'], $username, $urow['site-type'], 'synch' );
		}
		
		//
		// Change status to 'draft' for user's no longer in csv.
		//
		$wpquery = new WP_Query(
			array(
				'post_type'   => 'connection',
				'post_status' => 'publish',
				'meta_key'    => 'username',
				'meta_query'  => array(
					array(
						'key'     => 'username',
						'value'   => array_keys($rows),
						'compare' => 'NOT IN'
					),
				)
			)
		);
		
		if( $wpquery->have_posts() )
		{
			$connections_post = array( 'post_status' => 'draft' );
			
			while( $wpquery->have_posts() )
			{
				$wpquery->the_post();
				$post = get_post();
				$connections_post['ID'] = $post->ID;
				wp_update_post( $connections_post );
			}
		}
		else
		{
			//echo 'no users to remove.';
		}
		
		wp_reset_query();

		//
		// Done.
		//
		self::$notice_messages[] = 'Imported file: "'.$filename.'"';
	}
	
	
	/**
	 * 
	 */
	private static function organize_by_username( &$rows )
	{
		$urows = array();
		
		foreach( $rows as $row )
		{
			if( !array_key_exists($row['username'], $urows) )
				$urows[$row['username']] = array();

			$urows[$row['username']][] = $row;
		}
		
		$rows = $urows;
	}
	
	
	
	/**
     * Parse taxonomy data from the file
     *
     * array(
     *      // hierarchical taxonomy name => ID array
     *      'my taxonomy 1' => array(1, 2, 3, ...),
     *      // non-hierarchical taxonomy name => term names string
     *      'my taxonomy 2' => array('term1', 'term2', ...),
     * )
     *
     * @param array $data
     * @return array
     */
    private static function get_taxonomies( $data )
    {
        $taxonomies = array();
        foreach ($data as $k => $v) 
        {
            if( preg_match('/^taxonomy-(.*)$/', $k, $matches) ) 
            {
                $tax_name = $matches[1];
               	$taxonomy = get_taxonomy( $tax_name );

				if( $taxonomy === false )
				{
                    $this->log['error'][] = "Unknown taxonomy: '$tax_name'";
                    continue;
				}
				
				$taxonomies[$tax_name] = self::create_terms( $tax_name, $data[$k] );
            }
        }
        return $taxonomies;
    }



    /**
     * Return an array of term IDs for hierarchical taxonomies or the original
     * string from CSV for non-hierarchical taxonomies. The original string
     * should have the same format as csv_post_tags.
     *
     * @param string $taxonomy
     * @param string $field
     * @return mixed
     */
    private static function create_terms( $taxonomy_name, $fields )
    {
 		$terms = array_map( 'trim', explode(',', $fields) );

		if( is_taxonomy_hierarchical($taxonomy_name) )
        {
            $term_ids = array();
            
            foreach( $terms as $term )
            {
		 		$heirarchy = array_map( 'trim', explode('>', $term) );
            	
            	$parent = null;
            	for( $i = 0; $i < count($heirarchy); $i++ )
            	{
            		if( !term_exists($heirarchy[$i], $taxonomy_name, $parent) )
            		{
            			$args = array();
            			if( $parent ) $args['parent'] = $parent;
            			
            			$result = wp_insert_term( $heirarchy[$i], $taxonomy_name, $args );
            			if( is_wp_error($result) )
            			{
            				$this->log['error'][] = 'Unable to insert '.$taxonomy_name.'term: '.$heirarchy[$i];
            				break;
            			}
            		}
            		
            		$term_object = get_term_by( 'name', $heirarchy[$i], $taxonomy_name );
            		if( is_wp_error($term_object) )
            		{
           				$this->log['error'][] = 'Invalid '.$taxonomy_name.'term: '.$heirarchy[$i];
           				break;
            		}

            		$term_ids[] = $term_object->term_id;
            	}
            }
        
            return $term_ids;
        }

		return $terms;
    }

}


/*  */
