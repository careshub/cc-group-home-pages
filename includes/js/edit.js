jQuery(document).ready(function($){

	// We use WP's heartbeat API to set edit locks when a page is being edited from the front end.
	// Set the interval and the namespace event
	if ( typeof wp != 'undefined' && typeof wp.heartbeat != 'undefined' && typeof cc_bpghp.pulse != 'undefined' ) {

		wp.heartbeat.interval( Number( cc_bpghp.pulse ) );

		$.fn.extend({
			'heartbeat-send': function() {
			return this.bind( 'heartbeat-send.cc_bpghp' );
	        },
	    });
	}

	// When WP sends its heartbeat pulse, append a GHP-specific argument.
	$( document ).on( 'heartbeat-send.cc_bpghp', function( e, data ) {
		data['ccghp_post_id'] = $('#group_home_page_post_id').val();
		data['ccghp_lock_action'] = $('#group_home_page_heartbeat_action').val();
	});

	// Update the status message on response from the server.
	$( document ).on( 'heartbeat-tick', function( e, data ) {
		if ( data['ccghp_locked_by'] ) {
			if ( $( '#ccghp_edit_lock_status' ).length ) {
				// If the user is just watching the status banner, update the message.
				$( '#ccghp_edit_lock_status' ).html( data['ccghp_locked_by'] + ' is currently editing this page.' );
			} else {
				// If the user is editing the home page, and another user takes control, pop a warning balloon.
				alert( data['ccghp_locked_by'] + ' has taken control of this post.' );
			}
		} else {
			// console.log('user is in control!');
			$( '#ccghp_edit_lock_status' ).html('Reload the page to edit.');
		}
	});

	$(window).on('beforeunload', function( event ){

		// Unload is triggered (by hand) on removing the Thickbox iframe.
		// Make sure we process only the main document unload.
		if ( event.target && event.target.nodeName != '#document' ) {
			return;
		}

		$.ajax({
			type: 'POST',
			url: ajaxurl,
			async: false,
			data: {
				action: 'cc_bpghp_remove_edit_lock',
				ccghp_post_id: $('#group_home_page_post_id').val(),
			}
		});
	});

},(jQuery));
