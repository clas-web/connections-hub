<?php
/**
 * ConnectionsHub_ExportConnectionsAdminPage
 * 
 * This class controls the admin page "Export Connections".
 * 
 * @package    connection-hub
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <cbarto11@uncc.edu>
 */

if( !class_exists('ConnectionsHub_ExportConnectionsAdminPage') ):
class ConnectionsHub_ExportConnectionsAdminPage extends APL_AdminPage
{
	
	private $model = null;	
	
	
	/**
	 * Creates an ConnectionsHub_ExportConnectionsAdminPage object.
	 */
	public function __construct(
		$name = 'export-connections',
		$menu_title = 'Export Connections',
		$page_title = 'Export Connections',
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
		switch( $_REQUEST['action'] )
		{
			case 'export':
				require_once( CONNECTIONS_HUB_PLUGIN_PATH . '/libraries/csv-handler/csv-handler.php' );
				$this->model->csv_export();
				break;
		}
	}
	
	
	/**
	 * Displays the current admin page.
	 */
	public function display()
	{
		$export_url = $this->get_page_url(
			array( 'action' => 'export' )
		);
		
		?>
		<a href="<?php echo $export_url; ?>">Export</a>
		<?php
	}
	
	
} // class ConnectionsHub_ExportConnectionsAdminPage extends APL_AdminPage
endif; // if( !class_exists('ConnectionsHub_ExportConnectionsAdminPage') )

