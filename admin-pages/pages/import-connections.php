<?php
/**
 * Controls the admin page "Import Connections".
 * 
 * @package    connection-hub
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ConnectionsHub_ImportConnectionsAdminPage') ):
class ConnectionsHub_ImportConnectionsAdminPage extends ConnectionsHub_AdminPage
{
	/**
	 * The main model for the Connections Hub plugin.
	 * @var  ConnectionsHub_Model
	 */
	private $model = null;	
	
	/**
	 * Controller.
	 */
	public function __construct(
		$name = 'import-connections',
		$menu_title = 'Import Connections',
		$page_title = 'Import Connections',
		$capability = 'administrator' )
	{
		parent::__construct( $name, $menu_title, $page_title, $capability );
		$this->model = ConnectionsHub_Model::get_instance();
	}
	
	/**
	 * Processes the current admin page.
	 */
	public function process()
	{
		// Check for action in GET (from form_start) or POST
		$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : '');
		
		if( empty($action) ) return;
		
		switch( $action )
		{
			case 'import-file':
				// Only process if we have POST data (file upload)
				if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
					$this->import_file();
				}
				break;
				
			case 'clear-connections':
				if( $_SERVER['REQUEST_METHOD'] === 'POST' ) {
					$this->model->clear_connections();
					$this->add_notice( 'All connections cleared successfully.' );
				}
				break;
		}
	}
	
	/**
	 * Import a file.
	 */
	private function import_file()
	{
		// Debug: Check if we have file upload
		if( !isset($_FILES) || !isset($_FILES['upload']) ) {
			$this->add_notice( 'Error: No file was uploaded.' );
			return;
		}
		
		if( $_FILES['upload']['error'] != 0 ) {
			$this->add_notice( 'Error: File upload failed with error code ' . $_FILES['upload']['error'] );
			return;
		}
		
		$this->add_notice( 'Starting import process...' );
		
		$filepath = $_FILES['upload']['tmp_name'];
		if( !file_exists($filepath) ) {
			$this->add_notice( 'Error: Uploaded file not found.' );
			return;
		}
		
		$rows = array();
		if( ($handle = fopen($filepath, 'r')) !== FALSE )
		{
			while( ($data = fgetcsv($handle, 10000, ',')) !== FALSE )
			{
				$rows[] = $data;
			}
			fclose($handle);
		}
		
		if( empty($rows) ) return;
		
		$headers = array_shift($rows);
		$urows = array();
		foreach( $rows as $row )
		{
			if( count($row) != count($headers) ) continue;
			
			$urow = array();
			for( $i = 0; $i < count($headers); $i++ )
			{
				$urow[ $headers[$i] ] = $row[$i];
			}
			$urows[] = $urow;
		}
		
		$processed_rows = 0;
		$errors = 0;
		$groups_created = 0;
		$links_created = 0;
		
		foreach( $urows as $urow )
		{
			
			try {
				// Create or update connection post
				$post_data = array(
					'post_type' => 'connection',
					'post_status' => 'publish',
					'post_title' => isset($urow['title']) ? $urow['title'] : '',
					'post_content' => isset($urow['content']) ? $urow['content'] : '',
					'post_name' => isset($urow['slug']) ? $urow['slug'] : '',
				);
				
				// Check if connection already exists by slug
				$existing_post = get_page_by_path( $post_data['post_name'], OBJECT, 'connection' );
				
				if( $existing_post ) {
					// Update existing post
					$post_data['ID'] = $existing_post->ID;
					$post_id = wp_update_post( $post_data );
				} else {
					// Create new post
					$post_id = wp_insert_post( $post_data );
				}
				
				if( is_wp_error($post_id) ) {
					$errors++;
					continue;
				}
				
				// Update post meta fields
				if( isset($urow['username']) ) {
					update_post_meta( $post_id, 'username', $urow['username'] );
				}
				if( isset($urow['sort-title']) ) {
					update_post_meta( $post_id, 'sort-title', $urow['sort-title'] );
				}
				if( isset($urow['entry-method']) ) {
					update_post_meta( $post_id, 'entry-method', $urow['entry-method'] );
				}
				if( isset($urow['site-type']) ) {
					update_post_meta( $post_id, 'site-type', $urow['site-type'] );
				}
				if( isset($urow['url']) ) {
					update_post_meta( $post_id, 'url', $urow['url'] );
				}
				if( isset($urow['contact-info']) ) {
					update_post_meta( $post_id, 'contact-info', $urow['contact-info'] );
				}
				
				// Handle connection-group taxonomies (comma-separated like connection-links)
				if( isset($urow['connection-group']) && !empty($urow['connection-group']) ) {
					$groups = explode(',', $urow['connection-group']);
					$groups = array_map('trim', $groups);
					$group_term_ids = array();
					
					foreach( $groups as $group_name ) {
						if( empty($group_name) ) continue;
						
						// Check if term exists, create if it doesn't
						$term = get_term_by( 'name', $group_name, 'connection-group' );
						if( !$term ) {
							$term_result = wp_insert_term( $group_name, 'connection-group' );
							if( !is_wp_error($term_result) ) {
								$group_term_ids[] = $term_result['term_id'];
								$groups_created++;
							} else {
								error_log('Failed to create connection-group term: ' . $group_name . ' - ' . $term_result->get_error_message());
							}
						} else {
							$group_term_ids[] = $term->term_id;
						}
					}
					
					// Assign all groups to post
					if( !empty($group_term_ids) ) {
						$result = wp_set_post_terms( $post_id, $group_term_ids, 'connection-group' );
						if( is_wp_error($result) ) {
							error_log('Failed to assign connection-groups to post: ' . $result->get_error_message());
						}
					}
				}
				
				// Handle connection-link taxonomies (comma-separated)
				if( isset($urow['connection-link']) && !empty($urow['connection-link']) ) {
					$links = explode(',', $urow['connection-link']);
					$links = array_map('trim', $links);
					$link_term_ids = array();
					
					foreach( $links as $link_name ) {
						if( empty($link_name) ) continue;
						
						// Check if term exists, create if it doesn't
						$term = get_term_by( 'name', $link_name, 'connection-link' );
						if( !$term ) {
							$term_result = wp_insert_term( $link_name, 'connection-link' );
							if( !is_wp_error($term_result) ) {
								$link_term_ids[] = $term_result['term_id'];
								$links_created++;
							} else {
								error_log('Failed to create connection-link term: ' . $link_name . ' - ' . $term_result->get_error_message());
							}
						} else {
							$link_term_ids[] = $term->term_id;
						}
					}
					
					// Assign all links to post
					if( !empty($link_term_ids) ) {
						$result = wp_set_post_terms( $post_id, $link_term_ids, 'connection-link' );
						if( is_wp_error($result) ) {
							error_log('Failed to assign connection-links to post: ' . $result->get_error_message());
						}
					}
				}
				
				$processed_rows++;
				
			} catch( Exception $e ) {
				$errors++;
				error_log('Connection import error: ' . $e->getMessage());
			}
		}
		
		$this->add_notice( count($rows) . ' data rows found in file.' );
		$this->add_notice( count($urows) . ' connections processed from file.' );
		$this->add_notice( $processed_rows . ' connections imported or updated successfully.' );
		
		if( $groups_created > 0 ) {
			$this->add_notice( $groups_created . ' new connection-group terms created.' );
		}
		
		if( $links_created > 0 ) {
			$this->add_notice( $links_created . ' new connection-link terms created.' );
		}
		
		if( $errors > 0 ) {
			$this->add_notice( $errors . ' connections had errors during import. Check debug log for details.' );
		}
	}
	
	/**
	 * Displays the current admin page.
	 */
	public function display()
	{
		?>
		<h2>Import Connections from CSV File</h2>
		<p>Select a CSV file to import connections data.</p>
		<?php
		
		$this->form_start( 'upload', array('enctype' => 'multipart/form-data'), 'import-file', array() );
		?>
		
		<table class="form-table">
			<tr>
				<th scope="row">CSV File</th>
				<td>
					<input type="file" name="upload" accept=".csv" />
					<p class="description">Select a CSV file containing connections data.</p>
				</td>
			</tr>
		</table>
		
		<?php submit_button( 'Import Connections' ); ?>
		
		<?php
		$this->form_end();
		
		// Show clear connections form
		?>
		<hr />
		<h3>Clear All Connections</h3>
		<p><strong>Warning:</strong> This will delete all existing connections data.</p>
		<?php
		
		$this->form_start( 'clear', array(), 'clear-connections', array() );
		?>
		<?php submit_button( 'Clear All Connections', 'delete', 'clear', false, array('onclick' => 'return confirm("Are you sure you want to clear all connections? This cannot be undone.")') ); ?>
		<?php
		$this->form_end();
	}

} // class ConnectionsHub_ImportConnectionsAdminPage
endif; // if( !class_exists('ConnectionsHub_ImportConnectionsAdminPage') )