//This enables the media button on the post edit form
add_filter( 'map_meta_cap', 'cc_group_home_setup_map_meta_cap', 14, 4 );
function cc_group_home_setup_map_meta_cap( $primitive_caps, $meta_cap, $user_id, $args ) {	

	// In order to upload files, a user needs to have caps for uploading and editing posts. 
	// We reset the "required" caps by blanking the array for those caps.

	// Only do this when on the group admin's group home edit page
	if( is_user_logged_in() && bp_is_current_component( 'groups' ) && bp_is_current_action( 'admin' ) &&  bp_is_action_variable( 'group-home', 0 ) ) {

		if ( !in_array( $meta_cap, array( 'upload_files','edit_post' ) ) ) {  
	        return $primitive_caps;  
	    }

	    $primitive_caps = array();
	}

	return $primitive_caps;
}

//This affects the media upload
// add_filter( 'map_meta_cap', 'cc_group_home_map_meta_cap', 14, 4 );
function cc_group_home_map_meta_cap( $primitive_caps, $meta_cap, $user_id, $args ) {	

	// In order to upload files, a user needs to have caps for uploading and editing posts. 
	// We reset the "required" caps by blanking the array for those caps.

	// Only do this when on the group admin's group home edit page
	// if( is_user_logged_in() && bp_is_current_component( 'groups' ) && bp_is_current_action( 'admin' ) &&  bp_is_action_variable( 'group-home', 0 ) ) {

		if ( !in_array( $meta_cap, array( 'upload_files','edit_post' ) ) ) {  
	        return $primitive_caps;  
	    }

	    // $primitive_caps = array();

	    // Images are uploaded via an AJAX request, so we'll check for that
		// if( isset( $_POST['action'] ) && $_POST['action'] == 'upload-attachment' ) {
		//     $primitive_caps = array();
		// }

	// }

	return $primitive_caps;
}

// add_action('pre_get_posts','group_home_show_users_own_attachments');
function group_home_show_users_own_attachments( $wp_query_obj ) {
 
 	// Only do this when on the group admin's group home edit page
	if( is_user_logged_in() && bp_is_current_component( 'groups' ) && bp_is_current_action( 'admin' ) &&  bp_is_action_variable( 'group-home', 0 ) ) {

		// The image library is populated via an AJAX request, so we'll check for that
		if( isset( $_POST['action'] ) && $_POST['action'] == 'query-attachments' ) {

			// If the user isn't a site admin, limit the image library to only show his images.
			if( !current_user_can( 'delete_pages' ) )
			    $wp_query_obj->set( 'author', get_current_user_id() );

		}
	}

	if( isset( $_POST['action'] ) && $_POST['action'] == 'query-attachments' ) {

			// If the user isn't a site admin, limit the image library to only show his images.
			if( !current_user_can( 'delete_pages' ) )
			    $wp_query_obj->set( 'author', get_current_user_id() );

		}
}
add_filter( 'posts_where', 'devplus_attachments_wpquery_where' );
function devplus_attachments_wpquery_where( $where ){
	global $current_user;

	if( is_user_logged_in() ){
		// we spreken over een ingelogde user
		if( isset( $_POST['action'] ) ){
			// library query
			if( $_POST['action'] == 'query-attachments' ){
				$where .= ' AND post_author='.$current_user->data->ID;
			}
		}
	}

	return $where;
}