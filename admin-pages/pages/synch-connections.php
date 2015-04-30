<?php

if( !class_exists('ConnectionsHub_SynchListTable') )
	require_once( CONNECTIONS_HUB_PLUGIN_PATH.'/classes/synch-list-table.php' );


/**
 * ConnectionsHub_SynchConnectionsAdminPage
 * 
 * This class controls the admin page "Synch Connections".
 * 
 * @package    connection-hub
 * @subpackage admin-pages/pages
 * @author     Crystal Barton <cbarto11@uncc.edu>
 */

if( !class_exists('ConnectionsHub_SynchConnectionsAdminPage') ):
class ConnectionsHub_SynchConnectionsAdminPage extends APL_AdminPage
{
	
	private $model = null;	
// 	private $list_table = null;
	
	
	/**
	 * Creates an ConnectionsHub_SynchConnections object.
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
	 * Initialize the admin page.  Called during "admin_init" action.
	 */
	public function init()
	{
// 		$this->list_table = new ConnectionsHub_SynchListTable( $this );
	}
	
	
	/**
	 * Loads the admin page.  Called during "load-{page}" action.
	 */
	public function load()
	{
// 		$this->list_table->load();
	}


	/**
	 * Enqueues all the scripts or styles needed for the admin page. 
	 */
	public function enqueue_scripts()
	{
		wp_enqueue_script('synch-connections', CONNECTIONS_HUB_PLUGIN_URL.'/admin-pages/scripts/synch-connections.js', array('jquery'));
	}
	
	
	/**
	 * Processes the current admin page.
	 */
	public function process()
	{
	}
	
	
	/**
	 * Displays the current admin page.
	 */
	public function display()
	{
// 		$this->list_table->prepare_items();

		$this->form_start_get( 'check', null, 'check' );
			$this->create_ajax_submit_button(
				'Check Connections',
				'check-all-connections',
				null,
				null,
				'check_all_connections_start',
				'check_all_connections_end',
				'check_all_connections_loop_start',
				'check_all_connections_loop_end'
			);
		$this->form_end();
		
		$this->form_start_get( 'synch', null, 'synch' );
			$this->create_ajax_submit_button(
				'Synch Connections',
				'synch-all-connections',
				null,
				null,
				'synch_all_connections_start',
				'synch_all_connections_end',
				'synch_all_connections_loop_start',
				'synch_all_connections_loop_end'
			);
		$this->form_end();
		
		?>
		<div id="connection-synch-filters">
			<input type="checkbox" id="connections-show-only-errors" />Show only errors
		</div>
		
		<div id="connections-synch-status"></div>
		<div id="connections-synch-substatus"></div>
		<div id="connections-synch-results"></div>
		<?php
		
// 		$this->form_start( 'synch-connections-table' );
// 			$this->list_table->display();
// 		$this->form_end();
	}


	/**
	 * Processes and displays the output of an ajax request.
	 * @param  string  $action  The AJAX action.
	 * @param  array   $input   The AJAX input array.
	 * @param  int     $count   When multiple AJAX calls are made, the current count.
	 * @param  int     $total   When multiple AJAX calls are made, the total count.
	 */
	public function ajax_request( $action, $input, $count, $total )
	{
		switch( $action )
		{
			case 'check-all-connections':
				$connections = $this->model->synch->get_synching_connections();
				
				$items = array();
				foreach( $connections as $connection )
				{
					$items[] = array(
						'post_id'	=> $connection['post-id'],
						'title'		=> $connection['name'],
					);
				}
				
				$this->ajax_set_items(
					'check-connection',
					$items,
					'check_connection_start',
					'check_connection_end',
					'check_connection_loop_start',
					'check_connection_loop_end'
				);
				break;
				
			case 'check-connection':
				if( !isset($input['post_id']) )
				{
					$this->ajax_failed( 'No Connections Post id given.' );
					return;
				}
				
				$status = $this->model->synch->get_data( $input['post_id'] );
				$message = ( $status !== false ? 'OK' : $this->model->synch->last_error );
				$status = ( $status !== false ? 'success' : 'failure' );
				
				$this->ajax_set( 'status', $status );
				$this->ajax_set( 'message', $message );
				break;
				
			case 'synch-all-connections':
				$connections = $this->model->synch->get_synching_connections();
				
				$items = array();
				foreach( $connections as $connection )
				{
					$items[] = array(
						'post_id'	=> $connection['post-id'],
						'title'		=> $connection['name'],
					);
				}
				
				$this->ajax_set_items( 
					'synch-connection',
					$items,
					'synch_connection_start',
					'synch_connection_end',
					'synch_connection_loop_start',
					'synch_connection_loop_end'
				);
				break;
			
			case 'synch-connection':
				if( !isset($input['post_id']) )
				{
					$this->ajax_failed( 'No Connections Post id given.' );
					return;
				}
				
				$data = $this->model->synch->get_data( $input['post_id'] );
				if( $data === false )
				{
					$this->ajax_set( 'status', 'failure' );
					$this->ajax_set( 'message', $this->model->synch->last_error );
					break;
				}
				
				$this->model->synch->synch( $input['post_id'], $data );
				$this->ajax_set( 'status', 'success' );
				$this->ajax_set( 'message', 'OK' );
				break;
				
			default:
				$this->ajax_failed( 'No valid action was given.' );
				break;
		}
	}
	
	
} // class ConnectionsHub_SynchConnectionsAdminPage extends APL_AdminPage
endif; // if( !class_exists('ConnectionsHub_SynchConnectionsAdminPage') )

