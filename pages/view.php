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

auth_reauthenticate( );
access_ensure_global_level( config_get( 'manage_plugin_threshold' ) );

layout_page_header( lang_get( 'plugin_format_title' ) );

layout_page_begin( 'manage_overview_page.php' );

print_manage_menu( 'manage_plugin_page.php' );

require_once( dirname(__FILE__).'/../core/contributors_api.php' );
require_api( 'logging_api.php' );

$f_bug_id = gpc_get_int( 'bug_id' );
$t_arr = contributors_get_array( $f_bug_id );
$t_developers = contributors_list_users( DEVELOPER, $f_bug_id );
?>


<div class="col-md-12 col-xs-12">
<div class="space-10"></div>
<div class="form-container" >

<form id="formatting-config-form" action="<?php echo plugin_page( 'edit' ); ?>" method="post">
	<input type="hidden" name="bug_id" id="bug_id" value="<?php echo $f_bug_id; ?>" />
<?php echo form_security_field( 'plugin_contributors_edit' ) ?>

<div class="widget-box widget-color-blue2">
<div class="widget-header widget-header-small">
	<h4 class="widget-title lighter">
		<i class="ace-icon fa fa-video-camera"></i>
		<?php echo plugin_lang_get( 'show' )?>
	</h4>
</div>
<div class="widget-body">
<div class="widget-main no-padding">
<div class="table-responsive">
<table class="table table-bordered table-condensed table-striped">
<tr>
	<th class="category width-40">
		<?php echo plugin_lang_get( 'contributor' )?>
	</th>
	<th class="category">
		<?php echo plugin_lang_get( 'hundred_cents' )?>
	</th>
</tr>
<?php foreach( $t_arr as $t_elt ) { ?>
    <td>
        <?php echo user_get_name( $t_elt[0] ); ?>
        <input type="hidden" name="user[]" value="<?php echo $t_elt[0]; ?>" />
    </td>
	<td class="center" width="20%">
		<input type="number" class="ace" name="hundred_cents[]" min="0" max="1000" step="0.1" value="<?php echo ($t_elt[1] / 100.0); ?>" />
	</td>
</tr>
<?php } ?>
<tr>
    <td>
        <select name="new_user" id="new_user">
        <?php foreach( $t_developers as $t_developer ) {?>
            <option value="<?php echo $t_developer; ?>"><?php echo user_get_name( $t_developer ); ?></option>
        <?php } ?>
        </select>
    </td>
    <td><input type="number" name="new_hundred_cents" min="0" max="1000" step="0.1" /></td>
</tr>

</table>
</div>
</div>
<div class="widget-toolbox padding-8 clearfix">
	<input type="submit" class="btn btn-primary btn-white btn-round"><?php echo lang_get( 'submit' )?></input>
</div>
</div>
</div>
</form>
</div>
</div>
