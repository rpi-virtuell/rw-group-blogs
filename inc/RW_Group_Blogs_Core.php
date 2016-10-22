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

    /**
     *
     * @since    0.0.1
     * @access  public
     * @static
     * @var string
     */
    static $activity_type = 'rwgb';

    /**
     *
     * @since    0.0.1
     * @access  public
     * @static
     */
    function init() {

        bp_register_group_extension( 'RW_Group_Blogs_Extension' );

        add_action( 'bp_get_activity_avatar_item_id',           array( 'RW_Group_Blogs_Core', 'activity_avatar_id' ) );
        add_action( 'bp_get_activity_avatar_object_groups',     array( 'RW_Group_Blogs_Core', 'activity_avatar_type' ) );
        add_action( 'bp_get_activity_avatar_object_activity',   array( 'RW_Group_Blogs_Core', 'activity_avatar_type' ) );
        add_action( 'bp_group_activity_filter_options',         array( 'RW_Group_Blogs_Core', 'activity_add_filter' ) );
        add_action( 'bp_activity_filter_options',               array( 'RW_Group_Blogs_Core', 'activity_add_filter' ) );
        add_action( 'groups_screen_group_home',                 array( 'RW_Group_Blogs_Core', 'refetch' ) );
        add_action( 'wp_ajax_refetch_groupblogs',               array( 'RW_Group_Blogs_Core', 'ajax_refresh' ) );
        add_action( 'rw_group_blogs_cron',                      array( 'RW_Group_Blogs_Core', 'cron_refresh' ) );

    }


    /**
     *
     * @since    0.0.1
     * @access  public
     * @static
     * @param $var
     * @return mixed
     */
    function activity_avatar_id( $var ) {

        global $activities_template;
        if ( $activities_template->activity->type == RW_Group_Blogs_Core::$activity_type ) {
            return $activities_template->activity->item_id;
        }

        return $var;

    }

    /**
     *
     * @since    0.0.1
     * @access  public
     * @static
     * @param $var
     * @return string
     */
    function activity_avatar_type( $var ) {
        if ( $var == RW_Group_Blogs_Core::$activity_type ) {
            return 'group';
        } else {
            return $var;
        }
    }

    /**
     *
     * @since    0.0.1
     * @access  public
     * @static
     */
    function activity_add_filter() { ?>
        <option value="<?php echo RW_Group_Blogs_Core::$activity_type; ?>"><?php _e( 'RW External Blogs', RW_Group_Blogs::$textdomain ) ?></option><?php
    }

    /**
     *
     * @since    0.0.1
     * @access  public
     * @static
     * @param bool $group_id
     * @return bool
     */

    function fetch_group_feeds( $group_id = false ) {
        global $bp;

        include_once( ABSPATH . 'wp-includes/rss.php' );

        if ( empty( $group_id ) )
            $group_id = $bp->groups->current_group->id;
        if ( $group_id == $bp->groups->current_group->id )
            $group = $bp->groups->current_group;
        else
            $group = new BP_Groups_Group( $group_id );
        if ( !$group )
            return false;

        $group_blogs = groups_get_groupmeta( $group_id, 'rw-group-blogs-feeds' );

        /* Set the visibility */
        $hide_sitewide = ( 'public' != $group->status ) ? true : false;

        foreach ( (array) $group_blogs as $feed_url ) {

            $rss = fetch_feed( trim( $feed_url ) );

            if (!is_wp_error($rss) ) {
                $maxitems = $rss->get_item_quantity( 10 );
                $rss_items = $rss->get_items( 0, $maxitems );

                foreach ( $rss_items as $item ) {;
                    $key = $item->get_date( 'U' );
                    $items[$key]['title'] = $item->get_title();
                    $items[$key]['subtitle'] = $item->get_title();
                    //$items[$key]['author'] = $item->get_author()->get_name();
                    $items[$key]['blogname'] = $item->get_feed()->get_title();
                    $items[$key]['link'] = $item->get_permalink();
                    $items[$key]['blogurl'] = $item->get_feed()->get_link();
                    $items[$key]['description'] = $item->get_description();
                    $items[$key]['source'] = $item->get_source();
                    $items[$key]['copyright'] = $item->get_copyright();
                }
            }
        }

        if ( $items ) {
            ksort( $items );
            $items = array_reverse( $items, true );
        } else {
            return false;
        }


        /* Record found blog posts in activity streams */
        foreach ( (array) $items as $post_date => $post ) {

            $activity_action = sprintf( __( 'Blog: %s from %s in the group %s', RW_Group_Blogs::$textdomain ), '<a class="feed-link" href="' . esc_attr( $post['link'] ) . '">' . esc_attr( $post['title'] ) . '</a>', '<a class="feed-author" href="' . esc_attr( $post['blogurl'] ) . '">' . attribute_escape( $post['blogname'] ) . '</a>', '<a href="' . bp_get_group_permalink( $group ) . '">' . attribute_escape( $group->name ) . '</a>' );

            $activity_content = '<div>' . strip_tags( bp_create_excerpt( $post['description'], 175 ) ) . '</div>';
            $activity_content = apply_filters( 'rw_group_blogs_activity_content', $activity_content, $post, $group );

            $id = bp_activity_get_activity_id( array( 'user_id' => false, 'action' => $activity_action, 'component' => $bp->groups->id, 'type' => RW_Group_Blogs_Core::$activity_type, 'item_id' => $group_id, 'secondary_item_id' => wp_hash( $post['blogurl'] ) ) );

            if ( $id == NULL ) {
                // Add new record
                groups_record_activity(array(
                    'id' => $id,
                    'user_id' => false,
                    'action' => $activity_action,
                    'content' => $activity_content,
                    'primary_link' => $item->get_link(),
                    'type' => RW_Group_Blogs_Core::$activity_type,
                    'item_id' => $group_id,
                    'secondary_item_id' => wp_hash($post['blogurl']),
                    'recorded_time' => gmdate("Y-m-d H:i:s"),
                    'hide_sitewide' => $hide_sitewide
                ));
            }
        }

        return $items;
    }

    /**
     *
     * @since    0.0.1
     * @access  public
     * @static
     */
    function refetch() {
        global $bp;

        $last_refetch = groups_get_groupmeta( $bp->groups->current_group->id, 'rw-group-blogs-lastupdate' );
        $meta = groups_get_groupmeta( $bp->groups->current_group->id, 'fetchtime' );

        $fetch_time = !empty( $meta ) ? $meta : '30' ;

        if ( strtotime( gmdate( "Y-m-d H:i:s" ) ) >= strtotime( '+' .$fetch_time. ' minutes', strtotime( $last_refetch ) ) )
            add_action( 'wp_footer', array( 'RW_Group_Blogs_Core', 'footer_refetch')  );

    }

    /**
     *
     * @since    0.0.1
     * @access  public
     * @static
     */
    function footer_refetch() {
        global $bp; ?>

        <script type="text/javascript">
            jQuery(document).ready( function() {

                jQuery.post( ajaxurl, {
                        action: 'refetch_groupblogs',
                        'group_id': <?php echo $bp->groups->current_group->id ?>
                    },
                    function(response){

                    });
            });
        </script><?php
        groups_update_groupmeta( $bp->groups->current_group->id, 'bp_groupblogs_lastupdate', gmdate( "Y-m-d H:i:s" ) );
    }


    /**
     *
     * @since    0.0.1
     * @access  public
     * @static
     */
    function ajax_refresh() {
        RW_Group_Blogs_Core::fetch_group_feeds( $_POST['group_id'] );
    }

    /**
     *
     * @since    0.0.1
     * @access  public
     * @static
     */
    function cron_refresh() {
        global $bp, $wpdb;

        $group_ids = $wpdb->get_col( $wpdb->prepare( "SELECT group_id FROM " . $bp->groups->table_name_groupmeta . " WHERE meta_key = %s", 'rw-group-blogs-feeds') );
        foreach( $group_ids as $group_id ) {
            RW_Group_Blogs_Core::fetch_group_feeds($group_id);
        }
    }


}
