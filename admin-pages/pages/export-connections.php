<?php
/**
 * Controls the admin page "Export Connections".
 * 
 * @package    connection-hub
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ConnectionsHub_ExportConnectionsAdminPage') ):
class ConnectionsHub_ExportConnectionsAdminPage extends ConnectionsHub_AdminPage
{
	/**
	 * The main model for the Connections Hub plugin.
	 * @var  ConnectionsHub_Model
	 */
	private $model = null;	
	
	/**
	 * Constructor.
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
		if( !isset($_REQUEST['action']) ) return;
		
		switch( $_REQUEST['action'] )
		{
			case 'export':
				try {
					// Check if CSV handler exists
					$csv_handler_path = CONNECTIONS_HUB_PLUGIN_PATH . '/libraries/csv-handler/csv-handler.php';
					if( !file_exists($csv_handler_path) ) {
						wp_die('CSV handler library not found at: ' . $csv_handler_path);
					}
					
					require_once( $csv_handler_path );
					
					// Check if method exists
					if( !method_exists($this->model, 'csv_export') ) {
						wp_die('csv_export method not found in ConnectionsHub_Model');
					}
					
					// Attempt export
					$this->model->csv_export();
					exit; // Important: exit after export to prevent HTML output
					
				} catch( Exception $e ) {
					wp_die('Export error: ' . $e->getMessage());
				}
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
		<h2>Export Connections</h2>
		<p>Export all connections data to a CSV file.</p>
		
		<div class="card">
			<h3>CSV Export</h3>
			<p>Click the button below to download all connections data as a CSV file.</p>
			<p><a href="<?php echo esc_url($export_url); ?>" class="button button-primary">Download CSV Export</a></p>
		</div>
		
		<div class="card">
			<h3>Export Information</h3>
			<p>The CSV export will include:</p>
			<ul>
				<li>Connection titles and content</li>
				<li>Connection metadata</li>
				<li>Associated taxonomies and terms</li>
				<li>Publication dates and status</li>
			</ul>
		</div>
		<?php
	}

} // class ConnectionsHub_ExportConnectionsAdminPage
endif; // if( !class_exists('ConnectionsHub_ExportConnectionsAdminPage') )