<?php

/**
 * Class RW_Group_Blogs_Core
 *
 * Core functions
 *
 * @package   RW Group Blogs
 * @author    Frank Staude
 * @license   GPL-2.0+
 * @link      https://github.com/rpi-virtuell/rw-group-blogs
 */

class RW_Group_Blogs_Core {

    var $activity_type = 'rwgb';

    function init() {

        add_action( 'bp_get_activity_avatar_item_id',           array( 'RW_Group_Blogs_Core', 'activity_avatar_id' ) );
        add_action( 'bp_get_activity_avatar_object_groups',     array( 'RW_Group_Blogs_Core', 'activity_avatar_type' ) );
        add_action( 'bp_get_activity_avatar_object_activity',   array( 'RW_Group_Blogs_Core', 'activity_avatar_type' ) );
        add_action( 'bp_group_activity_filter_options',         array( 'RW_Group_Blogs_Core', 'activity_add_filter' ) );
        add_action( 'bp_activity_filter_options',               array( 'RW_Group_Blogs_Core', 'activity_add_filter' ) );
    }


    function activity_avatar_id( $var ) {
        global $activities_template;

        if ( $activities_template->activity->type == RW_Group_Blogs_Core::$activity_type ) {
            return $activities_template->activity->item_id;
        }

        return $var;

    }


    function activity_avatar_type( $var ) {
        global $activities_template;

        if ( $activities_template->activity->type == RW_Group_Blogs_Core::$activity_type ) {
            return 'group';
        } else {
            return $var;
        }
    }


    function activity_add_filter() { ?>
        <option value="exb"><?php _e( 'RW External Blogs', RW_Group_Blogs::$textdomain ) ?></option><?php
    }


}