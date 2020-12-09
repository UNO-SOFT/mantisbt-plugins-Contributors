<?php
# :vim set noet:
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

access_ensure_global_level( UPDATER );

html_page_top( plugin_lang_get( 'name' ) );

print_manage_menu( );

$t_this = plugin_page( basename(__FILE__) );

require_once( 'core/database_api.php' );
require_once( dirname(__FILE__) . '/../core/list_defs.inc.php' );

$f_list = $_REQUEST['list'];
if( !defined( $f_list ) ) {
	$f_list = gpc_get_string( 'list' );
	//echo '<pre>f_list=' . $f_list . '</pre>';
}

if( !is_blank( $f_list ) && array_key_exists( $f_list, $UC_lists ) ) {
	$t_list = $f_list;
	$t_query = $UC_lists[$t_list];
	$t_result = db_query( $t_query );
	if( !$t_result ) {
		echo 'Error executing query ' . $t_qry . '!';
	} elseif( 'modified_issues' == $t_list ) {
		echo '<table><thead><th>Id</th><th>Title</th><th>Time</th></thead><tbody>';
		while( !$t_result->EOF ) {
			$t_row = db_fetch_array( $t_result );
			//var_dump($t_row);
			echo '<tr><td>' . $t_row['id'] 
				. '</td><td>' . $t_row['summary'] 
				. '</td><td>' . strftime( '%Y-%m-%d %H:%M:%S', $t_row['last_updated'] ) 
				. '</td></tr>' . "\n";
		}
		echo '</tbody></table>';
	}
} else {
?>

<br/>
<ul>
<?php
	foreach( array_keys( $UC_lists ) as $t_name ) {
		echo '<li><a href="' . $t_this . '&list=' . $t_name . '">'
			. plugin_lang_get( 'list_' . $t_name ) . '</a></li>' . "\n";
	}
?>
</ul>
<?php
}
html_page_bottom();
?>
