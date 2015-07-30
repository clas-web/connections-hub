<?php

add_filter( 'posts_join', 'connections_posts_join', 10, 2 );
add_filter( 'posts_orderby', 'connections_posts_orderby', 10, 2 );


/**
 * 
 */
function connections_posts_join( $join, $wp_query )
{
	if( $wp_query->get('post_type') !== 'connection' && !$wp_query->get('connection-group') && !$wp_query->get('connection-link') )
		return $join;

	global $wpdb;
	$join .= " LEFT JOIN (SELECT post_id, meta_value AS sort_title FROM $wpdb->postmeta WHERE meta_key = 'sort-title') cttable ON ($wpdb->posts.ID = cttable.post_id) ";
	return $join;
}

/**
 * 
 */
function connections_posts_orderby( $orderby, $wp_query )
{
	if( $wp_query->get('post_type') !== 'connection' && !$wp_query->get('connection-group') && !$wp_query->get('connection-link') )
		return $orderby;

	$orderby = " ISNULL(sort_title) ASC, sort_title ASC, post_date DESC ";
	return $orderby;
}

