<?php
# MantisBT - a php based bugtracking system
# Copyright (C) 2002 - 2009  MantisBT Team - mantisbt-dev@lists.sourceforge.net
# MantisBT is free software: you can redistribute it and/or modify
# it under the terms of the GNU General Public License as published by
# the Free Software Foundation, either version 2 of the License, or
# (at your option) any later version.
#
# MantisBT is distributed in the hope that it will be useful,
# but WITHOUT ANY WARRANTY; without even the implied warranty of
# MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
# GNU General Public License for more details.
#
# You should have received a copy of the GNU General Public License
# along with MantisBT.  If not, see <http://www.gnu.org/licenses/>.

require_once( dirname(__FILE__).'/../core/contributors_api.php' );
require_api( 'database_api.php' );
require_api( 'form_api.php' );
require_api( 'gpc_api.php' );
require_api( 'logging_api.php' );

form_security_validate( 'plugin_contributors_edit' );

//auth_reauthenticate( );

$f_bug_id = gpc_get_int( 'bug_id' );
$f_users = gpc_get_int_array( 'user', array() );
$f_hundred_cents = gpc_get_string_array( 'hundred_cents', array() );
$f_deadline = gpc_get_string_array( 'deadline', array() );
$f_validity = gpc_get_string_array( 'validity', array() );
$f_description = gpc_get_string_array( 'description', array() );

$t_uid = auth_get_current_user_id();

log_event( LOG_PLUGIN, "users=" . var_export( $f_users, TRUE ) . 
    " cents=" . var_export( $f_hundred_cents, TRUE ) .
    " deadline=" . var_export( $f_deadline, TRUE ) .
    " validity=" . var_export( $f_validity, TRUE ) .
    " description=" . var_export( $f_description, TRUE ) .
	" user=" . var_export( $t_uid, TRUE )
);

access_ensure_global_level( plugin_config_get( 'view_threshold', UPDATER ) );
$t_lvl = access_get_project_level();
$t_edit_all = $t_lvl >= plugin_config_get( 'edit_threshold', MANAGER );

foreach ( $f_users as $i => $t_user_id) {
    if( $t_edit_all || $t_user_id == $t_uid ) {
		contributors_set( $f_bug_id, $t_user_id, array(
			'cents' => string_mul_100($f_hundred_cents[$i]),
			'deadline' => $f_deadline[$i], 
			'validity' => $f_validity[$i],
			'description' => $f_description[$i],
		));
	}
}
$f_new_user_id = gpc_get_int( 'new_user', 0 );
if( $f_new_user_id != 0 ) {
	$f_new_cents = string_mul_100(gpc_get_string( 'new_hundred_cents' ));
	$f_new_deadline = gpc_get_string( 'new_deadline' );
	$f_new_validity = gpc_get_string( 'new_validity' );
	$f_new_description = gpc_get_string( 'new_description' );
	log_event( LOG_PLUGIN, "new_user=" . var_export( $f_new_user_id, TRUE ) . 
		" new_cents=" . var_export( $f_new_cents, TRUE ) .
		" new_deadline=" . var_export( $f_new_deadline, TRUE ) . 
		" new_validity=" . var_export( $f_new_validity, TRUE ) . 
		" new_description=" . var_export( $f_new_description, TRUE ) 
	);
	if ( $f_new_user_id != 0 && $f_new_cents > 0 ) {
		contributors_set( $f_bug_id, $f_new_user_id, array(
			'cents' => $f_new_cents,
			'deadline' => $f_new_deadline, 
			'validity' => $f_new_validity, 
			'description' => $f_new_description,
		));
	}
}

form_security_purge( 'plugin_contributors_edit' );

print_successful_redirect( 'view.php?id=' . $f_bug_id . '#contributors' );
