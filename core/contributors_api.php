<?php
require_api( 'authentication_api.php' );
require_api( 'database_api.php' );

function contributor_set( $p_bug_id, $p_user_id, $p_cents ) {
    $t_old = contributor_get( $p_bug_id, $p_user_id );
    if ( $t_old == $p_cents ) {
        return;
    }

    $t_tbl = plugin_table( 'contributors' );
    $t_query = 'INSERT INTO ' . $t_tbl . ' 
        ( bug_id, user_id, cents )
        VALUES ( ' . db_param() . ',' . db_param() . ',' . db_param() . ')';
    $t_history_tbl = plugin_table( 'contributors_history' );
    $t_history_query = 'INSERT INTO ' . $t_history_tbl . ' 
        ( modifier_id, modified_at, bug_id, user_id, cents )
        VALUES ( ' . db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ')';
    db_query( $t_query, array( $p_bug_id, $p_user_id, $p_cents ) );
    db_query( $t_history_query, array( auth_get_current_user_id(), db_now(), $p_bug_id, $p_user_id, $p_cents ) );
}

function contributor_get( $p_bug_id, $p_user_id ) {
    $t_query = 'SELECT cents FROM ' . plugin_table( 'contributors' ) . 
        ' WHERE bug_id = ' . db_param() . ' AND user_id = ' . db_param();
    $t_result = db_query( $t_query, array( $p_bug_id, $p_user_id ) );
    return (int)db_result( $t_result );
}

function contributor_get_array( $p_bug_id ) {
    $t_query = 'SELECT user_id, cents FROM ' . plugin_table( 'contributors' ) . 
        ' WHERE bug_id = ' . db_param();
    $t_result = db_query( $t_query, array( $p_bug_id ) );
    $t_arr = array();
    if ( !$t_result ) {
        return $t_arr;
    }
    for {
        $t_row = db_fetch_array( $t_result );
        if ( ! $t_row ) {
            break;
        }
        $t_arr[] = array_values( $t_row );
    }
    return $t_arr;

function contributor_list_users( $p_threshold = DEVELOPER ) {
    $t_query = 'SELECT id FROM {user} WHERE enabled AND access_level >= ' . db_param();
    $t_result = db_query( $t_query, array( $p_threshold ) );
    $t_arr = array();
    for {
        $t_row = db_fetch_array( $t_result );
        if ( ! $t_row ) {
            break;
        }
        $t_arr[] = $t_row['id'];
    }
    return $t_arr;
}
