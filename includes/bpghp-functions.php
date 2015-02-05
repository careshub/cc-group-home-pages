<?php
/**
 * CC BuddyPress Group Home Pages
 *
 * @package   CC BuddyPress Group Home Pages
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 */

/**
 * For a given group, is there a published home page?
 *
 * @since    1.0.0
 */
function ccghp_enabled_for_group( $group_id ) {
	$setting = false;

    $custom_front_query = cc_get_group_home_page_post( $group_id );

    $has_posts = $custom_front_query->have_posts();

    if ( $has_posts ) {
		$setting = true;
        // If the group is hidden, this shouldn't show for non-group members.
        if ( bp_get_group_status( groups_get_group( array( 'group_id' => $group_id ) ) ) == 'hidden' ) {
            $setting = false;
            if ( current_user_can( 'delete_pages' ) || groups_is_user_member( get_current_user_id(), $group_id ) ) {
                $setting = true;
            }
        }
    }
	return apply_filters('ccghp_enabled_for_group', $setting);
}


/**
 * For a given group, get the group home page post.
 *
 * @since    1.0.0
 */
function cc_get_group_home_page_post( $group_id = null, $status = null ) {
	$group_id = ( $group_id ) ? $group_id : bp_get_current_group_id();

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
/**
 * Check to see if we're viewing a group's home page.
 *
 * @since    1.0.0
 */
function ccghp_is_group_home_page() {
    $ccgph_class = CC_BPGHP::get_instance();

    if ( bp_is_groups_component() && bp_is_current_action( $ccgph_class->get_plugin_slug() ) )
        return true;

    return false;
}
/**
 * Check to see if we're viewing a group's home page setting screen.
 * Like: /istanbul-secrets/admin/group-home/
 *
 * @since    1.0.0
 */
function ccghp_is_settings_screen() {
    $ccgph_class = CC_BPGHP::get_instance();

    if ( bp_is_groups_component() && bp_is_current_action( 'admin' ) && bp_is_action_variable( $ccgph_class->get_plugin_slug(), 0 ) )
        return true;

    return false;
}


////////////////
/**
 * Print Filters For
 *
 * Discover what functions are attached to a given hook in WordPress.
 */
function print_filters_for( $hook = null ) {
    global $wp_filter;

    // Error handling
    if ( !$hook )
        return new WP_Error( 'no_hook_provided', __("You didn't provide a hook.") );
    if ( !isset( $wp_filter[$hook] ) )
        return new WP_Error( 'hook_doesnt_exist', __("$hook doesn't exist.") );

    // Display output
    echo '<details closed>';
    echo "<summary>Hook summary: <code>$hook</code></summary>";
    echo '<pre style="text-align:left; font-size:11px;">';
    print_r( $wp_filter[$hook] );
    echo '</pre>';
    echo '</details>';
}

