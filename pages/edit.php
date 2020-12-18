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
access_ensure_global_level( plugin_config_get( 'edit_threshold', MANAGER ) );

$f_bug_id = gpc_get_int( 'bug_id' );
$f_users = gpc_get_int_array( 'user', array() );
$f_hundred_cents = gpc_get_string_array( 'hundred_cents', array() );

log_event( LOG_PLUGIN, "users=" . var_export( $f_users, TRUE ) . " cents=" . var_export( $f_hundred_cents, TRUE ) );
foreach ( $f_users as $i => $t_user_id) {
	contributors_set( $f_bug_id, $t_user_id, (int)((float)($f_hundred_cents[$i]) * 100) );
}
$f_new_user_id = gpc_get_int( 'new_user' );
$f_new_cents = (int)((float)(gpc_get_string( 'new_hundred_cents' )) * 100);
log_event( LOG_PLUGIN, "new_user=" . var_export( $f_new_user_id, TRUE ) . " new_cents=" . var_export( $f_new_ceents, TRUE ) );
if ( $f_new_user_id != 0 && $f_new_cents > 0 ) {
    contributors_set( $f_bug_id, $f_new_user_id, $f_new_cents );
}

form_security_purge( 'plugin_contributors_edit' );

print_successful_redirect( 'view.php?id=' . $f_bug_id . '#contributors' );
