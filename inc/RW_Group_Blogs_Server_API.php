<?php

/**
 * Class RW_Group_Blogs__Server_API
 *
 * Contains code for API
 *
 */

class RW_Group_Blogs_Server_API {

    /**
     * Add API Endpoint
     *
     * @since   0.0.4
     * @access  public
     * @static
     * @return void
     */
    static public function add_endpoint() {
        add_rewrite_endpoint( 'rwgroupinfo', EP_ROOT );
    }

    /**
     * Add query vars to retrieve api cmds
     *
     * @since   0.0.4
     * @access  public
     * @static
     * @param   $vars   array
     * @return  array
     */
    static public function add_query_vars( $vars ) {
        $vars[] = 'cmd';
        $vars[] = 'data';
        return $vars;
    }

    /**
     * Handle API Request
     *
     * @since   0.0.4
     * @access  public
     * @static
     * @return  void
     * @todo    sanitize input data
     */
    static protected function handle_request(){
        global $wp_query;
        $request = array( 'cmd' =>  $wp_query->query_vars[ 'cmd' ],
            'data' =>  $wp_query->query_vars[ 'data' ]);
        if( ! $request ) {
            self::send_response( array( 'errors' => 405 ) );
        } elseif( !$request[ 'cmd' ] ) {
            var_dump( $request);
            self::send_response( array( 'errors' => 406 ) );
        } else {
            apply_filters( 'rw_group_blogs_server_cmd_parser', $request );
            self::send_response( array( 'errors' => 407 ) );
        }
    }


    /**
     *
     * @since   0.0.4
     * @access  public
     * @static
     * @param $msg
     * @param string $data
     */
    static protected function send_response( $msg ){
        header('content-type: application/json; charset=utf-8');
        echo json_encode( $msg )."\n";
        exit;
    }



    /**
     *
     * @since   0.0.4
     * @access  public
     * @static
     * @hook    rw_remote_auth_server_cmd_parser
     * @param $request
     * @return mixed
     */
    static public function template_redirect() {
        global $wp_query;

        // if this is not a request for json or a singular object then bail
        if ( ! isset( $wp_query->query_vars['rwgroupinfo'] ) )
            return;

        RW_Group_Blogs_Server_API::handle_request();
        exit;

    }

    /**
     *
     * @since   0.0.4
     * @access  public
     * @static
     * @param $request
     * @return mixed
     */
    static public function cmd_list_profiles( $request ) {
        if ( 'get_group' == $request[ 'cmd' ] ) {
            $answer = self::generate_profile_data( $request[ 'data' ][ 'admin' ], $request[ 'data' ][ 'group_id' ]  );
            self::send_response( $answer );
        }
        return $request;
    }

    /**
     *
     * @since   0.0.4
     * @access  public
     * @static
     * @param $request
     * @return mixed
     */
    static public function cmd_add_blog( $request ) {
        if ( 'add_blog' == $request[ 'cmd' ] ) {
            $answer = self::add_blog( $request[ 'data' ][ 'feed_url' ], $request[ 'data' ][ 'group_id' ] );
            self::send_response( $answer );
        }
        return $request;
    }


    /**
     *
     * @since   0.0.5
     * @access  public
     * @static
     * @param $request
     * @return mixed
     */
    static public function cmd_add_activity( $request ) {
        if ( 'add_activity' == $request[ 'cmd' ] ) {
            $answer = self::add_activity( $request[ 'data' ] );
            self::send_response( $answer );
        }
        return $request;
    }

    /**
     *
     * @since   0.0.5
     * @access  public
     * @static
     * @param $args
     * @return mixed
     */
    static public function add_activity( $args ) {
        $defaults = array(
            'id' => NULL,
            'user' => false,  // username, to check if user is member of group
            'user_id' => false, // false or true to show user in activity
            'action' => '',
            'content' => '',
            'primary_link' => '',
            'type' => RW_Group_Blogs_Core::$activity_type,
            'item_id' => '',  // GroupID
            'secondary_item_id' => '',
            'recorded_time' => gmdate("Y-m-d H:i:s"),
            'hide_sitewide' => true
        );
        $args = wp_parse_args( $args, $defaults );

        $userdata = get_user_by( 'login', $args[ 'user' ] );
        if ( groups_is_user_member( $userdata->ID, $args[ 'item_id' ] ) ) {

            // Add new record
            $back = groups_record_activity(array(
                'id' => $args['id'],
                'user_id' => $args['user_id'],
                'action' => $args['action'],
                'content' => $args['content'],
                'primary_link' => $args['primary_link'],
                'type' => $args['type'],
                'item_id' => $args['item_id'],
                'secondary_item_id' => $args['secondary_item_id'],
                'recorded_time' => $args['recorded_time'],
                'hide_sitewide' => $args['hide_sitewide'],
            ));

            if ($back === false) {
                $ret = array('errors' => 406, 'message' => 'Activity not created');
            } else {
                $ret = array('message' => "ok");
            }
        } else {
            $ret = array('errors' => 403, 'message' => 'Activity not created');
        }
        return $ret;
    }

    /**
     *
     * @since   0.0.4
     * @access  public
     * @static
     * @param
     * @return mixed
     */
    static public function add_blog( $feed_url, $group_id  ) {
        if ( class_exists( 'RW_Group_Blogs_Core' ) ) {
            groups_update_groupmeta( $group_id, 'rw-group-blogs-fetchtime',"15" );
            groups_update_groupmeta( $group_id, 'rw-group-blogs-feeds', $feed_url );
            groups_update_groupmeta( $group_id, 'rw-group-blogs-lastupdate', gmdate( "Y-m-d H:i:s" ) );
            RW_Group_Blogs_Core::fetch_group_feeds( $group_id );
            $back = array( 'message' => "ok");
        } else {
            $back = array( 'errors' => 406, 'message' => 'Group Blogs component not active' );
        }

        return $back;
    }


    /**
     * Generates a array of profile data from a user
     * @since   0.0.4
     * @access  public
     * @static
     * @param $user_name
     */
    static public function generate_profile_data( $user_name, $group_id  ) {
        $group = array();
        $members = array();

        $userdata = get_user_by( 'login', $user_name );
        if ( groups_is_user_member( $userdata->ID, $group_id ) ) {
            // User ist in der Gruppe
            $groupdata = groups_get_group( array( 'group_id' => $group_id ) );
            $group['name'] = $groupdata->name;
            $group['url'] =  trailingslashit( bp_get_root_domain() . '/' . bp_get_groups_root_slug() . '/' . $groupdata->slug . '/' );

            // Members
            $membersdata = groups_get_group_members( array( 'group_id' => $group_id, 'page' => 1, 'per_page' => 99999, 'max' => 99999, 'exclude_admins_mods' => false ));

            foreach ( $membersdata[ "members" ] as $member) {
                $members[] = array(
                    'login_name' => $member->user_login,
                    'profil_url' => bp_core_get_userlink( $member->ID, false, true )
                );
            }
            $back = array( 'data' => array(
                'group' => $group,
                'member' => $members
            ) );

        } else {
            // User ist nicht in der Gruppe:
            $back = array( 'errors' => 403 );
        }


        return ( $back );
    }
}