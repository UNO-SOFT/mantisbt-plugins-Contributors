<?php
# :vim set noet:

if ( !defined( 'MANTIS_DIR' ) ) {
	define(MANTIS_DIR, dirname(__FILE__) . '/../..' );
}
if ( !defined( 'MANTIS_CORE' ) ) {
	define(MANTIS_CORE, MANTIS_DIR . '/core' );
}

require_once( MANTIS_DIR . '/core.php' );
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );
require_once( dirname(__FILE__).'/core/contributors_api.php' );

require_api( 'install_helper_functions_api.php' );
require_api( 'authentication_api.php');
require_api( 'string_api.php');

test_string_mul_100();

class ContributorsPlugin extends MantisPlugin {
	function register() {
		$this->name = 'Contributors';	# Proper name of plugin
		$this->description = 'Manage contributors per issue';	# Short description of the plugin
		$this->page = '';		   # Default plugin page

		$this->version = '0.4.0';	 # Plugin version string
		$this->requires = array(	# Plugin dependencies, array of basename => version pairs
			'MantisCore' => '2.0.0'
			);

		$this->author = 'Tamás Gulácsi';		 # Author/team name
		$this->contact = 'T.Gulacsi@unosoft.hu';		# Author/team e-mail address
		$this->url = 'http://www.unosoft.hu';			# Support webpage
	}

	function config() {
		return array( 
			'view_threshold' => plugin_config_get( 'view_threshold', UPDATER ),
			'edit_threshold' => plugin_config_get( 'edit_threshold', UPDATER ),
			'contributor_threshold' => plugin_config_get( 'contributor_threshold', UPDATER )
		);
	}

	function hooks() {
		return array(
			'EVENT_MENU_ISSUE' => 'menu_issue',
			'EVENT_VIEW_BUG_EXTRA' => 'view_bug_extra',
		);
	}

	function menu_issue($p_event, $p_bug_id) {
		if ( access_get_project_level() < plugin_config_get( 'view_threshold', MANAGER ) ) {
			return array();
		}
		return array( '<a class="btn btn-primary btn-sm btn-white btn-round" href="#contributors">'
			. plugin_lang_get('view') . '</a>', );
	}

	function view_bug_extra($p_event, $p_bug_id) {
		$t_lvl = access_get_project_level();
		if ( $t_lvl < plugin_config_get( 'view_threshold', MANAGER ) ) {
			return;
		}
		$t_page = 'view';
		$t_edit = $t_lvl >= plugin_config_get( 'edit_threshold', MANAGER );
		$t_arr = contributors_get_array( $p_bug_id );
		if ( $t_edit ) {
			$t_page = 'edit';
			echo '
<div class="form-container">
	<form id="plugin-contributors-edit" action="' . plugin_page( 'edit' ) . '" method="post">
		<input type="hidden" name="bug_id" id="bug_id" value="' . $p_bug_id . '" />
' . form_security_field( 'plugin_contributors_edit' );
		}
		echo '
		<div class="col-md-12 col-xs-12 noprint">
			<div id="contributors" class="widget-box widget-color-blue2">
				<div class="widget-header widget-header-small">
					<h4 class="widget-title lighter">' . plugin_lang_get( 'contribution' ) . '</h4>
				</div>
				<div class="widget-body">
					<table class="table table-bordered table-condensed table-hover table-striped">
						<thead><tr>
							<th>' . plugin_lang_get( 'contributor' ) . '</th><th>' . plugin_lang_get( 'hundred_cents' ) . '</th>
							<th>' . plugin_lang_get( 'deadline' ) . '</th><th>' . plugin_lang_get( 'validity' ) . '</th>
						</tr></thead>
						<tbody>
';

		if ( !$t_edit ) { // just view
			foreach( $t_arr as $t_elt ) {
				echo "<tr><td>" . user_get_name( $t_elt['user_id'] ) . '</td>
					<td>' . ($t_elt['cents'] / 100.0) . '</td>
					<td>' . $t_elt['deadline'] . '</td>
					<td>' . $t_elt['validity'] . '</td>
					<td><p>' . string_display_links( $t_elt['description'] ) . '</p></td>
					</tr>';
			}
		} else {
			$t_contributors = contributors_list_users( 
				plugin_config_get( 'contributor_threshold', DEVELOPER ), 
				$p_bug_id 
			);

			foreach( $t_arr as $t_elt ) { 
				echo '<tr><td>' . user_get_name( $t_elt['user_id'] ) . '
					<input type="hidden" name="user[]" value="' . $t_elt['user_id'] . '" />
				</td>
				<td class="center" width="20%">
					<input type="number" class="ace" name="hundred_cents[]" min="0" max="1000" step="0.1" value="' . ($t_elt['cents'] / 100.0) . '" />
				</td>
				<td><input type="date" class="datetimepicker input-sm" name="deadline[]" data-picker-locale="hu" data-picker-format="Y-MM-DD HH:mm" maxlength="16" value="' . $t_elt['deadline'] . '" style="" data-form-type="date" /></td>
				<td><input type="date" class="datetimepicker input-sm" name="validity[]" data-picker-locale="hu" data-picker-format="Y-MM-DD HH:mm" maxlength="16" value="' . $t_elt['validity'] . '" style="" data-form-type="date" /></td>
				<td><textarea class="input-sm" name="description[]" data-form-type="text">' . string_display( $t_elt['description'] ) . '</textarea></td>
				</tr>';
			}
			echo '
<tr>
    <td>
		<select name="new_user" id="new_user">
';
			$t_contributors = array_diff( $t_contributors, $t_arr );
			foreach( $t_contributors as $t_contributor ) {
				echo '<option value="' . $t_contributor . '">' . user_get_name( $t_contributor ) . '</option>';
			}
			echo '
        </select>
    </td>
    <td><input type="number" name="new_hundred_cents" min="0" max="1000" step="0.1" /></td>
    <td><input type="date" id="new_deadline" class="datetimepicker input-sm" name="new_deadline" data-picker-locale="hu" data-picker-format="Y-MM-DD HH:mm" maxlength="16" value="" style="" data-form-type="date" /></td>
    <td><input type="date" id="new_validity" class="datetimepicker input-sm" name="new_validity" data-picker-locale="hu" data-picker-format="Y-MM-DD HH:mm" maxlength="16" value="" style="" data-form-type="date" /></td>
    <td><textarea id="new_description" class="input-sm" name="new_description" data-form-type="text"></textarea></td>
</tr>
';
		} 

		echo '
						</tbody>
					</table>
				</div> <!--widget-body-->
';

		if ( !$t_edit ) {
			echo '
			</div> <!--widget-box-->
		</div> <!--noprint-->';
		} else {
			echo '
				<div class="widget-toolbox padding-8 clearfix">
					<input type="submit" class="btn btn-primary btn-sm btn-white btn-round" value="' . plugin_lang_get( $t_page ) . '" />
				</div>
			</div> <!--widget-box-->
		</div> <!--noprint-->
	</form>
</div> <!--form-container-->
';
		}

	}


	function schema() {
		$opts = array(
			'mysql' => 'DEFAULT CHARSET=utf8',
			'pgsql' => 'WITHOUT OIDS'
		);
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'current' ), "
				bug_id		I	NOTNULL UNSIGNED,
				user_id		I	NOTNULL UNSIGNED,
				cents		I	NOTNULL UNSIGNED",
				$opts)
			),
			array( 'CreateIndexSQL', array( 'idx_contributors_bugid', plugin_table( 'current' ), 'bug_id' ) ),

			array( 'CreateTableSQL', array( plugin_table( 'history' ) , "
				id			I	NOTNULL UNSIGNED PRIMARY AUTOINCREMENT,
				modifier_id	I	NOTNULL UNSIGNED,
				modified_at	I	NOTNULL DEFAULT 0,
				bug_id		I	NOTNULL UNSIGNED,
				user_id		I	NOTNULL UNSIGNED,
				cents		I	NOTNULL UNSIGNED",
				$opts)
			),

			array( 'AddColumnSQL', array( plugin_table( 'current' ), "
				description     X,
				deadline		I UNSIGNED,
				validity		I UNSIGNED",
				$opts)
			),
			array( 'AddColumnSQL', array( plugin_table( 'history' ), "
				description     X,
				deadline		I UNSIGNED,
				validity		I UNSIGNED",
				$opts)
			),
		);
	}
}

// vim: set noet:
