<?php

if( !class_exists('WP_List_Table') )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


/**
 * The WP_List_Table class for the Connections Synch table.
 * 
 * @package    connections-hub
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
if( !class_exists('ConnectionsHub_SynchListTable') ):
class ConnectionsHub_SynchListTable extends WP_List_Table
{
	/**
	 * The admin page that contains the list table.
	 * @var  APL_AdminPage
	 */
	private $parent;

	/**
	 * The main model for the Connections Hub.
	 * @var  ConnectionsHub_Model
	 */
	private $model;
	
	
	/**
	 * Constructor.
	 */
	public function __construct( $parent )
	{
		$this->parent = $parent;
		$this->model = ConnectionsHub_Model::get_instance();
	}
	

	/**
	 * Loads the list table.
	 */
	public function load()
	{
		parent::__construct(
            array(
                'singular' => 'connections-hub-upload',
                'plural'   => 'connections-hub-upload',
                'ajax'     => false
            )
        );

		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}
	

	/**
	 * Prepare the table's items.
	 */
	public function prepare_items()
	{
		$this->items = $this->model->synch->get_synching_connections();
		usort( $this->items, array($this, 'sort_data') );
	}
	
	
	/**
	 * Sort Connections by a column value by comparing two Connections.
	 * @param  array  $a  Connections post 1.
	 * @param  array  $b  Connections post 2.
	 * @return  int  -1 if post 1 is greater than post 2, 1 if vice versa, 0 if equal.
	 */
	function sort_data( $a, $b )
	{
		$orderby = ( !empty($_GET['orderby']) ? $_GET['orderby'] : 'name' );
		$order = ( !empty($_GET['order']) ? $_GET['order'] : 'asc' );

		switch( $orderby )
		{
			case 'name':
			default:
				$result = strcmp( $a[$orderby], $b[$orderby] );
				break;
		}
		
		return ( $order === 'asc' ) ? $result : -$result;
	}
	
	
	/**
	 * Get the columns for the table.
	 * @return  array  An array of columns for the table.
	 */
	public function get_columns()
	{
		return array(
			'name'   => 'Name',
			'synch'  => 'Synch Data',
			'status' => 'Status'
		);
	}
	

	/**
	 * Get the column that are hidden.
	 * @return  array  An array of hidden columns.
	 */
	public function get_hidden_columns()
	{
		return array();
	}

	
	/**
	 * Get the sortable columns.
	 * @return  array  An array of sortable columns.
	 */
	public function get_sortable_columns()
	{
		return array(
			'name'	=> array( 'name', true ),
		);
	}
	
	
	/**
	 * Get the selectable (throught Screen Options) columns.
	 * @return  array  An array of selectable columns.
	 */
	public function get_selectable_columns()
	{
		return array();
	}
	
	
	/**
	 * Generate content for a cell.
	 * @param  Array  $item  The current Connections post's data.
	 * @param  string  $column_name  The name of the column.
	 * @return  string  The generated html for the cell.
	 */
	function column_default( $item, $column_name )
	{
		return '<strong>ERROR:</strong><br/>'.$column_name;
	}
	

	/**
	 * Generate content for the name column for each Connection.
	 * @param  Array  $item  The current Connections post's data.
	 * @return  string  The generated html for the cell.
	 */
	function column_name( $item )
	{
		$actions = array(
            'edit' => sprintf( '<a href="%s" target="_blank">View</a>', get_permalink($item['post-id']) ),
            'view' => sprintf( '<a href="%s" target="_blank">Edit</a>', get_edit_post_link($item['post-id']) ),
        );
		
		$url = $item['url'];
		$site_type = $item['site-type'];
		
		if( empty($url) ) $url = 'Invalid URL';
		else $url = '<a href="'.$url.'" target="_blank">'.$url.'</a>';
		
		switch($site_type)
		{
			case 'rss': $site_type = 'RSS Feed'; break;
			case 'wp': $site_type = 'WordPress Site'; break;
			default: $site_type = 'Unknown'; break;
		}
		
		return sprintf( '%1$s<br/>%2$s<strong>Site Url:</strong> %3$s<br/><strong>Site Type:</strong> %4$s', $item['name'],  $this->row_actions($actions), $url, $site_type );
	}
	

	/**
	 * Generate content for the synch column for each Connection.
	 * @param  Array  $item  The current Connections post's data.
	 * @return  string  The generated html for the cell.
	 */
	function column_synch( $item )
	{
		return Connections_ConnectionCustomPostType::format_synch_data( $item['synch-data'] );
	}
	

	/**
	 * Generate content for the status column for each Connection.
	 * @param  Array  $item  The current Connections post's data.
	 * @return  string  The generated html for the cell.
	 */
	function column_status( $item )
	{
		$form = '';
		
		$form .= '<form class="connections-synch-form">';
		
		$form .= '<input type="hidden" name="post-id" value="'.$item['post-id'].'" />';
		$form .= '<input type="hidden" name="url" value="'.$item['url'].'" />';
		
		$form .= '<div class="check-status"></div>';
		$form .= '<div class="synch-status"></div>';
		
		$form .= '</form>';
		
		return $form;
	}
}
endif;

