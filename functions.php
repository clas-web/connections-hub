<?php
/**
 * The main functions for the Connections Hub plugin.
 * 
 * @package    connections-hub
 * @author     Crystal Barton <atrus1701@gmail.com>
 */
 
 /**
 * Remove capabilities from authors.
 *
 * 
 */

function wpsites_remove_author_capabilities() {
    // Get the role object.
    $role = get_role( 'author' );        
    $role->remove_cap( 'edit_posts' );
    $role->remove_cap( 'publish_posts' ); 

}
add_action( 'init', 'wpsites_remove_author_capabilities' );

 /**
 * Remove menu items from authors.
 *
 * 
 */

function remove_menus() {
	$author = wp_get_current_user();
	
	if (isset($author->roles[0])) { 
		$current_role = $author->roles[0];
	}else{
		$current_role = 'no_role';
	}

	if ($current_role == 'author') {
		remove_menu_page( 'tools.php' );
		remove_menu_page( 'upload.php' );
		remove_menu_page( 'jetpack');
		remove_menu_page( 'edit-comments.php' );
		remove_menu_page( 'edit.php' );
		remove_menu_page( 'edit.php?post_type=connection' );
	}  
}

add_action( 'admin_menu', 'remove_menus', 999 );


// Order the Connections posts by the sort title.
add_filter( 'posts_join', 'conhub_posts_join', 10, 2 );
add_filter( 'posts_orderby', 'conhub_posts_orderby', 10, 2 );

// Disable certain connections fields for non-admin users
wp_register_script('admin_title_disable', CONNECTIONS_HUB_PLUGIN_URL.'/scripts/admin_title_disable.js');

function disableAdminTitle () {
	if ( !current_user_can('customize')) {
		wp_enqueue_script('admin_title_disable');
	}
}

add_action('admin_enqueue_scripts', 'disableAdminTitle');

/**
 * If a connections post, then join the meta value for key 'sort-title'.
 * @param  string  $join  The current join statement.
 * @param  WP_Query  $wp_query  The WP_Query object of the query.
 * @return  string  The alterd join statement.
 */
if( !function_exists('conhub_posts_join') ):
function conhub_posts_join( $join, $wp_query )
{
	if( $wp_query->get('post_type') !== 'connection' && !$wp_query->get('connection-group') && !$wp_query->get('connection-link') )
		return $join;

	global $wpdb;
	$join .= " LEFT JOIN (SELECT post_id, meta_value AS sort_title FROM $wpdb->postmeta WHERE meta_key = 'sort-title') cttable ON ($wpdb->posts.ID = cttable.post_id) ";
	return $join;
}
endif;


/**
 * If a connections post, then sort by the 'sort-title', if it exists.
 * @param  string  $orderby  The current order by statement.
 * @param  WP_Query  $wp_query  The WP_Query object of the query.
 * @return  string  The altered order statement.
 */
if( !function_exists('conhub_posts_orderby') ):
function conhub_posts_orderby( $orderby, $wp_query )
{
	if( $wp_query->get('post_type') !== 'connection' && !$wp_query->get('connection-group') && !$wp_query->get('connection-link') )
		return $orderby;

	$orderby = " ISNULL(sort_title) ASC, sort_title ASC, post_date DESC ";
	return $orderby;
}
endif;

