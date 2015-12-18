<?php
/**
 * Class RW_Group_Blogs_Autoloader
 *
 * Autoloader for the plugin
 *
 * @package   RW Group Blogs
 * @author    Frank Staude
 * @license   GPL-2.0+
 * @link      https://github.com/rpi-virtuell/rw-group-blogs
 */

class RW_Group_Blogs_Autoloader {
    /**
     * Registers autoloader function to spl_autoload
     *
     * @since   0.0.1
     * @access  public
     * @static
     * @action  rw_site_config_autoload_register
     * @return  void
     */
    public static function register() {
        spl_autoload_register( 'RW_Group_Blogs_Autoloader::load' );
        do_action( 'rw_group_blogs_autoload_register' );
    }

    /**
     * Unregisters autoloader function with spl_autoload
     *
     * @since    0.0.1
     * @access  public
     * @static
     * @action  rw_site_config_autoload_unregister
     * @return  void
     */
    public static function unregister() {
        spl_autoload_unregister( 'RW_Group_Blogs_Autoloader::load' );
        do_action( 'rw_group_blogs_autoload_unregister' );
    }

    /**
     * Autoloading function
     *
     * @since   0.0.1
     * @param   string  $classname
     * @access  public
     * @static
     * @return  void
     */
    public static function load( $classname ) {
        // only PHP 5.3, use now __DIR__ as equivalent to dirname(__FILE__).
        $file =  __DIR__ . DIRECTORY_SEPARATOR . ucfirst( $classname ) . '.php';
        if( file_exists( $file ) ) {
            require_once $file;
        }
    }
}
