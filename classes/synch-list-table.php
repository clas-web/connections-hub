<?php

if( !class_exists('WP_List_Table') )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


/**
 * 
 */
class ConnectionsHub_SynchListTable extends WP_List_Table
{
	private $parent;		// The parent admin page.
	private $model;			// The main model.
	
	
	/**
	 * Constructor.
	 * Creates an ConnectionsHub_SynchListTable object.
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
	 * 
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
	 * 
	 */
	function column_default( $item, $column_name )
	{
		return '<strong>ERROR:</strong><br/>'.$column_name;
	}
	

	/**
	 * 
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
	 * 
	 */
	function column_synch( $item )
	{
		return Connections_ConnectionCustomPostType::format_synch_data( $item['synch-data'] );
	}
	

	/**
	 * 
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

