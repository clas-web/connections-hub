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
				'posts_per_page' => -1
			)
		);
		
		while( $wpquery->have_posts() )
		{
			$wpquery->the_post();
			$post = get_post();
			
			$this->items[] = array(
				'name'       => $post->post_title,
				'url'        => connections_fix_url( get_post_meta( $post->ID, 'url', true ) ),
				'site-type'  => get_post_meta( $post->ID, 'site-type', true ),
				'synch-data' => get_post_meta( $post->ID, 'synch-data', true ),
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
		
		$form .= $this->_nonce_field;
		$form .= '<input type="hidden" name="post-id" value="'.$item['post-id'].'" />';
		$form .= '<input type="hidden" name="url" value="'.$item['url'].'" />';
		
		$form .= '<div class="check-status"></div>';
		$form .= '<div class="synch-status"></div>';
		
		$form .= '</form>';
		
		return $form;
	}

}

