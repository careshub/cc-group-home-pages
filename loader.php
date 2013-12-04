<?php
/*
Plugin Name: CC Group Home Pages
Description: Adds custom group home pages editable by group admins
Version: 0.1
Requires at least: 3.6
Tested up to: 3.6
License: GPL3
Author: David Cavins
*/

// Define a constant that can be checked to see if the component is installed or not.
define( 'CC_GROUP_HOME_PAGES_IS_INSTALLED', 1 );

// Define a constant that will hold the current version number of the component
// This can be useful if you need to run update scripts or do compatibility checks in the future
define( 'CC_GROUP_HOME_PAGES_VERSION', '.1' );

// Define a constant that we can use to construct file paths throughout the component
define( 'CC_GROUP_HOME_PAGES_PLUGIN_DIR', dirname( __FILE__ ) );

/* Only load the component if BuddyPress is loaded and initialized. */
function bp_startup_group_home_page_extension() {
	// Because our loader file uses BP_Component, it requires BP 1.5 or greater.
	if ( version_compare( BP_VERSION, '1.5', '>' ) )
		require( dirname( __FILE__ ) . '/includes/cc-group-home-pages.php' );
}
add_action( 'bp_include', 'bp_startup_group_home_page_extension' );