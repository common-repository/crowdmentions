<?php
/**
 * Filter hooks
 *
 * @package Crowdmentions
 * @subpackage Filters
 */

add_filter( 'bp_notifications_get_registered_components',  'crowdmentions_get_registered_components', 10, 2 );
add_filter( 'bp_notifications_get_notifications_for_user', 'crowdmentions_format_notification',       10, 5 );
