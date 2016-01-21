<?php
/**
 * CC BuddyPress Group Home Pages
 *
 * @package   CC BuddyPress Group Home Pages
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 */

/* We're using the group extension to do these things:
 * - allow group admins to edit the group's home page via the group's Manage tab.
 * - add the new "home page" tab and allow anyone to see it.
 */

 if ( class_exists( 'BP_Group_Extension' ) ) : // Recommended, to prevent problems during upgrade or when Groups are disabled

    class CC_Group_Home_Page_Extension extends BP_Group_Extension {

        function __construct() {

        	// Instantiate the main class so we can get the slug
        	$ccgph_class = CC_BPGHP::get_instance();
			$access = ccghp_enabled_for_group( bp_get_current_group_id() ) ? 'anyone' : 'noone';

			$args = array(
	            	'slug'              => $ccgph_class->get_plugin_slug(),
	           		'name'              => 'Home',
	           		'access'			=> $access, // BP 2.1
	           		'show_tab'			=> $access, // BP 2.1
	           		'nav_item_position' => 1,
	           		'screens' => array(
		                'edit' => array(
		                    'name' => 'Hub Home Page',
		                    'enabled' => true,
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
	    function settings_screen( $group_id = null ) {
	    	// Check for concurrent editing.
	    	$home_page_post_id = cc_get_group_home_page_post_id( $group_id );
	    	$post_lock = cc_bpghp_check_post_lock( $home_page_post_id );

	    	if ( $post_lock ) {
	    		?>
	    		<div class="info" id="message"><p id="ccghp_edit_lock_status"><?php echo bp_core_get_userlink( $post_lock ); ?> is currently editing this page.<?php
		    		// Site admins can break the lock, of course.
		    		if ( current_user_can( 'delete_others_pages' ) ) {
		    			$break_edit_lock_link = get_edit_post_link( $home_page_post_id );
		    			echo ' As a site admin, you can <a href= "' . $break_edit_lock_link . '">break the edit lock</a>.';
		    		}
	    		?></p></div>
                <input type="hidden" name="group_home_page_post_id" id="group_home_page_post_id" value="<?php echo $home_page_post_id; ?>">
                <input type="hidden" name="group_home_page_heartbeat_action" id="group_home_page_heartbeat_action" value="check_post_lock">
	    		<!-- <input type="submit" disabled="disabled" name="save-disabled"> -->
	    		<?php
	    	} else {
	    		// Set an edit lock as the page loads.
				$now = time();
				$user_id = bp_loggedin_user_id();
				$lock = "$now:$user_id";
				update_post_meta( $home_page_post_id, '_edit_lock', $lock );

		        $custom_front_query = cc_get_group_home_page_post( $group_id, 'draft' );
		        // print_r($custom_front_query->posts);
		        if ( empty( $custom_front_query->posts ) ) {
					// The group doesn't have a front page yet, so we need to create one. For a variety of reasons, but mostly we need the post ID.
					?>
					<label class="enable-plugin"><input type='checkbox' name='create_a_group_home_page' value='1'> This hub should have a home page.</label>
					<?php

		        } else {
		        	// The group has a front page and we can load up the editor.
		        	while ( $custom_front_query->have_posts() ) :

						$custom_front_query->the_post();
						$post_content = get_the_content();
						$post_id = get_the_ID();
						$post_published = get_post_status( $post_id );

					endwhile;

		                $args = array(
		                        // 'textarea_rows' => 100,
		                        // 'teeny' => true,
		                        // 'quicktags' => false
		                		'tinymce' => true,
		                		'media_buttons' => true,
			                	'editor_height' => 360,
			                	'tabfocus_elements' => 'insert-media-button,save-post',
		                    );
		                    wp_editor( $post_content, 'group_home_page_content', $args);
		                ?>
			            <p>
				            <label for="cc_group_home_published">Published Status</label>
					        <select name="cc_group_home_published" id="cc_group_home_published">
					            <option <?php selected( $post_published, "publish" ); ?> value="publish">Published</option>
					            <option <?php selected( $post_published, "draft" );
					                if ( empty( $post_published ) ) { echo 'selected="selected"' ; }
					                ?> value="draft">Draft</option>
					        </select>
					    </p>
		                <input type="hidden" name="group_home_page_post_id" id="group_home_page_post_id" value="<?php echo $post_id; ?>">
		                <!-- This input is used for our hearbeat request to keep the lease renewed. -->
                        <input type="hidden" name="group_home_page_heartbeat_action" id="group_home_page_heartbeat_action" value="renew_post_lock">
		                <?php

				}
			}
	    }

	    /**
	     * settings_screen_save() contains the catch-all logic for saving
	     * settings from the edit, create, and Dashboard admin panels
	     */
	    function settings_screen_save( $group_id = 0 ) {
	    	// Use shared routine
	    	$this->ccghp_save_routine( $group_id );
		}

        /**
         * Use this function to display the actual content of your group extension when the nav item is selected.
         */
        function display( $group_id = null ) {
        	if ( is_null( $group_id ) ) {
	    		$group_id = bp_get_current_group_id();
	    	}

            do_action( 'cc_group_home_page_before_content', $group_id );

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

			do_action( 'cc_group_home_page_after_content', $group_id );

        }

        /**
         * A shared save routine.
         */
        public function ccghp_save_routine( $group_id ) {
        	/* If the page is new, $_POST['create_a_group_home_page'] will be set
	    	 * If the page already exists, $_POST['group_home_page_content'] will be set
	    	 */
	    	if ( isset( $_POST['group_home_page_content'] ) || isset( $_POST['create_a_group_home_page'] ) ) {

	    		// Get group name to use for title
	    		$current_group = groups_get_group( array( 'group_id' => $group_id ) );
	    		// Get the selected "published" status
    		    $published_status = in_array( $_POST['cc_group_home_published'], array( 'publish', 'draft' ) ) ? $_POST['cc_group_home_published'] : 'auto-draft';
    		    $post_content = ! empty( $_POST['group_home_page_content'] ) ? $_POST['group_home_page_content'] : '';

				$post_data = array(
	                'post_type' => 'group_home_page',
	                'post_title' => $current_group->name,
   	                'post_content' => $post_content,
                    'post_status' => $published_status,
	                'comment_status' => 'closed'
	            );

		        // Does a post already exist? TODO: Trust this or check it via a meta query?
	   	        if ( isset( $_POST['group_home_page_post_id'] ) && is_numeric( $_POST['group_home_page_post_id'] ) ) {
	   	        	$post_data['ID'] = $_POST['group_home_page_post_id'];
	   	        } else {
	   	        	// If this is a new post, we'll add an author id.
	   	        	$post_data['post_author'] = get_current_user_id();
	   	        }

	        	/* Save the post
	        	 * WP requires post_content by default, so we temporarily lift that restriction.
	        	 */
	        	if ( empty( $post_content ) ) {
		        	add_filter( 'wp_insert_post_empty_content', '__return_false' );
		        }
	            $post_id = wp_insert_post( $post_data, true ) ;
	        	if ( empty( $post_content ) ) {
					remove_filter( 'wp_insert_post_empty_content', '__return_false' );
				}

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

    }

    bp_register_group_extension( 'CC_Group_Home_Page_Extension' );

endif; // class_exists( 'BP_Group_Extension' )