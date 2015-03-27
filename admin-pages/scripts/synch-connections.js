

//========================================================================================
//================================================================ Check Connections =====


/**
 * Start the checking of all Connections.
 * @param  array  settings  The AJAX buttons settings.
 */
function check_all_connections_start( settings )
{
	jQuery('#connections-synch-status').html( 'Started checking Connections.' );
	jQuery('#connections-synch-substatus')
		.removeClass('error')
		.html( '&nbsp;' );
	jQuery('#connections-synch-results').html( '' );
	jQuery('.apl-ajax-button').prop( 'disabled', true );
}


/**
 * Start the checking of all Connections.
 * @param  array  settings  The AJAX buttons settings.
 */
function check_all_connections_end( settings )
{
	jQuery('#connections-synch-status').html( 'Done checking Connections.' );
	jQuery('.apl-ajax-button').prop( 'disabled', false );
}


/**
 * Start contacting the server via AJAX for Connections list.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 */
function check_all_connections_loop_start( fi, settings )
{
	jQuery('#connections-synch-status').html( 'Getting Connections list.' );
}


/**
 * Finished contacting the server via AJAX for Connections list.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 * @param  bool   success   True if the AJAX call was successful, otherwise false.
 * @param  array  data      The returned data on success, otherwise error information.
 */
function check_all_connections_loop_end( fi, settings, success, data )
{
	if( !success || !data.success )
	{
		jQuery('#connections-synch-status')
			.html( 'Failed to get Connections List.' );

		jQuery('#connections-synch-substatus')
			.addClass('error')
			.html( data.message );
	}
	else
	{
		jQuery('#connections-synch-status').html( 'Received Connections list.' );
	}
}


/**
 * Start cycling through the Connections list.
 * @param  array  ajax  The AJAX settings returned from the server.
 */
function check_connection_start( ajax )
{
	jQuery('#connections-synch-status').html( 'Checking each Connection.' );
}


/**
 * Finished cycling through the Connections list.
 * @param  array  ajax  The AJAX settings returned from the server.
 */
function check_connection_end( ajax )
{
	
}


/**
 * Start contacting the server via AJAX to check one Connection.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 * @param  int    ai        The current ajax items count.
 * @param  array  ajax      The AJAX settings returned from the server.
 */
function check_connection_loop_start( fi, settings, ai, ajax )
{
	jQuery('#connections-synch-substatus')
		.removeClass('error')
		.html(
			'Checking Connection '+(ai+1)+' of '+ajax.items.length+' "'+ajax.items[ai]['title']+'".'
		);
}


/**
 * Finished contacting the server via AJAX to check one Connection.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 * @param  int    ai        The current ajax items count.
 * @param  array  ajax      The AJAX settings returned from the server.
 * @param  bool   success   True if the AJAX call was successful, otherwise false.
 * @param  array  data      The returned data on success, otherwise error information.
 */
function check_connection_loop_end( fi, settings, ai, ajax, success, data )
{
	add_post_results( ajax.items[ai]['post_id'], ajax.items[ai]['title'], success, data );
}



//========================================================================================
//================================================================ Synch Connections =====


/**
 * Start the synching of all Connections.
 * @param  array  settings  The AJAX buttons settings.
 */
function synch_all_connections_start( settings )
{
	jQuery('#connections-synch-status').html( 'Getting Connections List.' );
	jQuery('#connections-synch-substatus')
		.removeClass('error')
		.html( '&nbsp;' );
	jQuery('#connections-synch-results').html( '' );
	jQuery('.apl-ajax-button').prop( 'disabled', true );
}


/**
 * Start the synching of all Connections.
 * @param  array  settings  The AJAX buttons settings.
 */
function synch_all_connections_end( settings )
{
	jQuery('#connections-synch-status').html( 'Done synching Connections.' );
	jQuery('.apl-ajax-button').prop( 'disabled', false );
}


/**
 * Start contacting the server via AJAX for Connections list.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 */
function synch_all_connections_loop_start( fi, settings )
{
}


/**
 * Finished contacting the server via AJAX for Connections list.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 * @param  bool   success   True if the AJAX call was successful, otherwise false.
 * @param  array  data      The returned data on success, otherwise error information.
 */
function synch_all_connections_loop_end( fi, settings, success, data )
{
	if( !success || !data.success )
	{
		jQuery('#connections-synch-substatus')
			.addClass('error')
			.html( 'Failed to get Connections List.' );
	}
}


/**
 * Start cycling through the Connections list.
 * @param  array  ajax  The AJAX settings returned from the server.
 */
function synch_connection_start( ajax )
{
	jQuery('#connections-synch-status').html( 'Synching each Connection.' );
}


/**
 * Finished cycling through the Connections list.
 * @param  array  ajax  The AJAX settings returned from the server.
 */
function synch_connection_end( ajax )
{
}


/**
 * Start contacting the server via AJAX to synch one Connection.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 * @param  int    ai        The current ajax items count.
 * @param  array  ajax      The AJAX settings returned from the server.
 */
function synch_connection_loop_start( fi, settings, ai, ajax )
{
	jQuery('#connections-synch-substatus')
		.removeClass('error')
		.html(
			'Synching Connection '+(ai+1)+' of '+ajax.items.length+' "'+ajax.items[ai]['title']+'".'
		);
}


/**
 * Finished contacting the server via AJAX to synch one Connection.
 * @param  int    fi        The current form count.
 * @param  array  settings  The AJAX buttons settings.
 * @param  int    ai        The current ajax items count.
 * @param  array  ajax      The AJAX settings returned from the server.
 * @param  bool   success   True if the AJAX call was successful, otherwise false.
 * @param  array  data      The returned data on success, otherwise error information.
 */
function synch_connection_loop_end( fi, settings, ai, ajax, success, data )
{
	add_post_results( ajax.items[ai]['post_id'], ajax.items[ai]['title'], success, data );
}



//========================================================================================
//=================================================================== Util functions =====


/**
 * Write out the results of checking/synching a Connection Post.
 * @param  int     post_id     The Connections post id.
 * @param  string  post_title  The Connections post title.
 * @param  bool    success     True if the AJAX call was successful, otherwise false.
 * @param  array   data        The returned data on success, otherwise error information.
 */
function add_post_results( post_id, post_title, success, data )
{
	jQuery('#connections-synch-substatus')
		.removeClass('error')
		.html( '&nbsp;' );
	
	var div = jQuery('<div>');
	var anchor = jQuery('<a>');
	var post_div = jQuery('<div>');
	
	jQuery(anchor).append(post_div);
	jQuery(div).append(anchor);
	
	jQuery(anchor).attr( 'href', 'post.php?post='+post_id+'&action=edit' )
		.attr('target', '_blank')
		.addClass('post-info');
	
	jQuery(post_div).append( jQuery('<div>')
		.addClass('post-title')
		.attr('title', post_id +' "'+post_title+'"')
		.html(post_title) );
		
	if( !success || !data.success )
	{
		jQuery(anchor).addClass('failure');

		jQuery(post_div).append( jQuery('<div>')
			.addClass('post-results')
			.html(data.message) );
	}
	else
	{
		jQuery(anchor).addClass(data.ajax.status);

		jQuery(post_div).append( jQuery('<div>')
			.addClass('post-results')
			.html(data.ajax.message) );
	}
	
	jQuery('#connections-synch-results').prepend( jQuery(div).html() );	
}






