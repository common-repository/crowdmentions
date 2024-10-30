<?php
/**
 * Action hooks
 *
 * @package Crowdmentions
 * @subpackage Actions
 */

add_action( 'bp_init',                                      'crowdmentions_i18n'                                                   );
add_action( 'bp_activity_after_save',                       'crowdmentions_activity_after_save_activity_update'                    );
add_action( 'bp_activity_after_save',                       'crowdmentions_activity_after_save_group_activity_update'              );
add_action( 'bp_activity_after_save',                       'crowdmentions_activity_after_save_activity_comment'                   );
add_action( 'bp_activity_after_save',                       'crowdmentions_activity_after_save_group_activity_comment'             );
add_action( 'bp_activity_screen_single_activity_permalink', 'crowdmentions_mark_notifications_group_single_activity_visited'       );
add_action( 'bp_activity_deleted_activities',               'crowdmentions_delete_notifications_group_activity_item_deleted'       );
add_action( 'groups_delete_group',                          'crowdmentions_delete_notifications_group_deleted'                     );
add_action( 'groups_leave_group',                           'crowdmentions_delete_notifications_group_exit',                 10, 2 );
add_action( 'groups_remove_member',                         'crowdmentions_delete_notifications_group_exit',                 10, 2 );
add_action( 'groups_ban_member',                            'crowdmentions_delete_notifications_group_exit',                 10, 2 );
