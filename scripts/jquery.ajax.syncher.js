/**
 * jQuery script for the Synch Connections in the Connections Hub plugin.
 *
 * @package    connections-hub
 * @author     Crystal Barton <atrus1701@gmail.com>
 * @version    1.0
 */


var connections_to_check = null;
var connections_to_check_index = 0;
var connections_to_synch = null;
var connections_to_synch_index = 0;

jQuery(document).ready( function() {

	// Start check each Connection site.
	connections_to_check = jQuery('form.connections-synch-form');
	for( var i = 0; i < 5; i++ )
	{
		if( connections_to_check_index < connections_to_check.length )
		{
			var connection = connections_to_check[connections_to_check_index];
			jQuery(connection).CheckConnection();
		}
		else
		{
			break;
		}
		connections_to_check_index++;
	}			
	
	// Setup to synch each Connection site.
	jQuery('button.synch-connections').click( function()
	{
		connections_to_synch = jQuery('form.connections-synch-form');
		for( var i = 0; i < 5; i++ )
		{
			if( connections_to_synch_index < connections_to_synch.length )
			{
				var connection = connections_to_synch[connections_to_synch_index];
				jQuery(connection).SynchConnection();
			}
			else
			{
				break;
			}
			connections_to_synch_index++;
		}
		
		if( connections_to_synch.length > 0 )
			this.attr( 'disabled', 'disabled' );
	});

});


/**
 * jQuery Plugin: Check the Connection.
 */
jQuery.fn.CheckConnection = function()
{
	var forms = this;
	var index = 0;

	function check_sites()
	{
		check_site( forms[index] );
		index++;
	}
	
	function check_site( form )
	{
		// Gather data to send to server.
		var data = {};
		data['nonce'] = jQuery(form).children('input#connection-synch-form').val();
		data['url'] = jQuery(form).children('input[name="url"]').val();
		data['id'] = jQuery(form).children('input[name="post-id"]').val();

		data['action'] = 'connections-synch';
		data['ajax-action'] = 'check-site';

		jQuery(form).children('div.check-status').html('Checking site...');

		// Perform the AJAX request.
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: data,
			dataType: "json"
		})
		.done(function( data )
		{
			if( data['status'] == false )
			{
				jQuery(form).children('div.check-status')
					.html('Checking site...error.<br/><span class="error">'+data['message'])+'</span>';
			}
			else
			{
				jQuery(form).children('div.check-status')
					.html('Checking site...done.<br/><span class="notice">'+data['message'])+'</span>';
			}

			check_next_connection();
		})
		.fail(function( jqXHR, textStatus )
		{
			jQuery(form).children('div.check-status')
				.html('Checking site...error.<br/>'+'Request Failed: '+jqXHR.responseText+' - '+textStatus);

			check_next_connection();
		});			
	}
	
	function check_next_connection()
	{
		if( !connections_to_check.length ) return;
		
		if( connections_to_check_index < connections_to_check.length )
		{
			var connection = connections_to_check[connections_to_check_index];
			jQuery(connection).CheckConnection();
			connections_to_check_index++;
		}
		else
		{
			connections_to_check = null;
			connections_to_check_index = 0;
		}
	}
	
	// Check each site.
	return this.each( function() { check_sites(); } );
}
	

/**
 * jQuery Plugin: Synch the Connection.
 */
jQuery.fn.SynchConnection = function()
{
	var forms = this;
	var index = 0;


	function synch_sites()
	{
		synch_site( forms[index] );
		index++;
	}

	function synch_site( form )
	{
		// Gather data to send to server.
		var data = {};
		data['nonce'] = jQuery(form).children('input#connection-synch-form').val();
		data['url'] = jQuery(form).children('input[name="url"]').val();
		data['id'] = jQuery(form).children('input[name="post-id"]').val();

		data['action'] = 'connections-synch';
		data['ajax-action'] = 'synch-site';

		jQuery(form).children('div.synch-status').html('Synching site...');

		// Perform the AJAX request.
		jQuery.ajax({
			type: "POST",
			url: ajaxurl,
			data: data,
			dataType: "json"
		})
		.done(function( data )
		{
			if( data['status'] == false )
			{
				jQuery(form).children('div.synch-status')
					.html('Synching site...error.<br/><span class="error">'+data['message'])+'</span>';
			}
			else
			{
				jQuery(form).children('div.synch-status')
					.html('Synching site...done.<br/><span class="notice">'+data['message'])+'</span>';
			}
			
			//alert( data['synch-data'] );
			jQuery(form).parent().parent().children('td.synch').html( data['synch-data'] );
			
			check_next_connection();
		})
		.fail(function( jqXHR, textStatus )
		{
			jQuery(form).children('div.synch-status')
				.html('Checking site...error.<br/>'+'Request Failed: '+jqXHR.responseText+' - '+textStatus);
			
			check_next_connection();
		});			
	}

	function check_next_connection()
	{
		if( !connections_to_synch.length ) return;
		
		if( connections_to_synch_index < connections_to_synch.length )
		{
			var connection = connections_to_synch[connections_to_synch_index];
			jQuery(connection).SynchConnection();
			connections_to_synch_index++;
		}
		else
		{
			connections_to_synch = null;
			connections_to_synch_index = 0;
			jQuery('button.synch-connections').removeAttr( 'disabled' );
		}
	}		
	
	// Synch each site.	
	return this.each( function() { synch_sites(); } );
}

