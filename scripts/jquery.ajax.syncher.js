

/**
 * 
 */
jQuery(document).ready(
	function()
	{
		jQuery('form.connections-synch-form').CheckConnection();
		
		jQuery('button.synch-connections').click( function()
		{
			jQuery('form.connections-synch-form').SynchConnection();
		});
	}
);


/**
 * 
 */
( function( $ ) {
			
	$.fn.CheckConnection = function()
	{
		var forms = this;
		var index = 0;


		/**
		 * Setup the CLAS Sites plugin.
		 */
		function check_sites()
		{
			check_site( forms[index] );
			index++;
		}
		
		function check_site( form )
		{
			//
			// Gather data to send to server.
			//
			var data = {};
			data['nonce'] = $(form).children('input#connection-synch-form').val();
			data['url'] = $(form).children('input[name="url"]').val();
			data['id'] = $(form).children('input[name="post-id"]').val();

			data['action'] = 'connections-synch';
			data['ajax-action'] = 'check-site';

			$(form).children('div.check-status').html('Checking site...');
	
			//
			// Perform the AJAX request.
			//
			$.ajax({
				type: "POST",
				url: ajaxurl,
				data: data,
				dataType: "json"
			})
			.done(function( data )
			{
				if( data['status'] == false )
				{
					$(form).children('div.check-status')
						.html('Checking site...error.<br/><span class="error">'+data['message'])+'</span>';
				}
				else
				{
					$(form).children('div.check-status')
						.html('Checking site...done.<br/><span class="notice">'+data['message'])+'</span>';
				}
			})
			.fail(function( jqXHR, textStatus )
			{
				$(form).children('div.check-status')
					.html('Checking site...error.<br/>'+'Request Failed: '+jqXHR.responseText+' - '+textStatus);
			});			
		}
		
		
		/**
		 * Setup the CLAS Sites plugin for each DOMObject.
		 */
		return this.each( function() { check_sites(); } );
	}
	
				
	$.fn.SynchConnection = function()
	{
		var forms = this;
		var index = 0;


		/**
		 * Setup the CLAS Sites plugin.
		 */
		function synch_sites()
		{
			synch_site( forms[index] );
			index++;
		}

		function synch_site( form )
		{
			//
			// Gather data to send to server.
			//
			var data = {};
			data['nonce'] = $(form).children('input#connection-synch-form').val();
			data['url'] = $(form).children('input[name="url"]').val();
			data['id'] = $(form).children('input[name="post-id"]').val();

			data['action'] = 'connections-synch';
			data['ajax-action'] = 'synch-site';

			$(form).children('div.synch-status').html('Synching site...');
	
			//
			// Perform the AJAX request.
			//
			$.ajax({
				type: "POST",
				url: ajaxurl,
				data: data,
				dataType: "json"
			})
			.done(function( data )
			{
				if( data['status'] == false )
				{
					$(form).children('div.synch-status')
						.html('Synching site...error.<br/><span class="error">'+data['message'])+'</span>';
				}
				else
				{
					$(form).children('div.synch-status')
						.html('Synching site...done.<br/><span class="notice">'+data['message'])+'</span>';
				}
				
				//alert( data['synch-data'] );
				$(form).parent().parent().children('td.synch').html( data['synch-data'] );
			})
			.fail(function( jqXHR, textStatus )
			{
				$(form).children('div.synch-status')
					.html('Checking site...error.<br/>'+'Request Failed: '+jqXHR.responseText+' - '+textStatus);
			});			
		}
		
		
		/**
		 * Setup the CLAS Sites plugin for each DOMObject.
		 */
		return this.each( function() { synch_sites(); } );
	}
	
})( jQuery )

