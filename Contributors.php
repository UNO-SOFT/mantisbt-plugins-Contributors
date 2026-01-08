<?php
// :vim set noet:

if (!defined('MANTIS_DIR')) {
    define('MANTIS_DIR', dirname(__FILE__) . '/../..');
}
if (!defined('MANTIS_CORE')) {
    define('MANTIS_CORE', MANTIS_DIR . '/core');
}

require_once (MANTIS_DIR . '/core.php');
require_once (config_get('class_path') . 'MantisPlugin.class.php');
require_once (dirname(__FILE__) . '/core/contributors_api.php');

require_api('install_helper_functions_api.php');
require_api('authentication_api.php');
require_api('string_api.php');

test_string_mul_100();

class ContributorsPlugin extends MantisPlugin
{
    private $cfg = null;
    private $edit_all_uids = array();

    function register()
    {
        $this->name = 'Contributors';  // Proper name of plugin
        $this->description = 'Manage contributors per issue';  // Short description of the plugin
        $this->page = '';  // Default plugin page

        $this->version = '0.5.1';  // Plugin version string
        $this->requires = array(  // Plugin dependencies, array of basename => version pairs
            'MantisCore' => '2.0.0'
        );

        $this->author = 'Tamás Gulácsi';  // Author/team name
        $this->contact = 'T.Gulacsi@unosoft.hu';  // Author/team e-mail address
        $this->url = 'http://www.unosoft.hu';  // Support webpage

        $this->cfg = array(
            'view_threshold' => plugin_config_get('view_threshold', UPDATER),
            'edit_all_threshold' => plugin_config_get('edit_all_threshold', ADMINISTRATOR),
            'contributor_threshold' => plugin_config_get('contributor_threshold', UPDATER),
            'edit_all_users' => plugin_config_get('edit_all_users', 'akoshuszti,tgulacsi,zbatta'),
        );
    }

    function config()
    {
        return $this->cfg;
    }

    function config_get($p_name)
    {
        $t_defaults = $this->config();
        return plugin_config_get($p_name, $t_defaults[$p_name]);
    }

    function hooks()
    {
        return array(
            'EVENT_MENU_ISSUE' => 'menu_issue',
            'EVENT_VIEW_BUG_EXTRA' => 'view_bug_extra',
        );
    }

    function menu_issue($p_event, $p_bug_id)
    {
        $t_threshold = $this->config_get('view_threshold');
        if (access_get_project_level() < $t_threshold) {
            return array();
        }
        return array(
            '<a class="btn btn-primary btn-sm btn-white btn-round" href="#contributors">'
                . plugin_lang_get('view') . '</a>',
        );
    }

    function edit_all($p_user = null)
    {
        if (!$this->edit_all_uids) {
            $this->edit_all_uids = array();
            $t_usernames = preg_split('/[,\s]+/', $this->config_get('edit_all_users'));
            foreach ($t_usernames as $t_username) {
                $this->edit_all_uids[user_get_id_by_name($t_username)] = $t_username;
            }
            if (
                !$this->edit_all_uids ||
                count($this->edit_all_uids) != count($t_usernames)
            ) {
                log_event(LOG_LDAP, 'edit_all_users=' . var_export($this->config_get('edit_all_users') . ' = ' . var_export($t_usernames, TRUE), TRUE) . ' -> ' . var_export($this->edit_all_uids, TRUE));
            }
            if (!$this->edit_all_uids) {
                $this->edit_all_uids[-1] = '';
            }
        }

        if (!$p_user) {
            $p_user = auth_get_current_user_id();
        }
        return array_key_exists($p_user, $this->edit_all_uids);
    }

    function view_bug_extra($p_event, $p_bug_id)
    {
        $t_lvl = access_get_project_level();
        $t_view_threshold = $this->config_get('view_threshold');
        if ($t_lvl < $t_view_threshold) {
            return;
        }
        $t_current_uid = auth_get_current_user_id();
        $t_edit_all = $this->edit_all($t_current_uid);

        $t_page = 'view';
        $t_arr = contributors_get_array($p_bug_id);
        // log_event( LOG_LDAP, "uid=" . var_export( $t_current_uid, TRUE ) . " view_threshold=" . var_export( $t_view_threshold, TRUE ) . " lvl=" . var_export( $t_lvl, TRUE ) );
        $t_may_edit = $t_edit_all;
        if (!$t_may_edit) {
            $t_projection = bug_get_field($p_bug_id, 'projection');
            $t_may_edit = $_projection > 51 ||
                $t_projection == 50 &&
                    bug_get_field($p_bug_id, 'status') < 30;
        }
        if (!$t_may_edit) {
            $t_msg = 'MAY_EDIT: projection=' . var_export($t_projection, TRUE)
                . ' status=' . var_export(bug_get_field($p_bug_id, 'status'));
            echo "\n<!--" . $t_msg . "-->\n";
            log_event(LOG_LDAP, $t_msg);
        }

        $t_page = 'edit';
        echo '
<div class="form-container">
<form id="plugin-contributors-edit" action="' . plugin_page('edit') . "\" method=\"post\">
\t<input type=\"hidden\" name=\"bug_id\" id=\"bug_id\" value=\"" . $p_bug_id . '" />
' . form_security_field('plugin_contributors_edit');
        echo "
\t\t<div class=\"col-md-12 col-xs-12 noprint\">
\t\t\t<div id=\"contributors\" class=\"widget-box widget-color-blue2\">
\t\t\t\t<div class=\"widget-header widget-header-small\">
\t\t\t\t\t<h4 class=\"widget-title lighter\">" . plugin_lang_get('contribution') . "</h4>
\t\t\t\t</div>
\t\t\t\t<div class=\"widget-body\">
\t\t\t\t\t<table class=\"table table-bordered table-condensed table-hover table-striped\">
\t\t\t\t\t\t<thead><tr>
\t\t\t\t\t\t\t<th>" . plugin_lang_get('contributor') . '</th><th>' . plugin_lang_get('hundred_cents') . "</th>
\t\t\t\t\t\t\t<th>" . plugin_lang_get('deadline') . '</th><th>' . plugin_lang_get('validity') . "</th>
\t\t\t\t\t\t</tr></thead>
\t\t\t\t\t\t<tbody>
";

        /*
         * if ( !$t_may_edit ) { // just view
         * 	foreach( $t_arr as $t_elt ) {
         * 		$t_uid = $t_elt['user_id'];
         * 		$t_cents = '*';
         * 		if( $t_current_uid == $t_uid ) {
         * 			$t_cents = $t_elt['cents'] / 100.0;
         * 		}
         * 		echo "<tr><td>" . user_get_name( $t_uid ) . '</td>
         * 			<td>' . $t_cents . '</td>
         * 			<td>' . $t_elt['deadline'] . '</td>
         * 			<td>' . $t_elt['validity'] . '</td>
         * 			<td><p>' . string_display_links( $t_elt['description'] ) . '</p></td>
         * 			</tr>';
         * 	}
         * } else {
         */
        $t_contributors = contributors_list_users($this->config_get('contributor_threshold'), $p_bug_id);
        // log_event( LOG_LDAP, "uid=" . var_export( $t_current_uid, TRUE ) . "=" .  user_get_name( $t_current_uid ) . " contributors=" . var_export( $t_contributors, TRUE ) );

        $t_seen = array();
        $t_sum = 0;
        foreach ($t_arr as $t_elt) {
            $t_sum += $t_elt['cents'];
            $t_uid = $t_elt['user_id'];
            $t_seen[] = $t_uid;
            $t_cents_type = 'hidden';
            $t_readonly = 'readonly';
            if ($t_edit_all || $t_may_edit && $t_uid == $t_current_uid) {
                $t_cents_type = 'number';
                $t_readonly = '';
            }
            echo '<tr><td>' . user_get_name($t_uid) . "
\t\t\t\t\t<input type=\"hidden\" name=\"user[]\" value=\"" . $t_uid . "\" />
\t\t\t\t</td>
\t\t\t\t<td class=\"center\" width=\"20%\">
\t\t\t\t\t<input type=\"" . $t_cents_type . '" class="ace" name="hundred_cents[]" min="0" max="1000" step="0.1" value="' . ($t_elt['cents'] / 100.0) . "\" />
\t\t\t\t</td>
\t\t\t\t<td><input " . $t_readonly . ' type="date" class="datetimepicker input-sm" name="deadline[]" data-picker-locale="hu" data-picker-format="Y-MM-DD HH:mm" maxlength="16" value="' . $t_elt['deadline'] . "\" data-form-type=\"date\" /></td>
\t\t\t\t<td><input " . $t_readonly . ' type="date" class="datetimepicker input-sm" name="validity[]" data-picker-locale="hu" data-picker-format="Y-MM-DD HH:mm" maxlength="16" value="' . $t_elt['validity'] . "\" data-form-type=\"date\" /></td>
\t\t\t\t<td><textarea " . $t_readonly . ' class="input-sm" name="description[]" data-form-type="text" >' . string_display($t_elt['description']) . "</textarea></td>
\t\t\t\t</tr>";
        }
        $t_contributors = array_diff($t_contributors, $t_seen);
        // log_event( LOG_LDAP, "seen=" . var_export( $t_seen, TRUE ) . " contributors=" . var_export( $t_contributors, TRUE ) );
        if (!$t_edit_all) {
            $t_found = FALSE;
            foreach ($t_contributors as $t_contributor) {
                if ($t_contributor == $t_current_uid) {
                    $t_found = TRUE;
                    break;
                }
            }
            // log_event( LOG_LDAP, "found=" . var_export( $t_found, TRUE ) . " current_uid=$t_current_uid contributors=" . var_export( $t_contributors, TRUE ) );
            if ($t_found) {
                $t_contributors = array($t_current_uid);
            } else {
                $t_contributors = array();
            }
        }
        // log_event( LOG_LDAP, " current_uid=$t_current_uid count=" . count($t_contributors) . " contributors=" . var_export( $t_contributors, TRUE ) );
        if ($t_edit_all) {
            echo '<td><td><p>Σ ' . ($t_sum / 100.0) . '</p></td><td/><td/><td/></tr>';
        }
        if (count($t_contributors) > 0) {
            echo "
<tr>
\t<td>
\t\t<select name=\"new_user\" id=\"new_user\">
";
            foreach ($t_contributors as $t_contributor) {
                if ($t_edit_all || $t_contributor == $t_current_uid) {
                    echo '<option value="' . $t_contributor . '">' . user_get_name($t_contributor) . '</option>';
                }
            }
            echo "
\t\t</select>
\t</td>
\t<td><input type=\"number\" name=\"new_hundred_cents\" min=\"0\" max=\"1000\" step=\"0.1\" /></td>
\t<td><input type=\"date\" id=\"new_deadline\" class=\"datetimepicker input-sm\" name=\"new_deadline\" data-picker-locale=\"hu\" data-picker-format=\"Y-MM-DD HH:mm\" maxlength=\"16\" value=\"\" data-form-type=\"date\" /></td>
\t<td><input type=\"date\" id=\"new_validity\" class=\"datetimepicker input-sm\" name=\"new_validity\" data-picker-locale=\"hu\" data-picker-format=\"Y-MM-DD HH:mm\" maxlength=\"16\" value=\"\" data-form-type=\"date\" /></td>
\t<td><textarea id=\"new_description\" class=\"input-sm\" name=\"new_description\" data-form-type=\"text\"></textarea></td>
</tr>
";
        }
        // }

        echo "
\t\t\t\t\t\t</tbody>
\t\t\t\t\t</table>
\t\t\t\t</div> <!--widget-body-->
";

        /*
         * if ( !$t_may_edit ) {
         * 	echo '
         * 	</div> <!--widget-box-->
         * </div> <!--noprint-->';
         * } else {
         */
        echo "
\t\t\t\t<div class=\"widget-toolbox padding-8 clearfix\">
\t\t\t\t\t<input type=\"submit\" class=\"btn btn-primary btn-sm btn-white btn-round\" value=\"" . plugin_lang_get($t_page) . "\" />
\t\t\t\t</div>
\t\t\t</div> <!--widget-box-->
\t\t</div> <!--noprint-->
\t</form>
</div> <!--form-container-->
";
        // }
    }

    function schema()
    {
        $opts = array(
            'mysql' => 'DEFAULT CHARSET=utf8',
            'pgsql' => 'WITHOUT OIDS'
        );
        return array(
            array('CreateTableSQL', array(plugin_table('current'), "
\t\t\t\tbug_id\t\tI\tNOTNULL UNSIGNED,
\t\t\t\tuser_id\t\tI\tNOTNULL UNSIGNED,
\t\t\t\tcents\t\tI\tNOTNULL UNSIGNED", $opts)),
            array('CreateIndexSQL', array('idx_contributors_bugid', plugin_table('current'), 'bug_id')),
            array('CreateTableSQL', array(plugin_table('history'), "
\t\t\t\tid\t\t\tI\tNOTNULL UNSIGNED PRIMARY AUTOINCREMENT,
\t\t\t\tmodifier_id\tI\tNOTNULL UNSIGNED,
\t\t\t\tmodified_at\tI\tNOTNULL DEFAULT 0,
\t\t\t\tbug_id\t\tI\tNOTNULL UNSIGNED,
\t\t\t\tuser_id\t\tI\tNOTNULL UNSIGNED,
\t\t\t\tcents\t\tI\tNOTNULL UNSIGNED", $opts)),
            array('AddColumnSQL', array(plugin_table('current'), "
\t\t\t\tdescription\t X,
\t\t\t\tdeadline\t\tI UNSIGNED,
\t\t\t\tvalidity\t\tI UNSIGNED", $opts)),
            array('AddColumnSQL', array(plugin_table('history'), "
\t\t\t\tdescription\t X,
\t\t\t\tdeadline\t\tI UNSIGNED,
\t\t\t\tvalidity\t\tI UNSIGNED", $opts)),
            // alter table mantis_plugin_contributors_current_table add primary key (bug_id,user_id) initially deferred;
        );
    }
}

// vim: set noet:
