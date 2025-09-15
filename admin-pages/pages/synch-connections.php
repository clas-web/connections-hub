<?php

if( !class_exists('ConnectionsHub_SynchListTable') )
	require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/synch-list-table.php' );

/**
 * Controls the admin page "Synch Connections".
 * 
 * @package    connection-hub
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ConnectionsHub_SynchConnectionsAdminPage') ):
class ConnectionsHub_SynchConnectionsAdminPage extends ConnectionsHub_AdminPage
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
		$name = 'synch-connections',
		$menu_title = 'Synch Connections',
		$page_title = 'Synch Connections',
		$capability = 'administrator' )
	{
		parent::__construct( $name, $menu_title, $page_title, $capability );
		$this->model = ConnectionsHub_Model::get_instance();
	}

	/**
	 * Initialize the admin page. Called during "admin_init" action.
	 */
	public function init()
	{
		// Any initialization code
	}
	
	/**
	 * Loads the admin page. Called during "load-{page}" action.
	 */
	public function load()
	{
		// Any page loading code
	}
	
	/**
	 * Processes the current admin page.
	 */
	public function process()
	{
		if( !isset($_REQUEST['action']) ) return;
		
		switch( $_REQUEST['action'] )
		{
			case 'check':
				$this->add_notice( 'Checking connections...' );
				// Add check logic here
				break;
				
			case 'synch':
				$this->add_notice( 'Synchronizing connections...' );
				// Add sync logic here
				break;
		}
	}
	
	/**
	 * Displays the current admin page.
	 */
	public function display()
	{
		?>
		<h2>Synchronize Connections</h2>
		<p>Check and synchronize connections with remote sources.</p>
		
		<div class="card">
			<h3>Check Connections</h3>
			<p>Check the status of all connections without making changes.</p>
			<?php
			$this->form_start_get( 'check', array(), 'check', array() );
			?>
			<p><?php submit_button( 'Check All Connections', 'secondary', 'check', false ); ?></p>
			<?php
			$this->form_end();
			?>
		</div>
		
		<div class="card">
			<h3>Synchronize Connections</h3>
			<p><strong>Warning:</strong> This will update existing connections with data from remote sources.</p>
			<?php
			$this->form_start_get( 'synch', array(), 'synch', array() );
			?>
			<p><?php submit_button( 'Synchronize All Connections', 'primary', 'synch', false, array('onclick' => 'return confirm("Are you sure you want to synchronize all connections? This will update existing data.")') ); ?></p>
			<?php
			$this->form_end();
			?>
		</div>
		
		<div class="card">
			<h3>Sync Information</h3>
			<p>The synchronization process will:</p>
			<ul>
				<li>Check remote sources for updated connection data</li>
				<li>Update existing connections with new information</li>
				<li>Add new connections found in remote sources</li>
				<li>Log all changes and errors</li>
			</ul>
		</div>
		<?php
	}

} // class ConnectionsHub_SynchConnectionsAdminPage
endif; // if( !class_exists('ConnectionsHub_SynchConnectionsAdminPage') )