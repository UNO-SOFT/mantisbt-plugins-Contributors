<?php
require_api( 'authentication_api.php' );
require_api( 'database_api.php' );
require_api( 'date_api.php' );

function contributors_set( $p_bug_id, $p_user_id, $p_row ) {
	$t_old = contributors_get( $p_bug_id, $p_user_id );
//log_event( LOG_LDAP, "old=" . var_export( $t_old, TRUE ) . " new=" . var_export( $p_row, TRUE ) . " equal=" . var_export( $t_old == $p_row, TRUE ) );
	if ( $t_old == $p_row ) {
		return;
	}

	$t_deadline = date_strtotime( $p_row['deadline'] );
	$t_validity = date_strtotime( $p_row['validity'] );
//log_event( LOG_LDAP, "deadline=" . $t_deadline . ' validity=' . $t_validity );
	$t_history_tbl = plugin_table( 'history' );
	$t_history_query = 'INSERT INTO ' . $t_history_tbl . ' 
		( modifier_id, modified_at, bug_id, user_id, 
		  cents, deadline, validity, description )
		VALUES ( ' . db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ',' . 
			db_param() . ',' . db_param() . ',' . db_param() . ',' . db_param() . ')';
	db_query( $t_history_query, array( 
		auth_get_current_user_id(), db_now(), $p_bug_id, $p_user_id, 
		$p_row['cents'], $t_deadline, $t_validity, $p_row['description']
	) );

	$t_tbl = plugin_table( 'current' );
	$t_query = 'DELETE FROM ' . $t_tbl . ' WHERE bug_id = ' . db_param() . ' AND user_id = ' . db_param();
	db_query( $t_query, array( $p_bug_id, $p_user_id ) );
	if ( $p_row['cents'] == 0 ) {
		return;
	}

	$t_query = 'INSERT INTO ' . $t_tbl . ' 
        ( bug_id, user_id, cents, deadline, validity, description )
        VALUES ( ' . db_param() . ',' . db_param() . ',' . db_param() .  ',' . 
        db_param() .  ',' . db_param() . ',' . db_param() . ')';
    db_query( $t_query, array( 
        $p_bug_id, $p_user_id, $p_row['cents'], $t_deadline, $t_validity, $p_row['description'] 
    ) );
}

function contributors_get( $p_bug_id, $p_user_id ) {
	$t_query = 'SELECT cents, deadline, validity, description FROM ' . plugin_table( 'current' ) . 
		' WHERE bug_id = ' . db_param() . ' AND user_id = ' . db_param();
	$t_result = db_query( $t_query, array( $p_bug_id, $p_user_id ) );
	$t_row = db_fetch_array( $t_result );
	if( !$t_row ) {
		return $t_row;
	};
	$t_row['validity'] = date_timetostr( $t_row['validity'] );
	$t_row['deadline'] = date_timetostr( $t_row['deadline'] );
//log_event( LOG_LDAP, "row=" . var_export( $t_row, TRUE ) );
	return $t_row;
}

function contributors_get_array( $p_bug_id ) {
	$t_query = 'SELECT user_id, cents, deadline, validity, description FROM ' . plugin_table( 'current' ) . 
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
		$t_row['validity'] = date_timetostr( $t_row['validity'] );
		$t_row['deadline'] = date_timetostr( $t_row['deadline'] );
//log_event( LOG_LDAP, "row=" . var_export( $t_row, TRUE ) );
		$t_arr[] = $t_row;
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

function string_mul_100( string $p_num ): int {
    $t_pos = strpos( $p_num, '.' );
    if( $t_pos === false ) { 
        return (int)($p_num . '00');
    } elseif( $t_pos === 0 ) {
        return (int)(substr( $p_num . '00', 1, 2 ));
    } elseif( $t_pos === strlen( $p_num )-1 ) {
        return (int)(substr( $p_num, 0, -1 ) . '00');
    }
    return (int)(substr( $p_num, 0, $t_pos ) . substr( $p_num . '00', $t_pos+1, 2 ));
}

function test_string_mul_100(): void {
    foreach( array(
        '1' => 100,
        '1.0' => 100,
        '0' => 0,
        '0.12345' => 12,
        '-34.56' => -3456,
        '.30' => 30,
    ) as $tIn => $tWant ) {
        $tGot = string_mul_100($tIn);
        if( $tGot != $tWant ) {
            echo "!! $tIn: got $tGot, wanted $tWant";
        }
    }
}

function date_timetostr( $p_date ) {
	if( date_is_null( $p_date ) ) {
		return '';
	} 
	return date( 'Y-m-d', $p_date );
}

// vim: set noet:
