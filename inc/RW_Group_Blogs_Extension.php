<?php

/**
 * Class RW_Group_Blogs_Extension
 *
 * Core functions
 *
 * @package   RW Group Blogs
 * @author    Frank Staude
 * @license   GPL-2.0+
 * @link      https://github.com/rpi-virtuell/rw-group-blogs
 */


if ( class_exists('BP_Group_Extension' ) ) {

    class RW_Group_Blogs_Extension extends BP_Group_Extension
    {

        /**
         * RW_Group_Blogs_Extension constructor.
         *
         * @since    0.0.1
         * @access  public
         * @static
         */
        function __construct()
        {

            $args = array(
                'slug' => 'rw-external-blog-feeds',
                'name' => __('External Blogs', RW_Group_Blogs::$textdomain),
                'enable_nav_item' => false,
            );

            parent::init($args);
        }

        /**
         *
         * @since    0.0.1
         * @access  public
         * @static
         * @return bool
         */
        function create_screen()
        {
            global $bp;
            if ( ! bp_is_group_creation_step( $this->slug ) ) {
                return false;
            }
            $fetch = groups_get_groupmeta($bp->groups->current_group->id, 'rw-group-blogs-fetchtime');
            $times = array('10', '15', '20', '30', '60');
            echo '<label for="fetch-time">' . _e('Refresh time:', RW_Group_Blogs::$textdomain ) . '</label>';
            echo "<select id='fetch-time' name='fetch-time'>";
            $default = __('Default', RW_Group_Blogs::$textdomain );
            echo "<option value='30'>$default</option>";
            foreach ($times as $time) {
                $selected = ($fetch == $time) ? 'selected="selected"' : '';
                echo "<option value='$time' $selected>$time</option>";
            }
            echo "</select>  ";
            ?>
            <p><?php _e(
                    "Add RSS feeds of blogs you'd like to attach to this group in the box below.
				 Any future posts on these blogs will show up on the group page and be recorded
				 in activity streams.", RW_Group_Blogs::$textdomain) ?>
            </p>

            <p>

                <span class="desc"><?php _e("Seperate URL's with commas.", RW_Group_Blogs::$textdomain) ?></span>
                <label for="blogfeeds"><?php _e("Feed URL's:", RW_Group_Blogs::$textdomain ) ?></label>
                <textarea name="blogfeeds"
                          id="blogfeeds"><?php echo attribute_escape(implode(', ', (array)groups_get_groupmeta($bp->groups->current_group->id, 'rw-group-blogs-feeds'))) ?></textarea>
            </p>
            <?php
            wp_nonce_field('groups_create_save_' . $this->slug);
        }


        /**
         *
         * @since    0.0.1
         * @access  public
         * @static
         */
        function create_screen_save()
        {
            global $bp;

            check_admin_referer( 'groups_create_save_' . $this->slug );
            $unfiltered_feeds = explode( ',', $_POST[ 'blogfeeds' ] );
            foreach ( ( array ) $unfiltered_feeds as $blog_feed) {
                if ( ! empty( $blog_feed ) ) {
                    $blog_feeds[] = trim($blog_feed);
                }

            }
            groups_update_groupmeta( $bp->groups->current_group->id, 'rw-group-blogs-fetchtime', $_POST[ 'fetch-time' ] );
            groups_update_groupmeta( $bp->groups->current_group->id, 'rw-group-blogs-feeds', $blog_feeds );
            groups_update_groupmeta( $bp->groups->current_group->id, 'rw-group-blogs-lastupdate', gmdate( "Y-m-d H:i:s" ) );
            RW_Group_Blogs_Core::fetch_group_feeds( $bp->groups->current_group->id );
        }


        /**
         *
         * @since    0.0.1
         * @access  public
         * @static
         * @return bool
         */
        function edit_screen()
        {
            global $bp;

            if ( ! bp_is_group_admin_screen( $this->slug ) ) {
                return false;
            }

            $url = bp_get_root_domain();
            $group = groups_get_current_group();
            $configstr = base64_encode( json_encode( array( 'group_id' => $group->id, 'url' => $url ) ) );

            ?>
            <p><label for"configstr"><?php _e("Config to invite groupmember to blog.",  RW_Group_Blogs::$textdomain ); ?></label>
            <input id="configstr" type="text" value="<?php echo $configstr; ?>"></p>
            <?php

            $meta = groups_get_groupmeta( $bp->groups->current_group->id, 'rw-group-blogs-fetchtime');
            $fetch = ! empty( $meta ) ? $meta : '30';
            $times = array('10', '15', '20', '30', '60');
            echo '<p><label for="fetch-time">';
            _e("Refresh time:",  RW_Group_Blogs::$textdomain );
            echo '</label>';
            echo "<select id='fetch-time' name='fetch-time'>";
            $default = __('Default',  RW_Group_Blogs::$textdomain );
            echo "<option value='30'>$default</option>";
            foreach ($times as $time) {
                $selected = ($fetch == $time) ? 'selected="selected"' : '';
                echo "<option value='$time' $selected>$time</option>";
            }
            echo "</select></p>";
            ?>
            <span
                class="desc"><?php _e("Enter RSS feed URL's for blogs you would like to attach to this group. Any future posts on these blogs will show on the group activity stream. Seperate URL's with commas.",  RW_Group_Blogs::$textdomain ) ?></span>
            <p>
                <label for="blogfeeds"><?php _e("Feed URL's:",  RW_Group_Blogs::$textdomain ) ?></label>
                <textarea name="blogfeeds"
                          id="blogfeeds"><?php echo attribute_escape( implode( ', ', (array) groups_get_groupmeta( $bp->groups->current_group->id, 'rw-group-blogs-feeds' ) ) ); ?></textarea>
            </p>
            <input type="submit" name="save" value="<?php _e("Update Feed URL's", RW_Group_Blogs::$textdomain ) ?>"/>
            <?php
            wp_nonce_field('groups_edit_save_' . $this->slug);
        }


        /**
         *
         * @since    0.0.1
         * @access  public
         * @static
         * @return bool
         */
        function edit_screen_save()
        {
            global $bp;

            if (! isset( $_POST[ 'save' ] ) ) {
                return false;
            }
            check_admin_referer('groups_edit_save_' . $this->slug);
            $existing_feeds = (array)groups_get_groupmeta($bp->groups->current_group->id, 'rw-group-blogs-feeds');
            $unfiltered_feeds = explode(',', $_POST['blogfeeds']);
            foreach ((array)$unfiltered_feeds as $blog_feed) {
                if (!empty($blog_feed))
                    $blog_feeds[] = trim($blog_feed);
            }
            /* Loop and find any feeds that have been removed, so we can delete activity stream items */
            if (!empty($existing_feeds)) {
                foreach ((array)$existing_feeds as $feed) {
                    if (!in_array($feed, (array)$blog_feeds))
                        $removed[] = $feed;
                }
            }
            if ($removed) {
                /* Remove activity stream items for this feed */
                include_once(ABSPATH . WPINC . '/rss.php');
                foreach ((array)$removed as $feed) {
                    $rss = fetch_rss(trim($feed));
                    if (function_exists('bp_activity_delete')) {
                        bp_activity_delete(array(
                            'item_id' => $bp->groups->current_group->id,
                            'secondary_item_id' => wp_hash($rss->channel['link']),
                            'component' => $bp->groups->id,
                            'type' => RW_Group_Blogs_Core::$activity_type
                        ));
                    }
                }
            }

            groups_update_groupmeta($bp->groups->current_group->id, 'rw-group-blogs-fetchtime', $_POST['fetch-time']);
            groups_update_groupmeta($bp->groups->current_group->id, 'rw-group-blogs-feeds', $blog_feeds);
            groups_update_groupmeta($bp->groups->current_group->id, 'rw-group-blogs-lastupdate', gmdate("Y-m-d H:i:s"));
            RW_Group_Blogs_Core::fetch_group_feeds( $bp->groups->current_group->id );
            bp_core_add_message(__('External blog feeds updated successfully!',  RW_Group_Blogs::$textdomain));
            bp_core_redirect( bp_get_group_permalink($bp->groups->current_group) . '/admin/' . $this->slug);
        }

        /**
         * @since    0.0.1
         * @access  public
         * @static
         */
        function display()
        {
        }

        /**
         * @since    0.0.1
         * @access  public
         * @static
         */
        function widget_display()
        {
        }

    }
}
