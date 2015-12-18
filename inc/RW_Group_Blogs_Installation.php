<?php

/**
 * Class RW_Group_Blogs_Installation
 *
 * Contains some helper code for plugin installation
 *
 * @package   RW Group Blogs
 * @author    Frank Staude
 * @license   GPL-2.0+
 * @link      https://github.com/rpi-virtuell/rw-group-blogs
 */

class RW_Group_Blogs_Installation {
    /**
     * Check some thinks on plugin activation
     *
     * @since   0.0.1
     * @access  public
     * @static
     * @return  void
     */
    public static function on_activate() {

        // check WordPress version
        if ( ! version_compare( $GLOBALS[ 'wp_version' ], '4.0', '>=' ) ) {
            deactivate_plugins( RW_Group_Blogs::$plugin_filename );
            die(
            wp_sprintf(
                '<strong>%s:</strong> ' .
                __( 'This plugin requires WordPress 4.0 or newer to work', RW_Group_Blogs::get_textdomain() )
                , RW_Group_Blogs::get_plugin_data( 'Name' )
            )
            );
        }


        // check php version
        if ( version_compare( PHP_VERSION, '5.3.0', '<' ) ) {
            deactivate_plugins( RW_Group_Blogs::$plugin_filename );
            die(
            wp_sprintf(
                '<strong>%1s:</strong> ' .
                __( 'This plugin requires PHP 5.3 or newer to work. Your current PHP version is %1s, please update.', RW_Group_Blogs::get_textdomain() )
                , RW_Group_Blogs::get_plugin_data( 'Name' ), PHP_VERSION
            )
            );
        }

        wp_schedule_event( time(), 'hourly', 'rw_group_blogs_cron' );
    }

    /**
     * Clean up after deactivation
     *
     * Clean up after deactivation the plugin
     *
     * @since   0.0.1
     * @access  public
     * @static
     * @return  void
     */
    public static function on_deactivation() {
        wp_clear_scheduled_hook( 'rw_group_blogs_cron' );
        bp_activity_delete( array( 'type' => RW_Group_Blogs_Core::$activity_type ) );
    }

    /**
     * Clean up after uninstall
     *
     * Clean up after uninstall the plugin.
     * Delete options and other stuff.
     *
     * @since   0.0.1
     * @access  public
     * @static
     * @return  void
     *
     */
    public static function on_uninstall() {
        // @todo cleanup group neta
    }
}
