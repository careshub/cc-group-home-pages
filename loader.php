<?php
/*
Plugin Name: CC Group Home Pages
Description: Adds custom group home pages editable by group admins
Version: 1.4.0
Requires at least: 3.9
Tested up to: 3.9
License: GPL3
Author: David Cavins
*/

/**
 * CC BuddyPress Group Home Pages
 *
 * @package   CC BuddyPress Group Home Pages
 * @author    CARES staff
 * @license   GPL-2.0+
 * @copyright 2014 CommmunityCommons.org
 */

// Define a constant that can be checked to see if the component is installed or not.
define( 'CC_GROUP_HOME_PAGES_IS_INSTALLED', 1 );

// Define a constant that will hold the current version number of the component
// This can be useful if you need to run update scripts or do compatibility checks in the future
define( 'CC_GROUP_HOME_PAGES_VERSION', '1.3.0' );

// Define a constant that we can use to construct file paths throughout the component
define( 'CC_GROUP_HOME_PAGES_PLUGIN_DIR', dirname( __FILE__ ) );

// Do our setup after BP is loaded, but before we create the group extension.
function cc_bpghp_class_init() {
	// The main class
	require_once( dirname( __FILE__ ) . '/includes/class-CC_BPGHP.php' );
	add_action( 'bp_include', array( 'CC_BPGHP', 'get_instance' ), 21 );
}
add_action( 'bp_include', 'cc_bpghp_class_init' );
