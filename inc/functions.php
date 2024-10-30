<?php
/**
 * Function definitions
 *
 * @package Crowdmentions
 * @subpackage Functions
 */

/**
 * Load the plugin textdomain.
 *
 * @since 1.0.0
 */
function crowdmentions_i18n() {
	load_plugin_textdomain( 'crowdmentions' );
}

/**
 * Get the custom component name.
 *
 * @since 1.0.0
 *
 * @return string
 */
function crowdmentions_get_component_name() {
	return 'mentions';
}

/**
 * Register a custom component.
 *
 * @since 1.0.0
 *
 * @param array $component_names Registered component names.
 * @param array $active_components Active components.
 * @return array
 */
function crowdmentions_get_registered_components( $component_names, $active_components ) {

	$component_names[] = crowdmentions_get_component_name();

	return $component_names;
}

/**
 * Search a given string for mention commands.
 *
 * @since 1.0.0
 *
 * @param string $string The string to search.
 * @return array The mention commands found.
 */
function crowdmentions_find_mentions( $string ) {

	preg_match_all( '/[@]+([A-Za-z0-9-_\.@]+)\b/', $string, $mentions );

	// Remove duplicates.
	$mentions = array_unique( $mentions[1] );

	// Convert all matches to lowercase and return.
	return array_map( 'strtolower', $mentions );
}

/**
 * Process an activity update.
 *
 * @since 1.0.0
 *
 * @param object $activity The activity item being saved.
 */
function crowdmentions_activity_after_save_activity_update( $activity ) {

	if ( $activity->type !== 'activity_update' ) {
		return;
	}

	if ( $activity->component !== 'activity' ) {
		return;
	}

	// Find mentions in the activity item's content.
	$mentions = crowdmentions_find_mentions( $activity->content );

	foreach( (array) array_values( $mentions ) as $mention ) {

		if ( ! bp_is_active( 'groups' ) && $mention === 'group' ) {
			continue;
		}

		if ( ! bp_is_active( 'groups' ) && $mention === 'moderators' ) {
			continue;
		}

		if ( ! bp_is_active( 'groups' ) && $mention === 'administrators' ) {
			continue;
		}

		if ( ! bp_is_active( 'friends' ) && $mention === 'friends' ) {
			continue;
		}

		switch ( $mention ) {
			case 'friends':
				$args = array(
					'user_ids'          => BP_Friends_Friendship::get_friend_user_ids( $activity->user_id, false, false ),
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->id,
					'secondary_item_id' => NULL,
					'component_action'  => 'mention_friends_' . $activity->id
				);
				break;

			default:
				// Skip to the next iteration.
				continue 2;
		}

		// Send notifications.
		crowdmentions_send_notifications( $args );
	}
}

/**
 * Process a group activity update.
 *
 * @since 1.0.0
 *
 * @param object $activity The activity item being saved.
 */
function crowdmentions_activity_after_save_group_activity_update( $activity ) {

	if ( $activity->type !== 'activity_update' ) {
		return;
	}

	if ( $activity->component !== 'groups' ) {
		return;
	}

	// Find mentions in the activity item's content.
	$mentions = crowdmentions_find_mentions( $activity->content );

	foreach( (array) array_values( $mentions ) as $mention ) {

		if ( ! bp_is_active( 'groups' ) && $mention === 'group' ) {
			continue;
		}

		if ( ! bp_is_active( 'groups' ) && $mention === 'moderators' ) {
			continue;
		}

		if ( ! bp_is_active( 'groups' ) && $mention === 'administrators' ) {
			continue;
		}

		if ( ! bp_is_active( 'friends' ) && $mention === 'friends' ) {
			continue;
		}

		switch ( $mention ) {
			case 'group':
				$args = array(
					'user_ids'          => BP_Groups_Member::get_group_member_ids( $activity->item_id ),
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->id,
					'secondary_item_id' => $activity->item_id,
					'component_action'  => 'mention_group_' . $activity->id
				);
				break;

			case 'moderators':
				$user_ids = [];
				$mods = BP_Groups_Member::get_group_moderator_ids( $activity->item_id );

				// Get an array of moderator IDs.
				foreach ( (array) $mods as $mod ) {
					$user_ids[] = $mod->user_id;
				}
				$args = array(
					'user_ids'          => $user_ids,
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->id,
					'secondary_item_id' => $activity->item_id,
					'component_action'  => 'mention_moderators_' . $activity->id
				);
				break;

			case 'administrators':
				$user_ids = [];
				$admins = BP_Groups_Member::get_group_administrator_ids( $activity->item_id );

				// Get an array of administrator IDs.
				foreach ( (array) $admins as $admin ) {
					$user_ids[] = $admin->user_id;
				}
				$args = array(
					'user_ids'          => $user_ids,
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->id,
					'secondary_item_id' => $activity->item_id,
					'component_action'  => 'mention_administrators_' . $activity->id
				);
				break;

			case 'friends':
				$friend_ids = BP_Friends_Friendship::get_friend_user_ids( $activity->user_id, false, false );
				$args = array(
					'user_ids'          => array_intersect( BP_Groups_Member::get_group_member_ids( $activity->item_id ), $friend_ids ),
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->id,
					'secondary_item_id' => $activity->item_id,
					'component_action'  => 'mention_friends_' . $activity->id
				);
				break;

			default:
				// Skip to the next iteration.
				continue 2;
		}

		// Send notifications.
		crowdmentions_send_notifications( $args );
	}
}

/**
 * Process an activity comment.
 *
 * @since 1.0.0
 *
 * @param object $activity The activity item being saved.
 */
function crowdmentions_activity_after_save_activity_comment( $activity ) {

	if ( $activity->type !== 'activity_comment' ) {
		return;
	}

	$root_activity = new BP_Activity_Activity( $activity->item_id );

	if ( $root_activity->component !== 'activity' ) {
		return;
	}

	// Find mentions in the activity item's content.
	$mentions = crowdmentions_find_mentions( $activity->content );

	foreach( (array) array_values( $mentions ) as $mention ) {

		if ( ! bp_is_active( 'groups' ) && $mention === 'group' ) {
			continue;
		}

		if ( ! bp_is_active( 'groups' ) && $mention === 'moderators' ) {
			continue;
		}

		if ( ! bp_is_active( 'groups' ) && $mention === 'administrators' ) {
			continue;
		}

		if ( ! bp_is_active( 'friends' ) && $mention === 'friends' ) {
			continue;
		}

		switch ( $mention ) {
			case 'friends':
				$args = array(
					'user_ids'          => BP_Friends_Friendship::get_friend_user_ids( $activity->user_id, false, false ),
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->id,
					'secondary_item_id' => NULL,
					'component_action'  => 'mention_friends_' . $activity->id
				);
				break;

			default:
				// Skip to the next iteration.
				continue 2;
		}

		// Send notifications.
		crowdmentions_send_notifications( $args );
	}
}

/**
 * Process a group activity comment.
 *
 * @since 1.0.0
 *
 * @param object $activity The activity item being saved.
 */
function crowdmentions_activity_after_save_group_activity_comment( $activity ) {

	if ( $activity->type !== 'activity_comment' ) {
		return;
	}

	$root_activity = new BP_Activity_Activity( $activity->item_id );

	if ( $root_activity->component !== 'groups' ) {
		return;
	}

	// Find mentions in the activity item's content.
	$mentions = crowdmentions_find_mentions( $activity->content );

	foreach( (array) array_values( $mentions ) as $mention ) {

		if ( ! bp_is_active( 'groups' ) && $mention === 'group' ) {
			continue;
		}

		if ( ! bp_is_active( 'groups' ) && $mention === 'moderators' ) {
			continue;
		}

		if ( ! bp_is_active( 'groups' ) && $mention === 'administrators' ) {
			continue;
		}

		if ( ! bp_is_active( 'friends' ) && $mention === 'friends' ) {
			continue;
		}

		switch ( $mention ) {
			case 'group':
				$args = array(
					'user_ids'          => BP_Groups_Member::get_group_member_ids( $root_activity->item_id ),
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->id,
					'secondary_item_id' => $root_activity->item_id,
					'component_action'  => 'mention_group_' . $activity->id
				);
				break;

			case 'moderators':
				$user_ids = [];
				$mods = BP_Groups_Member::get_group_moderator_ids( $root_activity->item_id );

				// Get an array of moderator IDs.
				foreach ( (array) $mods as $mod ) {
					$user_ids[] = $mod->user_id;
				}
				$args = array(
					'user_ids'          => $user_ids,
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->id,
					'secondary_item_id' => $root_activity->item_id,
					'component_action'  => 'mention_moderators_' . $activity->id
				);
				break;

			case 'administrators':
				$user_ids = [];
				$admins = BP_Groups_Member::get_group_administrator_ids( $root_activity->item_id );

				// Get an array of administrator IDs.
				foreach ( (array) $admins as $admin ) {
					$user_ids[] = $admin->user_id;
				}
				$args = array(
					'user_ids'          => $user_ids,
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->id,
					'secondary_item_id' => $root_activity->item_id,
					'component_action'  => 'mention_administrators_' . $activity->id
				);
				break;

			case 'friends':
				$friend_ids = BP_Friends_Friendship::get_friend_user_ids( $activity->user_id, false, false );
				$args = array(
					'user_ids'          => array_intersect( BP_Groups_Member::get_group_member_ids( $root_activity->item_id ), $friend_ids ),
					'user_id'           => $activity->user_id,
					'item_id'           => $activity->id,
					'secondary_item_id' => $root_activity->item_id,
					'component_action'  => 'mention_friends_' . $activity->id
				);
				break;

			default:
				// Skip to the next iteration.
				continue 2;
		}

		// Send notifications.
		crowdmentions_send_notifications( $args );
	}
}

/**
 * Send notifications.
 *
 * @since 1.0.0
 *
 * @param array $params The data used to add a notification.
 */
function crowdmentions_send_notifications( $params ) {

	// Exclude self from notifications.
	$key = array_search( $params['user_id'], $params['user_ids'] );
	if ( $key !== false ) {
		array_splice( $params['user_ids'], $key, 1 );
	}

	// Bail if no users to notify.
	if ( empty( $params['user_ids'] ) ) {
		return;
	}

	$args = array(
		'item_id'           => $params['item_id'],
		'secondary_item_id' => $params['secondary_item_id'],
		'component_action'  => $params['component_action'],
		'component_name'    => crowdmentions_get_component_name(),
		'date_notified'     => bp_core_current_time(),
		'is_new'            => 1
	);

	// Send a notification to each user.
	foreach ( $params['user_ids'] as $user_id ) {
		$args['user_id'] = $user_id;
		bp_notifications_add_notification( $args );
	}
}

/**
 * Format a notification.
 *
 * @since 1.0.0
 *
 * @param string $component_action The action associated with the notification.
 * @param int $item_id The ID of the item associated with the notification.
 * @param int $secondary_item_id The ID of the secondary item associated with the notification.
 * @param int $item_count The item count.
 * @param string $format The return type.
 * @return string|array The content of the notification.
 */
function crowdmentions_format_notification( $component_action, $item_id, $secondary_item_id, $item_count, $format = 'string' ) {

	$activity = new BP_Activity_Activity( $item_id );

	$display_name = bp_core_get_user_displayname( $activity->user_id );

	switch ( $component_action ) {
		case 'mention_group_' . $item_id:
			$link = bp_activity_get_permalink( $item_id );
			$text = sprintf( __( '%1$s mentioned group members', 'crowdmentions' ), $display_name );
			break;

		case 'mention_moderators_' . $item_id:
			$link = bp_activity_get_permalink( $item_id );
			$text = sprintf( __( '%1$s mentioned group moderators', 'crowdmentions' ), $display_name );
			break;

		case 'mention_administrators_' . $item_id:
			$link = bp_activity_get_permalink( $item_id );
			$text = sprintf( __( '%1$s mentioned group administrators', 'crowdmentions' ), $display_name );
			break;

		case 'mention_friends_' . $item_id:
			$link = bp_activity_get_permalink( $item_id );
			$text = sprintf( __( '%1$s mentioned his or her friends', 'crowdmentions' ), $display_name );
			break;

		default:
			return $component_action;
	}

	// Return a string if asked.
	if ( $format == 'string' ) {
		return '<a href="' . esc_url( $link ) . '">' . esc_html( $text ) . '</a>';
	}

	// Return an array.
	return array(
		'link' => esc_url( $link ),
		'text' => esc_html( $text )
	);
}

/**
 * Mark a notification.
 *
 * @since 1.0.0
 *
 * @param int|bool $user_id The ID of the user associated with the notification.
 * @param int|bool $item_id The ID of the item associated with the notification.
 * @param int|bool $secondary_item_id The ID of the secondary item associated with the notification.
 * @param string|bool $component_action The action associated with the notification.
 * @param bool $is_new The notification's status.
 */
function crowdmentions_mark_notification( $user_id = false, $item_id = false, $secondary_item_id = false, $component_action = false, $is_new = true ) {

	$data = array(
		'is_new' => $is_new
	);

	$where = array(
		'user_id'           => $user_id,
		'item_id'           => $item_id,
		'secondary_item_id' => $secondary_item_id,
		'component_action'  => $component_action,
		'component_name'    => crowdmentions_get_component_name()
	);

	BP_Notifications_Notification::update( $data, $where );
}

/**
 * Delete a notification.
 *
 * @since 1.0.0
 *
 * @param int|bool $user_id The ID of the user associated with the notification.
 * @param int|bool $item_id The ID of the item associated with the notification.
 * @param int|bool $secondary_item_id The ID of the secondary item associated with the notification.
 * @param string|bool $component_action The action associated with the notification.
 */
function crowdmentions_delete_notification( $user_id = false, $item_id = false, $secondary_item_id = false, $component_action = false ) {

	$where = array(
		'user_id'           => $user_id,
		'item_id'           => $item_id,
		'secondary_item_id' => $secondary_item_id,
		'component_action'  => $component_action,
		'component_name'    => crowdmentions_get_component_name()
	);

	BP_Notifications_Notification::delete( $where );
}

/**
 * Mark read a notification after the activity item is seen.
 *
 * @since 1.0.0
 *
 * @param object $activity The activity item info.
 */
function crowdmentions_mark_notifications_group_single_activity_visited( $activity ) {

	if ( ! bp_is_current_action( $activity->id ) ) {
		return;
	}

	$comments = BP_Activity_Activity::get_activity_comments( $activity->id, $activity->mptt_left, $activity->mptt_right );

	if ( $comments === false ) {
		crowdmentions_mark_notification( bp_loggedin_user_id(), false, false, 'mention_group_' . $activity->id, false );
		crowdmentions_mark_notification( bp_loggedin_user_id(), false, false, 'mention_moderators_' . $activity->id, false );
		crowdmentions_mark_notification( bp_loggedin_user_id(), false, false, 'mention_administrators_' . $activity->id, false );
		crowdmentions_mark_notification( bp_loggedin_user_id(), false, false, 'mention_friends_' . $activity->id, false );
	} else {
		foreach( $comments as $comment ) {
			crowdmentions_mark_notification( bp_loggedin_user_id(), false, false, 'mention_group_' . $comment->id, false );
			crowdmentions_mark_notification( bp_loggedin_user_id(), false, false, 'mention_moderators_' . $comment->id, false );
			crowdmentions_mark_notification( bp_loggedin_user_id(), false, false, 'mention_administrators_' . $comment->id, false );
			crowdmentions_mark_notification( bp_loggedin_user_id(), false, false, 'mention_friends_' . $comment->id, false );
		}
	}
}

/**
 * Delete notification(s) when associated activity item is deleted.
 *
 * @since 1.0.0
 *
 * @param array $activity_ids_deleted Affected activity item IDs.
 */
function crowdmentions_delete_notifications_group_activity_item_deleted( $activity_ids_deleted = array() ) {

	if ( empty( $activity_ids_deleted ) ) {
		return;
	}

	foreach ( $activity_ids_deleted as $activity_id ) {
		$activity = new BP_Activity_Activity( $activity_id );

		crowdmentions_delete_notification( false, $activity_id, false, false );
	}
}

/**
 * Delete mention notification(s) associated with a deleted group.
 *
 * @since 1.0.0
 *
 * @param int $group_id The ID of the deleted group.
 */
function crowdmentions_delete_notifications_group_deleted( $group_id ) {
	crowdmentions_delete_notification( false, false, $group_id, false );
}

/**
 * Delete mention notification(s) if a member leaves a group.
 *
 * @since 1.0.0
 *
 * @param int $group_id The ID of the group.
 * @param int $user_id The ID of the user leaving the group.
 */
function crowdmentions_delete_notifications_group_exit( $group_id, $user_id ) {
	crowdmentions_delete_notification( $user_id, false, $group_id, false );
}
