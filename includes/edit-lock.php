<?php
/**
 * Functionality related to Edit Lock
 *
 * @since 1.2.0
 */
/**
 * CC BuddyPress Group Home Pages
 *
 * @package   CC BuddyPress Group Home Pages
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 */

class CC_BPGHP_Edit_Lock extends CC_BPGHP {

	/**
	 * Initialize the extension class
	 *
	 * @since     1.2.0
	 */
	public function __construct() {
	}

	/**
	 * Handle Heartbeat API pings
	 *
	 * @since 1.2.0
	 * @todo use two actions: renew heartbeat lease, and just check the heartbeat.
	 */
	function heartbeat_callback( $response, $data, $screen_id ) {
		if ( empty( $data['ccghp_post_id'] ) || empty( $data['ccghp_lock_action'] ) ) {
			return $response;
		}

		$post_id = intval( $data['ccghp_post_id'] );

		if ( ! $post_id ) {
			return $response;
		}

		if ( 'group_home_page' != get_post_type( $post_id ) ) {
			return $response;
		}

		$user_id = bp_loggedin_user_id();

		$lock = cc_bpghp_check_post_lock( $post_id );

		// No lock, or belongs to the current user
		if ( empty( $lock ) || $lock == $user_id ) {
			// Only renew the lock if the user is actively editing the post.
			if ( 'renew_post_lock' == $data['ccghp_lock_action'] ) {
				$time = time();
				$lstring = "$time:$user_id";
				update_post_meta( $post_id, '_edit_lock', $lstring );
			}

			$response['ccghp_locked_by'] = 0;

		// Someone else is editing.
		} else {
			// We return who's got it locked for a status message.
			$response['ccghp_locked_by'] = bp_core_get_user_displayname( $lock );
		}

		return $response;
	}

	/**
	 * AJAX handler for remove_edit_lock action.
	 *
	 * This function is called when a user leaves the group home page edit screen.
	 *
	 * @since 1.2.0
	 */
	function remove_edit_lock() {
		$post_id = isset( $_POST['ccghp_post_id'] ) ? $_POST['ccghp_post_id'] : false;

		if ( ! $post_id ) {
			wp_die( 0 );
		}

		if ( ! $lock = get_post_meta( $post_id, '_edit_lock', true ) ) {
			wp_die( 0 );
		}

		$lock = array_map( 'absint', explode( ':', $lock ) );

		// Only continue if the acting user is the lock holder.
		if ( $lock[1] != get_current_user_id() ) {
			wp_die( 0 );
		}

		// Set an expired time for the post lock.
		$new_lock = ( time() - 155 ) . ':' . $lock[1];

		update_post_meta( $post_id, '_edit_lock', $new_lock, implode( ':', $lock ) );
		wp_die( 1 );
	}
}

// Functions in the global scope

/**
 * Check to see if the post is currently being edited by another user.
 *
 * This is a verbatim copy of wp_check_post_lock(), which is only available
 * in the admin
 *
 * @since 1.2.0
 *
 * @param int $post_id ID of the post to check for editing
 * @return bool|int False: not locked or locked by current user. Int: user ID of user with lock.
 */
function cc_bpghp_check_post_lock( $post_id ) {
	if ( ! $post = get_post( $post_id ) ) {
		return false;
	}

	if ( ! $lock = get_post_meta( $post_id, '_edit_lock', true ) ) {
		return false;
	}

	$lock = explode( ':', $lock );
	$time = $lock[0];
	$user_id = isset( $lock[1] ) ? $lock[1] : get_post_meta( $post_id, '_edit_last', true );

	$heartbeat_interval = cc_bpghp_heartbeat_pulse();

	// Bail out of the lock if four pings have been missed (one minute, by default)
	$time_window = apply_filters( 'cc_bpghp_post_lock_interval', $heartbeat_interval * 4 );

	if ( $time && $time > time() - $time_window && $user_id != get_current_user_id() ) {
		return $user_id;
	}

	return false;
}

/**
 * CC Group Home Pages heartbeat interval.
 *
 * @since 1.2.0
 *
 * @return int
 */
function cc_bpghp_heartbeat_pulse() {
	// Check whether a global heartbeat already exists
	$heartbeat_settings = apply_filters( 'heartbeat_settings', array() );
	if ( ! empty( $heartbeat_settings['interval'] ) ) {
		if ( 'fast' === $heartbeat_settings['interval'] ) {
			$pulse = 5;
		} else {
			$pulse = intval( $heartbeat_settings['interval'] );
		}
	}

	// Fallback
	if ( empty( $pulse ) ) {
		$pulse = 15;
	}

	// Filter here to specify a pulse frequency
	$pulse = intval( apply_filters( 'cc_bpghp_activity_pulse', $pulse ) );

	return $pulse;
}