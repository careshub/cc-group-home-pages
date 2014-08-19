<?php
/**
 * CC BuddyPress Group Home Pages
 *
 * @package   CC BuddyPress Group Home Pages
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 */

class CC_BPGHP {

	/**
	 * Plugin version, used for cache-busting of style and script file references.
	 *
	 * @since   1.0.0
	 *
	 * @var     string
	 */
	const VERSION = '1.0.0';

	/**
	 *
	 * Unique identifier for your plugin.
	 *
	 *
	 * The variable name is used as the text domain when internationalizing strings
	 * of text. Its value should match the Text Domain file header in the main
	 * plugin file.
	 *
	 * @since    1.0.0
	 *
	 * @var      string
	 */
	protected $plugin_slug = 'group-home';

	/**
	 *
	 * The ID for the AHA group on www.
	 *
	 *
	 *
	 * @since    1.0.0
	 *
	 * @var      int
	 */
	// public static cc_aha_get_group_id();// ( get_home_url() == 'http://commonsdev.local' ) ? 55 : 594 ; //594 on staging and www, 55 on local

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization and loading public scripts
	 * and styles.
	 *
	 * @since     1.0.0
	 */
	private function __construct() {

		// Load plugin text domain
		// add_action( 'init', array( $this, 'load_plugin_textdomain' ) );

		// Add filter to catch removal of a story from a group
		// add_action( 'bp_init', array( $this, 'remove_story_from_group'), 75 );

		// Activate plugin when new blog is added
		// add_action( 'wpmu_new_blog', array( $this, 'activate_new_site' ) );

		// Load public-facing style sheet and JavaScript.
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
		// add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		/* Define custom functionality.
		 * Refer To http://codex.wordpress.org/Plugin_API#Hooks.2C_Actions_and_Filters
		 */
		// add_action( '@TODO', array( $this, 'action_method_name' ) );
		// add_filter( '@TODO', array( $this, 'filter_method_name' ) );

		/* Create a custom post type for group home pages. */
		add_action( 'init', array( $this, 'register_cpt_group_home_page' ) );

		/* Add meta boxes on the 'add_meta_boxes' hook. */
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		/* Save post meta on the 'save_post' hook. */
		add_action( 'save_post', array( $this, 'save_meta' ), 10, 2 );

		/**
		 * Set the group home page as the default page if one exists.
		 * TODO: after BP 2.1 we can use this, I think.
		 */
		// add_filter( 'bp_groups_default_extension', array( $this, 'change_group_default_tab' ) );

		/* Add a tab to house the group activity, since we're using "Home" for the group home page. */
		add_action( 'bp_actions', array( $this, 'add_group_activity_tab' ), 8 );

		/* Filter "map_meta_caps" to let our users do things they normally can't. */
		// add_action( 'bp_init', array( $this, 'add_mmc_filter') );

		/* Don't interpret shortcodes on the group home page edit screen. */
		add_action( 'bp_init', array( $this, 'remove_shortcode_filter_on_settings_screen') );

		/* Only allow users to see their own items in the media library uploader. */
		add_action( 'pre_get_posts', array( $this, 'show_users_own_attachments') );





	}

	/**
	 * Return the plugin slug.
	 *
	 * @since    1.0.0
	 *
	 * @return    Plugin slug variable.
	 */
	public function get_plugin_slug() {
		return $this->plugin_slug;
	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	/**
	 * Fired when the plugin is activated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Activate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       activated on an individual blog.
	 */
	public static function activate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide  ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_activate();
				}

				restore_current_blog();

			} else {
				self::single_activate();
			}

		} else {
			self::single_activate();
		}

	}

	/**
	 * Fired when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 *
	 * @param    boolean    $network_wide    True if WPMU superadmin uses
	 *                                       "Network Deactivate" action, false if
	 *                                       WPMU is disabled or plugin is
	 *                                       deactivated on an individual blog.
	 */
	public static function deactivate( $network_wide ) {

		if ( function_exists( 'is_multisite' ) && is_multisite() ) {

			if ( $network_wide ) {

				// Get all blog ids
				$blog_ids = self::get_blog_ids();

				foreach ( $blog_ids as $blog_id ) {

					switch_to_blog( $blog_id );
					self::single_deactivate();

				}

				restore_current_blog();

			} else {
				self::single_deactivate();
			}

		} else {
			self::single_deactivate();
		}

	}

	/**
	 * Fired when a new site is activated with a WPMU environment.
	 *
	 * @since    1.0.0
	 *
	 * @param    int    $blog_id    ID of the new blog.
	 */
	public function activate_new_site( $blog_id ) {

		if ( 1 !== did_action( 'wpmu_new_blog' ) ) {
			return;
		}

		switch_to_blog( $blog_id );
		self::single_activate();
		restore_current_blog();

	}

	/**
	 * Get all blog ids of blogs in the current network that are:
	 * - not archived
	 * - not spam
	 * - not deleted
	 *
	 * @since    1.0.0
	 *
	 * @return   array|false    The blog ids, false if no matches.
	 */
	private static function get_blog_ids() {

		global $wpdb;

		// get an array of blog ids
		$sql = "SELECT blog_id FROM $wpdb->blogs
			WHERE archived = '0' AND spam = '0'
			AND deleted = '0'";

		return $wpdb->get_col( $sql );

	}

	/**
	 * Fired for each blog when the plugin is activated.
	 *
	 * @since    1.0.0
	 */
	private static function single_activate() {
		// @TODO: Define activation functionality here
	}

	/**
	 * Fired for each blog when the plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	private static function single_deactivate() {
		// @TODO: Define deactivation functionality here
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		$domain = $this->plugin_slug;
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, trailingslashit( WP_LANG_DIR ) . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, FALSE, basename( plugin_dir_path( dirname( __FILE__ ) ) ) . '/languages/' );

	}

	/**
	 * Register and enqueue public-facing style sheet.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		// wp_enqueue_style( $this->plugin_slug . '-plugin-styles', plugins_url( 'css/aha-extras-tab.css', __FILE__ ), array(), self::VERSION );
	}

	/**
	 * Register and enqueue public-facing JavaScript files.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
	}


	/**
	 * Set the group home page as the default page if one exists.
	 * TODO: after BP 2.1 we can use this, I think.
	 *
	 * @since    1.0.0
	 */
	public function change_group_default_tab( $default_tab ){
	 	// Get the current group id.
		if ( $group_id = bp_get_current_group_id() ) {

			$default_tab = ( ccghp_enabled_for_group( $group_id ) ? $this->plugin_slug : $default_tab );
		 
		}
		 
		return $default_tab;
	}

	
	/**
	 * Move activity off to its own tab. We'll reuse the Home tab
	 * TODO: after BP 2.1 we can do this another way, using the access and show_tab properties
	 *
	 * @since    1.0.0
	 */
	public function add_group_activity_tab() {
	  // Only continue if we're on a group page
	  if( ! bp_is_group() )
	  	return false;

	  $bp = buddypress();

	  // Only add the "Home" tab if the group has a custom front page, so check for an associated post. 
	  // Only add the new "Activity" tab if the group is visible to the user.
	  // Todo this will fail if a page is associated to multiple groups.
	    $group_id = $bp->groups->current_group->id ;
	    $visible = $bp->groups->current_group->is_visible ;
	    $args =  array(
	       'post_type'   => 'group_home_page',
	       'posts_per_page' => '1',
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

	  	// TODO New approach: don't move activity, just rename "Home" to "Activity" using a language file, then create the new tab to show the group home page when appropriate. Yah?
	    if( $custom_front_query->have_posts() ) { 
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
	    } // END if( $custom_front_query->have_posts() )
	}


	/**
	 * Generate Group Home Page custom post type to populate group home pages
	 *
	 * @since    1.0.0
	 */
	public function register_cpt_group_home_page() {

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


	/**
	 * Add meta box to Group Home Page custom post type to associate posts with the group home page
	 *
	 * @since    1.0.0
	 */
	/* Register the meta boxes */
	public function add_meta_boxes() {

	  add_meta_box(
	    'group-home-page-association',      // Unique ID
	    esc_html__( 'Groups to Use this Home Page', 'group-home-page' ),    // Title
	    array( $this, 'output_meta_box' ),   // Callback function
	    'group_home_page',         // Admin page (or post type)
	    'normal',         // Context
	    'default'         // Priority
	  );
	}

	/* Display the post meta box on the post type edit page in wp-admin */
	public function output_meta_box( $object, $box ) { ?>

	  <?php wp_nonce_field( basename( __FILE__ ), 'group_home_association_nonce' ); ?>
	<!-- Loop through Group Tree with the addition of checkboxes -->
	  <?php if ( class_exists( 'BP_Groups_Hierarchy' ) ) {
	    $tree = BP_Groups_Hierarchy::get_tree();
	    //print_r($tree);
	    $group_associations = get_post_meta( $object->ID, 'group_home_page_association', false ); // Use false because we want an array of associations to be returned
	    //print_r($group_associations);

	    echo '<ul class="group-tree">';
	    foreach ( $tree as $branch ) {
	      ?>
	      <li>
	        <input type="checkbox" id="group-home-page-assoc-<?php echo $branch->id ?>" name="group_home_page_association[]" value="<?php echo $branch->id ?>" <?php checked( in_array( $branch->id , $group_associations ) ); ?> />
	        <label for="group-home-page-assoc-<?php echo $branch->id ?>"><?php echo $branch->name; ?></label>
	      </li>
	      <?php
	    }
	    echo '</ul>';

	  } else {
	  	echo "BP Group Hierarchy is needed to display the group tree.";
	  }
	}

	/* Save the meta box's post metadata. */
	public function save_meta( $post_id, $post ) {

	  /* Verify the nonce before proceeding. */
	  if ( ! isset( $_POST['group_home_association_nonce'] ) || ! wp_verify_nonce( $_POST['group_home_association_nonce'], basename( __FILE__ ) ) )
	    return $post_id;

	  /* Get the post type object. */
	  $post_type = get_post_type_object( $post->post_type );

	  /* Check if the current user has permission to edit the post. */
	  if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
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


	/**
	 * Filter "map_meta_caps" to let our users do things they normally can't.
	 *
	 * @since    1.0.0
	 */
	public function add_mmc_filter() {
		if( ( bp_is_current_component( 'groups' ) && bp_is_current_action( 'admin' ) && bp_is_action_variable( $this->plugin_slug, 0 ) ) 
			|| ( isset( $_POST['action'] ) && $_POST['action'] == 'upload-attachment' )
			) {
		    add_filter( 'map_meta_cap', array( $this, 'setup_map_meta_cap' ), 14, 4 );
		}
	}

	/**
	 * Filter "map_meta_caps" to let our users do things they normally can't.
	 * This enables the media button on the post edit form (allows an ordinary user to add media).
	 *
	 * @since    1.0.0
	 */
	public function setup_map_meta_cap( $primitive_caps, $meta_cap, $user_id, $args ) {	
		// In order to upload files, a user needs to have caps for uploading and editing posts.
		// Check if this is a request we want to filter. 
		if ( ! in_array( $meta_cap, array( 'upload_files','edit_post' ) ) ) {  
	        return $primitive_caps;  
	    }

	  	// We reset the "required" caps by blanking the array for those caps.
	    $primitive_caps = array();

		return $primitive_caps;
	}


	/**
	 * Only allow users to see their own items in the media library uploader.
	 *
	 * @since    1.0.0
	 */
	public function show_users_own_attachments( $wp_query_obj ) {
	 
		// The image library is populated via an AJAX request, so we'll check for that
		if( isset( $_POST['action'] ) && $_POST['action'] == 'query-attachments' ) {

			// If the user isn't a site admin, limit the image library to only show his images.
			if( ! current_user_can( 'delete_pages' ) )
			    $wp_query_obj->set( 'author', get_current_user_id() );

		}
	}



	/**
	 * Don't interpret shortcodes on the group home page edit screen.
	 *
	 * @since    1.0.0
	 */
	public function remove_shortcode_filter_on_settings_screen() {
		if ( bp_is_current_component( 'groups' ) && bp_is_current_action( 'admin' ) && bp_is_action_variable( $this->plugin_slug, 0 ) ) {
			remove_filter( 'the_content', 'do_shortcode', 11);
		}
	}

} // End class