<?php
require_api( 'authentication_api.php' );
require_api( 'database_api.php' );

function contributors_set( $p_bug_id, $p_user_id, $p_cents ) {
	$t_old = contributors_get( $p_bug_id, $p_user_id );
	if ( $t_old == $p_cents ) {
		return;
	}

	$t_history_tbl = plugin_table( 'history' );
	$t_history_query = 'INSERT INTO ' . $t_history_tbl . ' 
		( modifier_id, modified_at, bug_id, user_id, cents )
		VALUES ( ' . db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ')';
	db_query( $t_history_query, array( auth_get_current_user_id(), db_now(), $p_bug_id, $p_user_id, $p_cents ) );

	$t_tbl = plugin_table( 'current' );
	if ( $p_cents == 0 ) {
		$t_query = 'DELETE FROM ' . $t_tbl . ' WHERE bug_id = ' . db_param() . ' AND user_id = ' . db_param();
		db_query( $t_query, array( $p_bug_id, $p_user_id ) );
		return;
	}

	$t_query = 'UPDATE ' . $t_tbl . ' SET cents = ' . db_param() . ' WHERE bug_id = ' . db_param() .
		' AND user_id = ' . db_param();
	db_query( $t_query, array( $p_cents, $p_bug_id, $p_user_id ) );

	if ( db_affected_rows() == 0 ) {
		$t_query = 'INSERT INTO ' . $t_tbl . ' 
			( bug_id, user_id, cents )
			VALUES ( ' . db_param() . ',' . db_param() . ',' . db_param() . ')';
		db_query( $t_query, array( $p_bug_id, $p_user_id, $p_cents ) );
	}
}

function contributors_get( $p_bug_id, $p_user_id ) {
	$t_query = 'SELECT cents FROM ' . plugin_table( 'current' ) . 
		' WHERE bug_id = ' . db_param() . ' AND user_id = ' . db_param();
	$t_result = db_query( $t_query, array( $p_bug_id, $p_user_id ) );
	return (int)db_result( $t_result );
}

function contributors_get_array( $p_bug_id ) {
	$t_query = 'SELECT user_id, cents FROM ' . plugin_table( 'current' ) . 
		' WHERE bug_id = ' . db_param();
	$t_result = db_query( $t_query, array( $p_bug_id ) );
	$t_arr = array();
	if ( !$t_result ) {
		return $t_arr;
	}
	while ( true ) {
		$t_row = db_fetch_array( $t_result );
		if ( ! $t_row ) {
			break;
		}
		$t_arr[] = array_values( $t_row );
	}
	return $t_arr;
}

function contributors_list_users( $p_threshold = DEVELOPER, $p_bug_id = 0 ) {
	$t_params = array( $p_threshold );
	if ( $p_bug_id == 0 ) {
		$t_query = 'SELECT id FROM {user} WHERE enabled AND access_level >= ' . db_param() . ' ORDER BY username';
		$t_result = db_query( $t_query, $t_params );
	} else {
		$t_query = 'SELECT id FROM {user} A WHERE enabled AND access_level >= ' . db_param() . 
			' AND NOT EXISTS (SELECT 1 FROM ' . plugin_table( 'current' ) . 
			' B WHERE B.bug_id = ' . db_param() . ' AND B.user_id = A.id) ' .
			' ORDER BY username';
		$t_params[] = $p_bug_id;
		$t_result = db_query( $t_query, $t_params );
	}
	$t_arr = array();
	while ( true ) {
		$t_row = db_fetch_array( $t_result );
		if ( ! $t_row ) {
			break;
		}
		$t_arr[] = $t_row['id'];
	}
	return $t_arr;
}

// vim: set noet:
