<?php
# :vim set noet:

if ( !defined( 'MANTIS_DIR' ) ) {
	define( 'MANTIS_DIR', dirname(__FILE__) . '/../..' );
}
if ( !defined( 'MANTIS_CORE' ) ) {
	define( 'MANTIS_CORE', MANTIS_DIR . '/core' );
}

require_once( MANTIS_DIR . '/core.php' );
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );
require_once( dirname(__FILE__).'/core/contributors_api.php' );

require_api( 'install_helper_functions_api.php' );
require_api( 'authentication_api.php');
require_api( 'string_api.php');

test_string_mul_100();

class ContributorsPlugin extends MantisPlugin {
	private $cfg = null;

	function register() {
		$this->name = 'Contributors';	# Proper name of plugin
		$this->description = 'Manage contributors per issue';	# Short description of the plugin
		$this->page = '';		   # Default plugin page

		$this->version = '0.5.0';	 # Plugin version string
		$this->requires = array(	# Plugin dependencies, array of basename => version pairs
			'MantisCore' => '2.0.0'
			);

		$this->author = 'Tamás Gulácsi';		 # Author/team name
		$this->contact = 'T.Gulacsi@unosoft.hu';		# Author/team e-mail address
		$this->url = 'http://www.unosoft.hu';			# Support webpage
		
		$this->cfg = array(
			'view_threshold' => plugin_config_get( 'view_threshold', UPDATER ),
			'edit_all_threshold' => plugin_config_get( 'edit_all_threshold', ADMINISTRATOR ),
			'contributor_threshold' => plugin_config_get( 'contributor_threshold', UPDATER ),
		);
	}

	function config() {
		return $this->cfg;
	}

	function config_get( $p_name ) {
		$t_defaults = $this->config();
		return plugin_config_get( $p_name, $t_defaults[$p_name] );
	}

	function hooks() {
		return array(
			'EVENT_MENU_ISSUE' => 'menu_issue',
			'EVENT_VIEW_BUG_EXTRA' => 'view_bug_extra',
		);
	}

	function menu_issue($p_event, $p_bug_id) {
		$t_threshold = $this->config_get( 'view_threshold' );
		if ( access_get_project_level() < $t_threshold ) {
			return array();
		}
		return array( '<a class="btn btn-primary btn-sm btn-white btn-round" href="#contributors">'
			. plugin_lang_get('view') . '</a>', );
	}

	function view_bug_extra($p_event, $p_bug_id) {
		$t_lvl = access_get_project_level();
		$t_view_threshold = $this->config_get( 'view_threshold' );
		$t_edit_all = $t_lvl >= $this->config_get( 'edit_all_threshold' );
		if ( $t_lvl < $t_view_threshold ) {
			return;
		}
		$t_page = 'view';
		$t_current_uid = auth_get_current_user_id();
		$t_arr = contributors_get_array( $p_bug_id );
//log_event( LOG_LDAP, "uid=" . var_export( $t_current_uid, TRUE ) . " view_threshold=" . var_export( $t_view_threshold, TRUE ) . " lvl=" . var_export( $t_lvl, TRUE ) );
		$t_page = 'edit';
		echo '
<div class="form-container">
<form id="plugin-contributors-edit" action="' . plugin_page( 'edit' ) . '" method="post">
	<input type="hidden" name="bug_id" id="bug_id" value="' . $p_bug_id . '" />
' . form_security_field( 'plugin_contributors_edit' );
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

/*
		if ( !$t_edit ) { // just view
			foreach( $t_arr as $t_elt ) {
				$t_uid = $t_elt['user_id'];
				$t_cents = '*';
				if( $t_current_uid == $t_uid ) {
					$t_cents = $t_elt['cents'] / 100.0;
				}
				echo "<tr><td>" . user_get_name( $t_uid ) . '</td>
					<td>' . $t_cents . '</td>
					<td>' . $t_elt['deadline'] . '</td>
					<td>' . $t_elt['validity'] . '</td>
					<td><p>' . string_display_links( $t_elt['description'] ) . '</p></td>
					</tr>';
			}
		} else {
*/
			$t_contributors = contributors_list_users( $this->config_get( 'contributor_threshold' ), $p_bug_id );
//log_event( LOG_LDAP, "uid=" . var_export( $t_current_uid, TRUE ) . "=" .  user_get_name( $t_current_uid ) . " contributors=" . var_export( $t_contributors, TRUE ) );

			$t_seen = array();
			$t_sum = 0;
			foreach( $t_arr as $t_elt ) { 
				$t_sum += $t_elt['cents'];
				$t_uid = $t_elt['user_id'];
				$t_seen[] = $t_uid;
				$t_cents_type = 'hidden';
				$t_readonly = 'readonly';
				if( $t_edit_all || $t_uid == $t_current_uid ) {
					$t_cents_type = 'number';
					$t_readonly = '';
				}
				echo '<tr><td>' . user_get_name( $t_uid ) . '
					<input type="hidden" name="user[]" value="' . $t_uid . '" />
				</td>
				<td class="center" width="20%"> 
					<input type="' . $t_cents_type . '" class="ace" name="hundred_cents[]" min="0" max="1000" step="0.1" value="' . ($t_elt['cents'] / 100.0) . '" />
				</td>
				<td><input ' . $t_readonly . ' type="date" class="datetimepicker input-sm" name="deadline[]" data-picker-locale="hu" data-picker-format="Y-MM-DD HH:mm" maxlength="16" value="' . $t_elt['deadline'] . '" data-form-type="date" ' . $t_disabled . '/></td>
				<td><input ' . $t_readonly . ' type="date" class="datetimepicker input-sm" name="validity[]" data-picker-locale="hu" data-picker-format="Y-MM-DD HH:mm" maxlength="16" value="' . $t_elt['validity'] . '" data-form-type="date" ' . $t_disabled . '/></td>
				<td><textarea ' . $t_readonly . ' class="input-sm" name="description[]" data-form-type="text" ' . $t_disabled . '>' . string_display( $t_elt['description'] ) . '</textarea></td>
				</tr>';
			}
			$t_contributors = array_diff( $t_contributors, $t_seen );
//log_event( LOG_LDAP, "seen=" . var_export( $t_seen, TRUE ) . " contributors=" . var_export( $t_contributors, TRUE ) );
			if( !$t_edit_all ) {
				$t_found = FALSE;
				foreach( $t_contributors as $t_contributor ) {
					if( $t_contributor == $t_current_uid ) {
						$t_found = TRUE;
						break;
					}
				}
//log_event( LOG_LDAP, "found=" . var_export( $t_found, TRUE ) . " current_uid=$t_current_uid contributors=" . var_export( $t_contributors, TRUE ) );
				if( $t_found ) {
					$t_contributors = array( $t_current_uid );
				} else {
					$t_contributors = array();
				}
			}
//log_event( LOG_LDAP, " current_uid=$t_current_uid count=" . count($t_contributors) . " contributors=" . var_export( $t_contributors, TRUE ) );
			if( $t_edit_all ) {
				echo '<td><td><p>Σ ' . ($t_sum / 100.0) . '</p></td><td/><td/><td/></tr>';
			}
			if( count($t_contributors) > 0 ) {
				echo '
<tr>
	<td>
		<select name="new_user" id="new_user">
';
				foreach( $t_contributors as $t_contributor ) {
					if( $t_edit_all || $t_contributor == $t_current_uid ) {
						echo '<option value="' . $t_contributor . '">' . user_get_name( $t_contributor ) . '</option>';
					}
				}
				echo '
		</select>
	</td>
	<td><input type="number" name="new_hundred_cents" min="0" max="1000" step="0.1" /></td>
	<td><input type="date" id="new_deadline" class="datetimepicker input-sm" name="new_deadline" data-picker-locale="hu" data-picker-format="Y-MM-DD HH:mm" maxlength="16" value="" data-form-type="date" /></td>
	<td><input type="date" id="new_validity" class="datetimepicker input-sm" name="new_validity" data-picker-locale="hu" data-picker-format="Y-MM-DD HH:mm" maxlength="16" value="" data-form-type="date" /></td>
	<td><textarea id="new_description" class="input-sm" name="new_description" data-form-type="text"></textarea></td>
</tr>
';
				}
		//} 

		echo '
						</tbody>
					</table>
				</div> <!--widget-body-->
';

/*
		if ( !$t_edit ) {
			echo '
			</div> <!--widget-box-->
		</div> <!--noprint-->';
		} else {
*/
			echo '
				<div class="widget-toolbox padding-8 clearfix">
					<input type="submit" class="btn btn-primary btn-sm btn-white btn-round" value="' . plugin_lang_get( $t_page ) . '" />
				</div>
			</div> <!--widget-box-->
		</div> <!--noprint-->
	</form>
</div> <!--form-container-->
';
		//}

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
				description	 X,
				deadline		I UNSIGNED,
				validity		I UNSIGNED",
				$opts)
			),
			array( 'AddColumnSQL', array( plugin_table( 'history' ), "
				description	 X,
				deadline		I UNSIGNED,
				validity		I UNSIGNED",
				$opts)
			),
            // alter table mantis_plugin_contributors_current_table add primary key (bug_id,user_id) initially deferred;
		);
	}
}

// vim: set noet:
