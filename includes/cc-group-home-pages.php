<?php
/* Contents:
	1. Aggregate group activity streams via a group meta checkbox
	1a. Identify "prime" groups via a group meta checkbox
	2. Add maps & reports pane to groups (not finished)
*/
// 	1. Add the "home" page if one exists, move "Activity" to its own tab
//////////////////////
//////////////////////
// add_action( 'bp_actions', 'cc_add_group_activity_tab', 8 );

function cc_add_group_activity_tab() {
	  // Only check if we're on a group page
	  if( bp_is_group() ) { 
	  	$bp = buddypress();


	  // Only add the "Home" tab if the group has a custom front page, so check for an associated post. 
	  // Only add the new "Activity" tab if the group is visible to the user.
	  // Todo this will fail if a page is associated to multiple groups.
	    // $group_id = $bp->groups->current_group->id ;
	    // $visible = $bp->groups->current_group->is_visible ;
	    // $args =  array(
	    //    'post_type'   => 'group_home_page',
	    //    'posts_per_page' => '1',
	    //    'meta_query'  => array(
	    //                        array(
	    //                         'key'           => 'group_home_page_association',
	    //                         'value'         => $group_id,
	    //                         'compare'       => '=',
	    //                         'type'          => 'NUMERIC'
	    //                         )
	    //                     )
	    // ); 
	    // $custom_front_query = new WP_Query( $args );

	  	// TODO New approach: don't move activity, just rename "Home" to "Activity" using a language file, then create the new tab to show the group home page when appropriate. Yah?
	    // if( $custom_front_query->have_posts() && $visible ) { 
	      bp_core_new_subnav_item( 
	        array( 
	          'name' => 'Activity', 
	          'slug' => 'activity', 
	          'parent_slug' => $bp->groups->current_group->slug, 
	          'parent_url' => bp_get_group_permalink( $bp->groups->current_group ), 
	          'position' => 11, 
	          'item_css_id' => 'nav-activity',
	          'screen_function' => create_function('',"bp_core_load_template( apply_filters( 'groups_template_group_home', 'groups/single/home' ) );"),
	          'user_has_access' => 1
	        ) 
	      );
	   
	      if ( bp_is_current_action( 'activity' ) ) {
	        add_action( 'bp_template_content_header', create_function( '', 'echo "' . esc_attr( 'Activity' ) . '";' ) );
	        add_action( 'bp_template_title', create_function( '', 'echo "' . esc_attr( 'Activity' ) . '";' ) );
	      } // END if ( bp_is_current_action( 'activity' ) ) 
	    // } // END if( $custom_front_query->have_posts() )
	  } //END if( bp_is_group() )
	}

//  2. Create Group Home Page custom post type and needed meta boxes
//////////////////////
//////////////////////
//Generate Group Home Page custom post type to populate group home pages
add_action( 'init', 'register_cpt_group_home_page' );

	function register_cpt_group_home_page() {

	    $labels = array( 
	        'name' => _x( 'Group Home Pages', 'group_home_page' ),
	        'singular_name' => _x( 'Group Home Page', 'group_home_page' ),
	        'add_new' => _x( 'Add New', 'group_home_page' ),
	        'add_new_item' => _x( 'Add New Group Home Page', 'group_home_page' ),
	        'edit_item' => _x( 'Edit Group Home Page', 'group_home_page' ),
	        'new_item' => _x( 'New Group Home Page', 'group_home_page' ),
	        'view_item' => _x( 'View Group Home Page', 'group_home_page' ),
	        'search_items' => _x( 'Search Group Home Pages', 'group_home_page' ),
	        'not_found' => _x( 'No group home pages found', 'group_home_page' ),
	        'not_found_in_trash' => _x( 'No group home pages found in Trash', 'group_home_page' ),
	        'parent_item_colon' => _x( 'Parent Group Home Page:', 'group_home_page' ),
	        'menu_name' => _x( 'Group Homes', 'group_home_page' ),
	    );

	    $args = array( 
	        'labels' => $labels,
	        'hierarchical' => false,
	        'description' => 'This post type is queried when a group home page is requested.',
	        'supports' => array( 'title', 'editor' ),
	        'public' => true,
	        'show_ui' => true,
	        'show_in_menu' => true,
	        'menu_position' => 53,
	        //'menu_icon' => '',
	        'show_in_nav_menus' => false,
	        'publicly_queryable' => true,
	        'exclude_from_search' => true,
	        'has_archive' => false,
	        'query_var' => true,
	        'can_export' => true,
	        'rewrite' => false,
	        'capability_type' => 'post'//,
	        //'map_meta_cap'    => true
	    );

	    register_post_type( 'group_home_page', $args );
	}

//Add meta box to Group Home Page custom post type to associate posts with the group home page

	/* Fire our meta box setup function on the post editor screen. */
	add_action( 'load-post.php', 'cc_group_home_meta_boxes_setup' );
	add_action( 'load-post-new.php', 'cc_group_home_meta_boxes_setup' );

	/* Meta box setup function. */
	function cc_group_home_meta_boxes_setup() {

	  /* Add meta boxes on the 'add_meta_boxes' hook. */
	  add_action( 'add_meta_boxes', 'cc_add_group_home_meta_boxes' );

	  /* Save post meta on the 'save_post' hook. */
	  add_action( 'save_post', 'cc_save_group_home_meta', 10, 2 );
	}

	/* Create one or more meta boxes to be displayed on the group home page editor screen. */
	function cc_add_group_home_meta_boxes() {

	  add_meta_box(
	    'group-home-page-association',      // Unique ID
	    esc_html__( 'Groups to Use this Home Page', 'group-home-page' ),    // Title
	    'cc_group_home_page_meta_box',   // Callback function
	    'group_home_page',         // Admin page (or post type)
	    'normal',         // Context
	    'default'         // Priority
	  );
	}

	/* Display the post meta box on the post type edit page in wp-admin*/
	function cc_group_home_page_meta_box( $object, $box ) { ?>

	  <?php wp_nonce_field( basename( __FILE__ ), 'group_home_association_nonce' ); ?>
	<!-- Loop through Group Tree with the addition of checkboxes -->
	  <?php if (class_exists('BP_Groups_Hierarchy')) {
	    $tree = BP_Groups_Hierarchy::get_tree();
	    //print_r($tree);
	    $group_associations = get_post_meta( $object->ID, 'group_home_page_association', false); // Use false because we want an array of associations to be returned
	    //print_r($group_associations);

	    echo '<ul class="group-tree">';
	    foreach ($tree as $branch) {
	      ?>
	      <li><!-- ID: <?php echo $branch->id ;?> Name: <?php echo $branch->name;?> Parent ID:<?php echo $branch->parent_id ;?> -->
	        <input type="checkbox" id="group-home-page-assoc-<?php echo $branch->id ?>" name="group_home_page_association[]" value="<?php echo $branch->id ?>" <?php checked( in_array( $branch->id , $group_associations ) ); ?> />
	        <label for="group-home-page-assoc-<?php echo $branch->id ?>"><?php echo $branch->name; ?></label>
	      </li>
	      <?php
	    }
	    echo '</ul>';

	  }
	}

	/* Save the meta box's post metadata. */
	function cc_save_group_home_meta( $post_id, $post ) {

	  /* Verify the nonce before proceeding. */
	  if ( !isset( $_POST['group_home_association_nonce'] ) || !wp_verify_nonce( $_POST['group_home_association_nonce'], basename( __FILE__ ) ) )
	    return $post_id;

	  /* Get the post type object. */
	  $post_type = get_post_type_object( $post->post_type );

	  /* Check if the current user has permission to edit the post. */
	  if ( !current_user_can( $post_type->cap->edit_post, $post_id ) )
	    return $post_id;

	  if ( empty($_POST['group_home_page_association']) ) {
			//If this element of POST is empty, then we should delete any stored values if they exist
	        delete_post_meta($post_id, 'group_home_page_association');
	    }

	  if ( !empty($_POST['group_home_page_association']) && is_array($_POST['group_home_page_association']) ) {
	        delete_post_meta($post_id, 'group_home_page_association');
	        foreach ($_POST['group_home_page_association'] as $association) {
	        	// This stores multiple entries, in the event that the page is associated with multiple groups. This approach makes the meta query much more straightforward.
	            add_post_meta($post_id, 'group_home_page_association', $association);
	        }
	    }

	}

// Integrating the home page editing into the group admin tab
if ( class_exists( 'BP_Group_Extension' ) ) : // Recommended, to prevent problems during upgrade or when Groups are disabled
 
    class CC_Group_Home_Page_Extension extends BP_Group_Extension {

        function __construct() {

			$args = array(
	            	'slug'              => 'group-home',
	           		'name'              => 'Group Home',
	           		'visibility'        => 'public',
	           		'nav_item_position' => 1,
	           		//Todo: Currently using the "home" tab to display via templates.
	           		'enable_nav_item'   => cc_home_page_enabled_for_group( bp_get_current_group_id() ),
	           		'screens' => array(
		                'edit' => array(
		                    'name' => 'Group Home Page',
		                    'enabled' => true,
		                    // Changes the text of the Submit button
		                    // on the Edit page
		                    // 'submit_text' => 'Submit, suckaz',
		                ),
		                'create' => array(
		                    'enabled' => false,
		                ),
		            ),
	        	);
	        
	        	parent::init( $args );
			
		}

		/**
	     * settings_screen() is the catch-all method for displaying the content 
	     * of the edit, create, and Dashboard admin panels
	     */
	    function settings_screen( $group_id ) {

	        $custom_front_query = cc_get_group_home_page_post( $group_id, 'draft' );
	        // print_r($custom_front_query->posts);
	        if ( empty( $custom_front_query->posts) ) {
				// The group doesn't have a front page yet, so we need to create one. For a variety of reasons, but mostly we need the post ID.
				?>
				<label><input type='checkbox' name='create_a_group_home_page' value='1'> This group should have a home page.</label>
				<?php

	        } else {
	        	// The group has a front page and we can load up the editor.
	        	while ( $custom_front_query->have_posts() ) :

					$custom_front_query->the_post(); 
					$post_content = get_the_content();
					$post_id = get_the_ID();

				endwhile; 	

	                $args = array(
	                        // 'textarea_rows' => 100,
	                        // 'teeny' => true,
	                        // 'quicktags' => false
	                		'media_buttons' => true,
		                	'editor_height' => 360,
		                	'tabfocus_elements' => 'insert-media-button,save-post',
	                    );
	                    wp_editor( $post_content, 'group_home_page_content', $args); 
	                ?>
	                <input type="hidden" name="group_home_page_post_id" value="<?php echo $post_id; ?>">
	                <?php

			}

	    }
	 
	    /**
	     * settings_screen_save() contains the catch-all logic for saving 
	     * settings from the edit, create, and Dashboard admin panels
	     */
	    function settings_screen_save( $group_id ) {

	    	// If the page is new, $_POST['create_a_group_home_page'] will be set
	    	// If the page already exists, $_POST['group_home_page_content'] will be set
	    	if ( isset( $_POST['group_home_page_content'] ) || isset( $_POST['create_a_group_home_page'] ) ) {

	    		// Get group name to use for title
	    		$current_group = groups_get_group( array( 'group_id' => $group_id ) );

		    	// Some defaults
				$post_data = array(
	                'post_content' => $_POST['group_home_page_content'],
	                'post_type' => 'group_home_page',
	                'post_status' => 'publish',
	                'post_title' => $current_group->name,
	                'comment_status' => 'closed'
	            );

		        // Does a post already exist?
	   	        if ( isset( $_POST['group_home_page_post_id'] ) && is_numeric( $_POST['group_home_page_post_id'] ) ) {
	   	        	$post_data['ID'] = $_POST['group_home_page_post_id'];
	   	        } else {
	   	        	//If this is a new post, we'll add an author id.
	   	        	$post_data['post_author'] = get_current_user_id();
	   	        }

	   			// $towrite = PHP_EOL . print_r($post_data, TRUE);
				// $fp = fopen('creating_group_home_page.txt', 'a');
				// fwrite($fp, $towrite);
				// fclose($fp);

	        	// Save the post
	            $post_id = wp_insert_post($post_data);

	        	// If the post save was successful, save the postmeta
	            if ( $post_id ) {
		            // Associate the post with the group
					update_post_meta( $post_id, 'group_home_page_association', $group_id, false );
					// Add a success message
					bp_core_add_message( 'Group home page was successfully updated.', 'success' );

				} else {
					// Something went wrong
					bp_core_add_message( 'We couldn\'t update the group home page at this time.', 'error' );
				}
	        }	 		
	 			    
		}
 
        /**
         * Use this function to display the actual content of your group extension when the nav item is selected
         */
        function display() {

		    $custom_front_query = cc_get_group_home_page_post( $group_id );

			while ( $custom_front_query->have_posts() ) :
				$custom_front_query->the_post(); ?>
				<article id="post-<?php the_ID(); ?>" <?php post_class( 'clear' ); ?>>
					<!-- <h1 class="entry-title"><?php the_title(); ?></h1> -->
					<div class="entry-content">
						<?php the_content(); ?>
					</div><!-- .entry-content -->
				</article><!-- #post -->

			<?php
			endwhile;           
        }
 
        /**
         * If your group extension requires a meta box in the Dashboard group admin,
         * use this method to display the content of the metabox
         *
         * As in the case of create_screen() and edit_screen(), it may be helpful
         * to abstract shared markup into a separate method.
         *
         * This is an optional method. If you don't need/want a metabox on the group
         * admin panel, don't define this method in your class.
         *
         * <a href="http://buddypress.org/community/members/param/" rel="nofollow">@param</a> int $group_id The numeric ID of the group being edited. Use
         *   this id to pull up any relevant metadata
         *
         */
        // We're using the fallback method setting_screen, but may use this later.
        // function admin_screen( $group_id ) {
        //     if ( cc_home_page_enabled_for_group( $group_id ) ){
        //      	echo '<p>This group has a custom home page.</p>';
        //     } else {
        //      	echo '<p>This group <strong>does not</strong> have a custom home page.</p>';
        //     };
        // }
 
        /**
         * The routine run after the group is saved on the Dashboard group admin screen
         *
         * <a href="http://buddypress.org/community/members/param/" rel="nofollow">@param</a> int $group_id The numeric ID of the group being edited. Use
         *   this id to pull up any relevant metadata
         */
        // We're using the fallback method setting_screen_save, but may use this later.
        // function admin_screen_save( $group_id ) {
            // Grab your data out of the $_POST global and save as necessary
        // }
 
        // function widget_display() {
        // }

    }
 
    bp_register_group_extension( 'CC_Group_Home_Page_Extension' );
 
endif; // class_exists( 'BP_Group_Extension' )

//Helper functions.
////////////////////////////////
////////////////////////////////

add_filter('bp_groups_default_extension','cc_change_group_default_tab');
 
function cc_change_group_default_tab( $default_tab ){
 	// Get the current group id.
	if ( $group_id = bp_get_current_group_id() ) {

		$default_tab = ( cc_home_page_enabled_for_group( $group_id ) ? 'group-home' : $default_tab );
	 
	}
	 
	return $default_tab;
}

function cc_home_page_enabled_for_group( $group_id ) {
	// global $bp;
	$setting = false;

	// if ( bp_is_group() ) {
    
	    // $visible = $bp->groups->current_group->is_visible;

	    $custom_front_query = cc_get_group_home_page_post( $group_id );
	    // $towrite = PHP_EOL . 'group_id: ' . print_r($group_id, TRUE);
	    // $towrite .= PHP_EOL . 'visible: ' . print_r($visible, TRUE);
	    // $towrite .= PHP_EOL . 'custom front query: ' . print_r( $custom_front_query->have_posts(), TRUE);

	    if( $custom_front_query->have_posts() )
			$setting = true;

		// $towrite .= PHP_EOL . 'enabled?: ' . print_r( $setting, TRUE);
		// $fp = fopen('group_has_front_page_check.txt', 'a');
		// fwrite($fp, $towrite);
		// fclose($fp);
	// }

	return apply_filters('cc_home_page_enabled_for_group', $setting);
}

function cc_get_group_home_page_post( $group_id, $status = null ) {

	$args =  array(
       'post_type'   => 'group_home_page',
       'posts_per_page' => '1',
       'post_status' => $status == 'draft' ? array( 'pending', 'draft', 'publish' ) : array( 'publish' ),
       'meta_query'  => array(
                           array(
                            'key'           => 'group_home_page_association',
                            'value'         => $group_id,
                            'compare'       => '=',
                            'type'          => 'NUMERIC'
                            )
                        )
    ); 
    $custom_front_query = new WP_Query( $args );

    return $custom_front_query;
}

// add_filter( 'map_meta_cap', 'obliterate_caps', 16, 4 );
function obliterate_caps( $primitive_caps, $meta_cap, $user_id, $args ) {	

		$towrite = PHP_EOL . 'primitive caps: ' . print_r( $primitive_caps, TRUE);
		$towrite .= PHP_EOL . 'meta cap: ' . print_r( $meta_cap, TRUE);
		$fp = fopen('cap_checks_group_home_page.txt', 'a');
		fwrite($fp, $towrite);
		fclose($fp);

		if ( !in_array( $meta_cap, array( 'upload_files','edit_post','delete_post' ) ) ) {  
	        return $primitive_caps;  
	    }

	    $primitive_caps = array();

	return $primitive_caps;
	// For some reason, if the caps aren't being obliterated, the images in the gallery aren't being shown. Must be yet another caps thing.
}

// This enables the media button on the post edit form
add_filter( 'map_meta_cap', 'cc_group_home_setup_map_meta_cap', 14, 4 );
function cc_group_home_setup_map_meta_cap( $primitive_caps, $meta_cap, $user_id, $args ) {	

	// In order to upload files, a user needs to have caps for uploading and editing posts. 
	// We reset the "required" caps by blanking the array for those caps.

	// First we have to tell BP Group Hierarchy to not try to overwrite this action
	remove_filter('bp_current_action', 'group_hierarchy_override_current_action');

	// Only do this when on the group admin's group home edit page
	if( ( bp_is_current_component( 'groups' ) && bp_is_current_action( 'admin' ) &&  bp_is_action_variable( 'group-home', 0 ) )
		|| ( isset( $_POST['action'] ) && $_POST['action'] == 'upload-attachment' )
		// Could possibly restrict this further by checking that $_POST['post_id'] is a group home type post
		// || ( isset( $_POST['action'] ) && $_POST['action'] == 'query-attachments' )
		) {

		// Put the filter back before any possible return
		add_filter('bp_current_action', 'group_hierarchy_override_current_action');

		if ( !in_array( $meta_cap, array( 'upload_files','edit_post' ) ) ) {  
	        return $primitive_caps;  
	    }

	    $primitive_caps = array();
	}

	// Put the filter back before any possible return
	add_filter('bp_current_action', 'group_hierarchy_override_current_action');
	return $primitive_caps;
}

add_action('pre_get_posts','group_home_show_users_own_attachments');
function group_home_show_users_own_attachments( $wp_query_obj ) {
 
 	// Only do this when on the group admin's group home edit page
	// if( is_user_logged_in() && bp_is_current_component( 'groups' ) && bp_is_current_action( 'admin' ) &&  bp_is_action_variable( 'group-home', 0 ) ) {

		// The image library is populated via an AJAX request, so we'll check for that
		if( isset( $_POST['action'] ) && $_POST['action'] == 'query-attachments' ) {

			// If the user isn't a site admin, limit the image library to only show his images.
			if( !current_user_can( 'delete_pages' ) )
			    $wp_query_obj->set( 'author', get_current_user_id() );

		}
	// }

}

// add_filter( 'posts_where', 'devplus_attachments_wpquery_where' );
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