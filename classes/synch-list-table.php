<?php

if( !class_exists('WP_List_Table') )
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );


/**
 * 
 */
class ConnectionsHub_AdminPage_SynchListTable extends WP_List_Table
{

	private $_nonce_field;
	
	
	/**
	 * 
	 */
	function prepare_items()
	{
		$this->_nonce_field = wp_nonce_field( CONNECTIONS_PLUGIN_PATH, 'connection-synch-form', false, false );
		
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array($columns, $hidden, $sortable);

		$this->get_items();
		usort( $this->items, array( &$this, 'sort_data' ) );
	}


	/**
	 * 
	 */
	function get_items()
	{
		$this->items = array();
		
		$wpquery = new WP_Query(
			array(
				'post_type'   => 'connection',
				'post_status' => 'publish',
			)
		);
		
		while( $wpquery->have_posts() )
		{
			$wpquery->the_post();
			$post = get_post();
			
			$this->items[] = array(
				'name'       => $post->post_title,
				'url'        => get_post_meta( $post->ID, 'url', true ),
				'site-type'  => get_post_meta( $post->ID, 'site-type', true ),
				'synch-data' => get_post_meta( $post->ID, 'site-data' ),
				'post-id'    => $post->ID,
			);
		}
	}
	
	
	/**
	 * 
	 */
	function get_columns()
	{
		return array(
			'name'   => 'Name',
			'site'   => 'Site',
			'synch'  => 'Synch Data',
			'status' => 'Status'
		);
	}

	
	/**
	 * 
	 */
	function get_hidden_columns()
	{
		return array();
	}

	
	/**
	 * 
	 */
	function get_sortable_columns()
	{
		return array(
			'name'  => array( 'name', false ),
		);
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
	 * 
	 */
	function column_default( $item, $column_name )
	{
		return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
	}
	

	/**
	 * 
	 */
	function column_name( $item )
	{
		return $item['name'];
	}
	

	/**
	 * 
	 */
	function column_site( $item )
	{
		$url = $item['url'];
		$site_type = $item['site-type'];
		
		if( empty($url) ) $url = 'Invalid URL';

		switch($site_type)
		{
			case 'rss': $site_type = 'RSS Feed'; break;
			case 'wp': $site_type = 'WordPress Site'; break;
			default: $site_type = 'Unknown'; break;
		}

		return $url.'<br/>'.$site_type;	
	}
	

	/**
	 * 
	 */
	function column_synch( $item )
	{
			$synch_data = $item['synch-data'];
			
			if( empty($synch_data) )
			{
				$synch_data = 'Never synched.';
			}
			else
			{
				$synch_data = '';
				foreach( $synch_data as $key => $value )
				{
					$synch_data .= $key.': '.$value.'<br/>';
				}
			}
			
			return $synch_data;
	}
	

	/**
	 * 
	 */
	function column_status( $item )
	{
		$form = '';
		$form .= '<form class="connections-synch-form">';
		
		$form .= $this->_nonce_field;
		$form .= '<input type="hidden" name="post-id" value="'.$item['post-id'].'" />';
		$form .= '<input type="hidden" name="url" value="'.$item['url'].'" />';
		
		$form .= '<div class="check-status"></div>';
		$form .= '<div class="synch-status"></div>';
		
		$form .= '</form>';
		
		return $form;
	}

}

