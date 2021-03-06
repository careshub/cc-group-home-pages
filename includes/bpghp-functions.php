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
    $setting = (bool) groups_get_groupmeta( $group_id, 'cc_has_home_page' );
	return apply_filters( 'ccghp_enabled_for_group', $setting );
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
       'post_status' => $status == 'draft' ? array( 'auto-draft', 'pending', 'draft', 'publish' ) : array( 'publish' ),
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
 * For a given group, get the group home page post's ID.
 *
 * @since    1.2.0
 */
function cc_get_group_home_page_post_id( $group_id = null, $status = null ) {
    $group_id = ( $group_id ) ? $group_id : bp_get_current_group_id();

    $args =  array(
       'post_type'   => 'group_home_page',
       'posts_per_page' => '1',
       'post_status' => $status == 'draft' ? array( 'auto-draft', 'pending', 'draft', 'publish' ) : array( 'publish' ),
       'fields' => 'ids',
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

    return current( $custom_front_query->posts );
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