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
	// Debug: Force error if parent class doesn't exist
	public function __construct( $name = 'import-connections', $menu_title = 'Import Connections', $page_title = 'Import Connections', $capability = 'administrator' )
	{
		// Clear opcache if possible
		if( function_exists('opcache_reset') ) {
			opcache_reset();
		}
		
		if( !class_exists('ConnectionsHub_AdminPage') ) {
			wp_die('ConnectionsHub_AdminPage class not found!');
		}
		parent::__construct( $name, $menu_title, $page_title, $capability );
		$this->model = ConnectionsHub_Model::get_instance();
	}
	/**
	 * The main model for the Connections Hub plugin.
	 * @var  ConnectionsHub_Model
	 */
	private $model = null;	
	
	
	
	
	/**
	 * Processes the current admin page.
	 */
	public function process()
	{
		if( !isset($_POST) || !isset($_POST['action']) ) return;
		
		switch( $_POST['action'] )
		{
			case 'import-file':
				$this->import_file();
				break;
				
			case 'clear-connections':
				$this->model->clear_connections();
				break;
		}
	}
	
	
	/**
	 * Import the connections from the file.
	 */
	protected function import_file()
	{
		// check for upload file.
		if( !isset($_FILES) || !isset($_FILES['upload']) )
        {
        	$this->set_error( 'No uploaded file.' );
            return;
        }
		
		// check for file upload errors.
		if( $_FILES['upload']['error'] > 0 )
		{
			$this->set_error( 'Error uploading file: "'.$_FILES['upload']['name'].'".  Error code "'.$_FILES['csv-file']['error'].'".' );
			return;
		}

		// check that uploaded file type is supported.
		// if( $_FILES['upload']['type'] !== 'text/csv' )
		// {
		// 	$this->set_error( 'Error uploading file: "'.$_FILES['upload']['name'].'".  Unsupported filetype: "'.$_FILES['upload']['type'].'".' );
		// 	return;
		// }

		require_once( CONNECTIONS_HUB_PLUGIN_PATH . '/libraries/csv-handler/csv-handler.php' );
		
		// parse the CSV files.
		$rows = array();
		$results = PHPUtil_CsvHandler::import( $_FILES['upload']['tmp_name'], $rows, false );
		if( $results === false )
		{
			$this->set_error( PHPUtil_CsvHandler::$last_error );
            return;
		}
		
		// organize the rows into an associated array with the username as key.
		$urows = array();
		
		foreach( $rows as $row )
		{
			if( !array_key_exists($row['username'], $urows) )
				$urows[$row['username']] = array();

			$urows[$row['username']][] = $row;
		}
		
		// process each row of the CSV file.
		$processed_rows = 0;
		$count = 1;
		$errors = '';
		foreach( $urows as $username => $urow )
		{
			if( $this->model->add_connection($username, $urow) )
			{
				$processed_rows++;
			}
			else
			{
				$this->add_error( 'Row '.$count.': '.$this->model->last_error );
			}
			$count++;
		}
		
		// store upload results to display to users.
		$this->add_notice( 'Upload file: "'.$_FILES['upload']['name'].'".' );
		$this->add_notice( count($rows) . ' rows found in file.' );
		$this->add_notice( count($urows) . ' users found in file.' );
		$this->add_notice( $processed_rows . ' users added or updated successfully.' );
	}
	
	
	/**
	 * Displays the current admin page.
	 */
	public function display()
	{
		// Debug: Show class info
		echo '<div class="notice notice-info"><p>Debug: Class = ' . get_class($this) . '</p>';
		echo '<p>Debug: Parent class = ' . get_parent_class($this) . '</p>';
		echo '<p>Debug: form_start method exists = ' . (method_exists($this, 'form_start') ? 'YES' : 'NO') . '</p></div>';
		
		$this->form_start( 'upload', array('enctype' => 'multipart/form-data'), 'import-file', null );
		?>
		
		<input type="file"
			   name="upload"
			   accept=".csv" />
		<div class="upload-submit"><?php submit_button( 'Import', 'small' ); ?></div>
		<div style="clear:both"></div>
 		
 		<?php
 		$this->form_end();
	}

} // class ConnectionsHub_ImportConnectionsAdminPage extends APL_AdminPage
endif; // if( !class_exists('ConnectionsHub_ImportConnectionsAdminPage') )

