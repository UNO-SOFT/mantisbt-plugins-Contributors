<?php
# :vim set noet:

define(MANTIS_DIR, dirname(__FILE__) . '/../..' );
define(MANTIS_CORE, MANTIS_DIR . '/core' );

require_once( MANTIS_DIR . '/core.php' );
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );
require_once( dirname(__FILE__).'/core/contributors_api.php' );

require_api( 'install_helper_functions_api.php' );
require_api( 'authentication_api.php');

class ContributorsPlugin extends MantisPlugin {
	function register() {
		$this->name = 'Contributors';	# Proper name of plugin
		$this->description = 'Manage contributors per issue';	# Short description of the plugin
		$this->page = 'view';		   # Default plugin page

		$this->version = '0.1';	 # Plugin version string
		$this->requires = array(	# Plugin dependencies, array of basename => version pairs
			'MantisCore' => '2.0.0',
			);

		$this->author = 'Tamás Gulácsi';		 # Author/team name
		$this->contact = 'T.Gulacsi@unosoft.hu';		# Author/team e-mail address
		$this->url = 'http://www.unosoft.hu';			# Support webpage
	}

	function config() {
		return array();
	}

	function hooks() {
		return array(
			'EVENT_MENU_ISSUE' => 'menu_issue',
			'EVENT_VIEW_BUG_EXTRA' => 'view_bug_extra',
		);
	}

	function menu_issue($p_event, $p_bug_id) {
		if ( access_get_project_level() < MANAGER ) {
			return array();
		}
		return array( '<a href="' . plugin_page( 'view.php' ) . '?bug_id=' . $p_bug_id . '">'
			.  plugin_lang_get('view') . '</a>', );
	}

	function view_bug_extra($p_event, $p_params) {
		if ( access_get_project_level() >= MANAGER ) {
			return;
		}
		$f_bug_id = $p_params[0];
		$t_arr = contributors_get_array( $f_bug_id );
		echo '<table>';
		echo '<tr><td><th>' . plugin_lang_get( 'contributor ' ) . '</th></td><td><th>' . plugin_lang_get( 'hundred_cents' ) . '</th></td></tr>';
		forearch( $t_arr as $t_elt ) {
			echo "<tr><td>" . user_get_name($t_elt[0]) . "</td><td>" . ($t_elt[1] / 100.0) . "</td></tr>";
		}
		echo "</table>";
	}


	function schema() {
		$opts = array(
			'mysql' => 'DEFAULT CHARSET=utf8',
			'pgsql' => 'WITHOUT OIDS'
		);
		return array(
			array( 'CreateTableSQL', array( plugin_table( 'contribution' ), "
				bug_id		I	NOTNULL UNSIGNED PRIMARY,
				user_id		I	NOTNULL UNSIGNED,
				cents		I	NOTNULL UNSIGNED",
				$opts)
			),
			array( 'CreateIndexSQL', array( 'idx_contributors_bugid', plugin_table( 'contribution' ), 'bug_id' ) ),

			array( 'CreateTableSQL', array( plugin_table( 'contribution_history' ) . '_history', "
				id			I	NOTNULL UNSIGNED PRIMARY AUTOINCREMENT
				modifier_id	I	NOTNULL UNSIGNED,
				modified_at	T	NOTNULL DEFAULT '" . db_null_date() . "'
				bug_id		I	NOTNULL UNSIGNED,
				user_id		I	NOTNULL UNSIGNED,
				cents		I	NOTNULL UNSIGNED",
				$opts)
			),
		);
	}
}

// vim: set noet:
