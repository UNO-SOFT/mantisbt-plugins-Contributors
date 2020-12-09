<?php
# :vim set noet:

define(MANTIS_DIR, dirname(__FILE__) . '/../..' );
define(MANTIS_CORE, MANTIS_DIR . '/core' );

require_once(MANTIS_DIR . '/core.php');
require_once( config_get( 'class_path' ) . 'MantisPlugin.class.php' );

class ContributorsPlugin extends MantisPlugin {
	function register() {
		$this->name = 'Contributors';	# Proper name of plugin
		$this->description = 'Manage contributors per issue';	# Short description of the plugin
		$this->page = 'config';		   # Default plugin page

		$this->version = '0.1';	 # Plugin version string
		$this->requires = array(	# Plugin dependencies, array of basename => version pairs
			'MantisCore' => '2.0.0',
			);

		$this->author = 'Tamás Gulácsi';		 # Author/team name
		$this->contact = 'T.Gulacsi@unosoft.hu';		# Author/team e-mail address
		$this->url = 'http://www.unosoft.hu';			# Support webpage
	}

	function config() {
		return array(
			'contributors' => array(),
		);
	}

	function hooks() {
		return array(
			'EVENT_MENU_MANAGE' => 'menu_manage',
			'EVENT_MENU_ISSUE' => 'menu_issue',
			'EVENT_REPORT_BUG' => 'bug_reported',
			'EVENT_VIEW_BUG_EXTRA' => 'view_bug_extra',
		);
	}

	function menu_manage( ) {
		if ( access_get_project_level() >= MANAGER) {
			return array( '<a href="' . plugin_page( 'config.php' ) . '">'
				.  plugin_lang_get('config') . '</a>', );
		}
	}

	function menu_issue($p_event, $p_bug_id) {
		if ( access_get_project_level() >= MANAGER) {
			return array( '<a href="' . plugin_page( 'manage.php' ) . '">'
				.  plugin_lang_get('manage') . '</a>', );
		}
	}

	function view_bug_extra($p_event, $p_params) {
		$f_bug_id = $p_params[0];
		echo "<table></table>";
	}

}

// vim: set noet:
