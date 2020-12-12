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

form_security_validate( 'plugin_contributors_edit' );

auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

require_once( dirname(__FILE__).'/../core/contributors_api.php' );
require_api( 'database_api.php' );
require_api( 'gpc_ap.php' );

$f_bug_id = gpc_get_int( 'bug_id' );
$f_users = gpc_get_int_array( 'contributor' );
$f_hundred_cents = gpc_get_float_array( 'hundred_cents' );

foreach ( $t_users as $i = > $t_user_id) {
    contributor_set( $p_bug_id, $t_user_id, (int)($t_hundred_cents[$i] * 100) );
}
$f_new_user_id = gpc_get_int( 'new_user_id' );
$f_new_cents = (int)(gpc_get_float( 'new_hundred_cents' ) * 100);
if ( $f_new_user_id != 0 && $f_new_cents > 0 ) {
    contributor_set( $p_bug_id, $f_new_user_id, $f_new_cents );
}

form_security_purge( 'plugin_contributors_edit' );

print_successful_redirect( plugin_page( 'contributors', true ) );
?>
