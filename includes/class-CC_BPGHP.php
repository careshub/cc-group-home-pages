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
	const VERSION = '1.3.0';

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

		$this->load_dependencies();

		// Create a custom post type for group home pages.
		add_action( 'init', array( $this, 'register_cpt_group_home_page' ) );

		// Add meta boxes on the 'add_meta_boxes' hook.
		add_action( 'add_meta_boxes', array( $this, 'add_meta_boxes' ) );
		// Save post meta on the 'save_post' hook.
		add_action( 'save_post', array( $this, 'save_meta' ), 10, 2 );

		// Set the group home page as the default page if one exists.
		add_filter( 'bp_groups_default_extension', array( $this, 'change_group_default_tab' ) );

		// Don't show the "activity" tab if a group home page exists and if the user isn't a member of the group.
		add_action( 'bp_setup_nav', array( $this, 'hide_activity_tab' ), 99 );

		// Filter "map_meta_caps" to let our users do things they normally can't, like upload media.
		add_action( 'bp_init', array( $this, 'add_mmc_filter') );

		// Don't interpret shortcodes on the group home page edit screen.
		add_action( 'bp_init', array( $this, 'remove_shortcode_filter_on_settings_screen') );

		/* Only allow users to see their own items in the media library uploader.
		 * This functionality is shared between several plugins and has been moved
		 * to a standalone plugin "CC Manage Media and Permissions"
		 */

		// Add styles & scripts to the settings screen
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_edit_styles_scripts' ) );

		// Change behavior of link button in wp_editor.
		// Add back in docs & group stories.
		add_filter( 'wp_link_query', array( $this, 'filter_link_suggestions' ), 14, 2 );

		// Edit locking for front-end editing. /////////////////////////////////
		$cc_bpghp_edit_lock = new CC_BPGHP_Edit_Lock();
		// Use WP's heartbeat API to set content locks when a user is editing a group home page from the front end.
		add_filter( 'heartbeat_received', array( $cc_bpghp_edit_lock, 'heartbeat_callback' ), 10, 3 );
		// Remove a lock when the user navigates away.
		add_action( 'wp_ajax_cc_bpghp_remove_edit_lock', array( $cc_bpghp_edit_lock, 'remove_edit_lock' ) );

		// Storing "enabled status" for a single group. ////////////////////////
		// When a post is updated/trashed, update the has_home_page groupmeta.
		add_action( 'transition_post_status', array( $this, 'update_has_home_page' ), 10, 3 );

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
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.2.0
	 * @access   private
	 */
	private function load_dependencies() {
		// Edit locking.
		require_once( dirname( __FILE__ ) . '/edit-lock.php' );

		// Helper and utility functions.
		require_once( dirname( __FILE__ ) . '/bpghp-functions.php' );

		// The group extension class.
		require( dirname( __FILE__ ) . '/class-bp-group-extension.php' );
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
	 * Register and enqueue style sheet for edit screen.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_edit_styles_scripts() {
		if ( ccghp_is_settings_screen() ) {

			wp_enqueue_style( $this->plugin_slug . '-edit-screen-styles', plugins_url( 'css/edit.css', __FILE__ ), array(), self::VERSION );

			wp_enqueue_script( 'cc_bpghp_edit', plugins_url( 'js/edit.js', __FILE__ ), array( 'jquery', 'heartbeat' ), self::VERSION );

			$strings = array(
				'pulse' => cc_bpghp_heartbeat_pulse(),
			);
			wp_localize_script( 'cc_bpghp_edit', 'cc_bpghp', $strings );
		}
	}

	/**
	 * Set the group home page as the default page if one exists.
	 *
	 * @since    1.2.0
	 */
	public function change_group_default_tab( $default_tab ){
		// Get the current group id.
		if ( $group_id = bp_get_current_group_id() ) {

			$default_tab = ccghp_enabled_for_group( $group_id ) ? $this->plugin_slug : $default_tab;

		}

		return $default_tab;
	}

	/**
	 * Don't show the "activity" tab if a group home page exists,
	 * the group is not public, and if the user isn't a member of the group.
	 *
	 * @since    1.2.0
	 */
	public function hide_activity_tab(){
		// Only fire if viewing a single group.
		if ( current_user_can( 'bp_moderate' ) || ! bp_is_groups_component() || ! $group = groups_get_current_group() ){
			return;
		}

		/* Change the behavior only if there is a group home page,
		 * the group is not public and the visitor is either not logged in or not a member.
		 */
		if ( ccghp_enabled_for_group( $group->id )
			&& bp_get_group_status( $group ) != 'public'
			&& ! groups_is_user_member( get_current_user_id(), $group->id ) ) {
			 bp_core_remove_subnav_item( bp_get_group_slug( $group ), 'home', 'groups' );
		}
	}

	/**
	 * Generate Group Home Page custom post type to populate group home pages
	 *
	 * @since    1.0.0
	 */
	public function register_cpt_group_home_page() {

		$labels = array(
			'name' => _x( 'Hub Home Pages', 'group_home_page' ),
			'singular_name' => _x( 'Hub Home Page', 'group_home_page' ),
			'add_new' => _x( 'Add New', 'group_home_page' ),
			'add_new_item' => _x( 'Add New Hub Home Page', 'group_home_page' ),
			'edit_item' => _x( 'Edit Hub Home Page', 'group_home_page' ),
			'new_item' => _x( 'New Hub Home Page', 'group_home_page' ),
			'view_item' => _x( 'View Hub Home Page', 'group_home_page' ),
			'search_items' => _x( 'Search Hub Home Pages', 'group_home_page' ),
			'not_found' => _x( 'No hub home pages found', 'group_home_page' ),
			'not_found_in_trash' => _x( 'No hub home pages found in Trash', 'group_home_page' ),
			'parent_item_colon' => _x( 'Parent Hub Home Page:', 'group_home_page' ),
			'menu_name' => _x( 'Hub Homes', 'group_home_page' ),
		);

		$args = array(
			'labels' => $labels,
			'hierarchical' => false,
			'description' => 'This post type is queried when a group home page is requested.',
			'supports' => array( 'title', 'editor', 'revisions' ),
			'public' => true,
			'show_ui' => true,
			'show_in_menu' => true,
			'menu_position' => 51,
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
	 * Add meta box to Group Home Page custom post type to associate posts with the group home page.
	 *
	 * @since    1.0.0
	 */
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

	/**
	 * Display the meta box on the post type edit page in wp-admin.
	 *
	 * @since    1.0.0
	 */
	public function output_meta_box( $object, $box ) { ?>
		<?php wp_nonce_field( basename( __FILE__ ), 'group_home_association_nonce' ); ?>
		<!-- Loop through Group Tree with the addition of checkboxes -->
		<?php if ( class_exists( 'BP_Groups_Hierarchy' ) ) {
			$tree = BP_Groups_Hierarchy::get_tree();
			// Use false below because we want an array of associations to be returned
			$group_associations = get_post_meta( $object->ID, 'group_home_page_association', false );

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

	/**
	 * Save the meta box's post metadata.
	 *
	 * @since    1.0.0
	 */
	public function save_meta( $post_id, $post ) {

		// Verify the nonce before proceeding.
		if ( ! isset( $_POST['group_home_association_nonce'] ) || ! wp_verify_nonce( $_POST['group_home_association_nonce'], basename( __FILE__ ) ) ) {
			return;
		}

		// Get the post type object.
		$post_type = get_post_type_object( $post->post_type );

		// Check if the current user has permission to edit the post.
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) ) {
			return;
		}

		// Start from scratch.
		delete_post_meta( $post_id, 'group_home_page_association' );


		if ( ! empty( $_POST['group_home_page_association'] ) && is_array( $_POST['group_home_page_association'] ) ) {
			delete_post_meta( $post_id, 'group_home_page_association' );
			foreach ( $_POST['group_home_page_association'] as $association ) {
				/* This stores multiple entries, in the event that the page is associated with multiple groups.
				 * This approach makes the meta query much more straightforward.
				 */
				add_post_meta( $post_id, 'group_home_page_association', $association );
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
		/* In order to upload media, a user needs to have caps.
		 * Check if this is a request we want to filter.
		 */
		if ( ! in_array( $meta_cap, array( 'upload_files', 'edit_post', 'delete_post' ) ) ) {
			return $primitive_caps;
		}

		/* It would be useful for a user to be able to delete her own uploaded media.
		 * If this is someone else's post, we don't want to allow deletion of that, though.
		 */
		if ( $meta_cap == 'delete_post' && in_array( 'delete_others_posts', $primitive_caps ) ) {
			return $primitive_caps;
		}

		// We pass a blank array back, meaning there's no capability required.
		$primitive_caps = array();

		return $primitive_caps;
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

	/**
	 * Change what populates the "link to existing content" box in the wp_editor instance.
	 *
	 * @since 1.1.0
	 *
	 * @return array of result posts
	 */
	function filter_link_suggestions( $results, $query ) {

		if ( ! ccghp_is_settings_screen() ) {
			return $results;
		}

		// We're replacing the suggestions, so start with a blank slate.
		$results = array();

		// Fetch allowable bp_docs, maps, reports
		$docs = $this->get_shareable_docs( $query );
		$narratives = $this->get_shareable_narratives( $query );
		$results = array_merge( $docs, $narratives );

		/* Sort the results by datetime, descending.
		 * Create the sort column array for array_multisort to use.
		 */
		foreach ( $results as $key => $value ) {
			$datetime[$key]  = $value['datetime'];
		}

		// Add $results as the last parameter, to sort by the common key
		array_multisort( $datetime, SORT_DESC, $results );

		// Return the correct records, based on the query.
		$results = array_slice( $results, $query['offset'], $query['posts_per_page'] );

		return $results;
	}

	/**
	 * Find BP Docs that could be included in a group home page.
	 *
	 * @since 1.1.0
	 *
	 * @return array of result posts
	 */
	public function get_shareable_docs( $query, $group_id = null ) {
		$group_id = ! empty( $group_id ) ? $group_id : bp_get_current_group_id();
		$good_docs = array();

		if ( ! function_exists( 'bp_docs_get_associated_item_tax_name' ) ) {
			return $good_docs;
		}

		$args = array(
			'post_type' => 'bp_doc',
			'tax_query' => array(
					'relation' => 'AND',
					array(
						'taxonomy' => bp_docs_get_associated_item_tax_name(),
						'field'    => 'slug',
						'terms'    => array( bp_docs_get_term_slug_from_group_id( $group_id ) ),
						),
					array(
						'taxonomy' => bp_docs_get_access_tax_name(),
						'field'    => 'slug',
						'terms'    => array( bp_docs_get_access_term_anyone() ),
						),
				),
			'posts_per_page' => 20,
			'post_status' => 'publish',
		);

		if ( isset( $query['s'] ) && ! empty( $query['s'] ) ) {
			$args['s'] = $query['s'];
		}

		// We're manually limiting the docs returned, so we'll short-circuit the docs protection.
		remove_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );

		$docs = new WP_Query( $args );

		// Un-short-circuit the docs protection.
		add_action( 'pre_get_posts', 'bp_docs_general_access_protection', 28 );

		foreach ( $docs->posts as $doc ) {
			$good_docs[] = array(
				'ID' 		=> $doc->ID,
				'title' 	=> $doc->post_title,
				'permalink' => get_the_permalink( $doc->ID ),
				'info' 		=> 'Doc',
				'datetime'	=> date('Ymd', strtotime( $doc->post_date) ),
				);
		}

		return $good_docs;
	}


	/**
	 * Find Hub Narratives that could be included in a group home page.
	 *
	 * @since 1.1.0
	 *
	 * @return array of result posts
	 */
	public function get_shareable_narratives( $query, $group_id = null ) {
		$group_id = ! empty( $group_id ) ? $group_id : bp_get_current_group_id();
		$retval = array();

		if ( ! function_exists( 'ccgn_get_group_term_id' ) ) {
			return $retval;
		}

		$args = array(
			'post_type' => 'group_story',
			'tax_query' => array(
				array(
					'taxonomy' => 'ccgn_related_groups',
					'field' => 'id',
					'terms' => ccgn_get_group_term_id( $group_id ),
					'include_children' => false,
					// 'operator' => 'IN'
				)
			),
			'posts_per_page' => 20,
			'post_status' => 'publish',
		);

		if ( isset( $query['s'] ) && ! empty( $query['s'] ) ) {
			$args['s'] = $query['s'];
		}

		$narratives = new WP_Query( $args );

		foreach ( $narratives->posts as $narrative ) {
			$retval[] = array(
				'ID' 		=> $narrative->ID,
				'title' 	=> $narrative->post_title,
				'permalink' => get_permalink( $narrative->ID ),
				'info' 		=> 'Hub Narrative',
				'datetime'	=> date( 'Ymd', strtotime( $narrative->post_date ) ),
			);
		}

		return $retval;
	}

	/**
	 * When a post is updated/trashed, update the has_home_page groupmeta.
	 *
	 * @since 1.3.0
	 *
	 * @param string  $new_status New post status.
	 * @param string  $old_status Old post status.
	 * @param WP_Post $post       Post object.
	 *
	 */
	public function update_has_home_page( $new_status, $old_status, $post ) {

		// Is a hub home page being changed?
		if ( 'group_home_page' != $post->post_type ) {
			return;
		}

		// What group is this associated with?
		$group_id = get_post_meta( $post->ID, 'group_home_page_association', true );

		if ( empty( $group_id ) ) {
			return;
		}

		// True if published, false otherwise--draft, trash
		if ( 'publish' == $new_status ) {
			$has_home_page = 1;
		} else {
			$has_home_page = 0;
		}

		groups_update_groupmeta( $group_id, 'cc_has_home_page', $has_home_page );
	}
} // End class