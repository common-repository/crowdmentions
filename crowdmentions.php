<?php
/**
 * Plugin Name: Crowdmentions
 * Plugin URI: https://github.com/henrywright/crowdmentions
 * Description: Use a command to mention a collection of members on your BuddyPress site.
 * Version: 1.0.3
 * Author: Henry Wright
 * Author URI: http://about.me/henrywright
 * Text Domain: crowdmentions
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

/**
 * Crowdmentions
 *
 * @package Crowdmentions
 */

/**
 * Require the plugin's files.
 *
 * @since 1.0.0
 */
function crowdmentions_init() {

	if ( ! bp_is_active( 'activity' ) || ! bp_is_active( 'notifications' ) ) {
		return;
	}

	if ( ! bp_activity_do_mentions() ) {
		return;
	}

	require_once dirname( __FILE__ ) . '/inc/functions.php';
	require_once dirname( __FILE__ ) . '/inc/actions.php';
	require_once dirname( __FILE__ ) . '/inc/filters.php';
}
add_action( 'bp_include', 'crowdmentions_init' );
