<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Module Creation local plugin settings and presets.
 *
 * @package    local_modulecreate
 * @copyright  2017 RMOelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_modulecreate',
        get_string('pluginname', 'local_modulecreate'));
    $ADMIN->add('localplugins', $settings);

        // Headings.
    $settings->add(new admin_setting_heading('local_modulecreate_settings', '',
        get_string('pluginname_desc', 'local_modulecreate')));
    $settings->add(new admin_setting_heading('local_modulecreate_exdbheader',
        get_string('settingsheaderdb', 'local_modulecreate'), ''));

    // Db Connection Settings.
    // -----------------------

    // Db type.
    $options = array('',
        "access",
        "ado_access",
        "ado",
        "ado_mssql",
        "borland_ibase",
        "csv",
        "db2",
        "fbsql",
        "firebird",
        "ibase",
        "informix72",
        "informix",
        "mssql",
        "mssql_n",
        "mssqlnative",
        "mysql",
        "mysqli",
        "mysqlt",
        "oci805",
        "oci8",
        "oci8po",
        "odbc",
        "odbc_mssql",
        "odbc_oracle",
        "oracle",
        "pdo",
        "postgres64",
        "postgres7",
        "postgres",
        "proxy",
        "sqlanywhere",
        "sybase",
        "vfp");
    $options = array_combine($options, $options);
    $settings->add(new admin_setting_configselect('local_modulecreate/dbtype',
        get_string('dbtype', 'local_modulecreate'),
        get_string('dbtype_desc', 'local_modulecreate'), '', $options));

    // Db host.
    $settings->add(new admin_setting_configtext('local_modulecreate/dbhost',
        get_string('dbhost', 'local_modulecreate'),
        get_string('dbhost_desc', 'local_modulecreate'), 'localhost'));

    // Db User.
    $settings->add(new admin_setting_configtext('local_modulecreate/dbuser',
        get_string('dbuser', 'local_modulecreate'), '', ''));

    // Db Password.
    $settings->add(new admin_setting_configpasswordunmask('local_modulecreate/dbpass',
        get_string('dbpass', 'local_modulecreate'), '', ''));

    // Db Name.
    $settings->add(new admin_setting_configtext('local_modulecreate/dbname',
        get_string('dbname', 'local_modulecreate'),
        get_string('dbname_desc', 'local_modulecreate'), ''));

    // Db Encoding.
    $settings->add(new admin_setting_configtext('local_modulecreate/dbencoding',
        get_string('dbencoding', 'local_modulecreate'), '', 'utf-8'));

    // Db Setup.
    $settings->add(new admin_setting_configtext('local_modulecreate/dbsetupsql',
        get_string('dbsetupsql', 'local_modulecreate'),
        get_string('dbsetupsql_desc', 'local_modulecreate'), ''));

    // Db Sybase.
    $settings->add(new admin_setting_configcheckbox('local_modulecreate/dbsybasequoting',
        get_string('dbsybasequoting', 'local_modulecreate'),
        get_string('dbsybasequoting_desc', 'local_modulecreate'), 0));

    // AODBC Debug.
    $settings->add(new admin_setting_configcheckbox('local_modulecreate/debugdb',
        get_string('debugdb', 'local_modulecreate'),
        get_string('debugdb_desc', 'local_modulecreate'), 0));

    // Table Settings.
    $settings->add(new admin_setting_heading('local_modulecreate_remoteheader',
        get_string('settingsheaderremote', 'local_modulecreate'), ''));

    // Table name.
    $settings->add(new admin_setting_configtext('local_modulecreate/remotetablewrite',
        get_string('remotetablewrite', 'local_modulecreate'),
        get_string('remotetablewrite_desc', 'local_modulecreate'), ''));
    $settings->add(new admin_setting_configtext('local_modulecreate/remotetablecat',
        get_string('remotetablecat', 'local_modulecreate'),
        get_string('remotetablecat_desc', 'local_modulecreate'), ''));
    $settings->add(new admin_setting_configtext('local_modulecreate/remotetablecourses',
        get_string('remotetablecourses', 'local_modulecreate'),
        get_string('remotetablecourses_desc', 'local_modulecreate'), ''));
    $settings->add(new admin_setting_configtext('local_modulecreate/remotetableenrols',
        get_string('remotetableenrols', 'local_modulecreate'),
        get_string('remotetableenrols_desc', 'local_modulecreate'), ''));

}
