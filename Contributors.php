<?php
# :vim set noet:

if ( !defined( MANTIS_DIR ) ) {
	define(MANTIS_DIR, dirname(__FILE__) . '/../..' );
}
if ( !defined( MANTIS_CODE ) ) {
	define(MANTIS_CORE, MANTIS_DIR . '/core' );
}

require_once( MANTIS_DIR . '/core.php' );
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );
require_once( dirname(__FILE__).'/core/contributors_api.php' );

require_api( 'install_helper_functions_api.php' );
require_api( 'authentication_api.php');

class ContributorsPlugin extends MantisPlugin {
	function register() {
		$this->name = 'Contributors';	# Proper name of plugin
		$this->description = 'Manage contributors per issue';	# Short description of the plugin
		$this->page = '';		   # Default plugin page

		$this->version = '0.2';	 # Plugin version string
		$this->requires = array(	# Plugin dependencies, array of basename => version pairs
			'MantisCore' => '2.0.0'
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
		return array( '<a class="btn btn-primary btn-sm btn-white btn-round" href="' . plugin_page( 'view.php' ) . '&bug_id=' . $p_bug_id . '">'
			.  plugin_lang_get('view') . '</a>', );
	}

	function view_bug_extra($p_event, $p_bug_id) {
		if ( access_get_project_level() < MANAGER ) {
			return;
		}
		$t_arr = contributors_get_array( $p_bug_id );
		log_event( LOG_PLUGIN, "bug_id=$p_bug_id contributors=" . var_export( $t_arr, TRUE ) );

		echo '
<div class="col-md-12 col-xs-12 noprint">
	<div id="contributors" class="widget-box widget-color-blue2">
		<div class="widget-header widget-header-small">
			<h4 class="widget-title lighter">' . plugin_lang_get( 'contribution' ) . '</h4>
		</div>
	<div class="widget-body">
		<table class="table table-bordered table-condensed table-hover table-striped">
			<thead><tr><th>' . plugin_lang_get( 'contributor' ) . '</th><th>' . plugin_lang_get( 'hundred_cents' ) . '</th></tr></thead>
			<tbody>
';
		foreach( $t_arr as $t_elt ) {
			echo "<tr><td>" . user_get_name($t_elt[0]) . "</td><td>" . ($t_elt[1] / 100.0) . "</td></tr>\n";
		}
		echo '
			</tbody>
		</table>
	</div>
	<div class="widget-toolbox padding-8 clearfix">
		<a class="btn btn-primary btn-sm btn-white btn-round" href="' . plugin_page( 'view.php' ) . '&bug_id=' . $p_bug_id . '">' . plugin_lang_get('view') . '</a>
	</div>
</div>';
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
		);
	}
}

// vim: set noet:
