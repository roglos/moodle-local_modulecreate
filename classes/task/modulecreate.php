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
 * A scheduled task for scripted database integrations.
 *
 * @package    local_modulecreate - template
 * @copyright  2016 ROelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_modulecreate\task;
use stdClass;

defined('MOODLE_INTERNAL') || die;

/**
 * A scheduled task for scripted external database integrations.
 *
 * @copyright  2016 ROelmann
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class modulecreate extends \core\task\scheduled_task {

    /**
     * Get a descriptive name for this task (shown to admins).
     *
     * @return string
     */
    public function get_name() {
        return get_string('pluginname', 'local_modulecreate');
    }

    /**
     * Run sync.
     */
    public function execute() {

        global $CFG, $DB;

        // Check connection and label Db/Table in cron output for debugging if required.
        if (!$this->get_config('dbtype')) {
            echo 'Database not defined.<br>';
            return 0;
        } else {
            echo 'Database: ' . $this->get_config('dbtype') . '<br>';
        }
        if (!$this->get_config('remotetablewrite')) {
            echo 'Table not defined.<br>';
            return 0;
        } else {
            echo 'Table: ' . $this->get_config('remotetablewrite') . '<br>';
        }
        if (!$this->get_config('remotetablecat')) {
            echo 'Categories Table not defined.<br>';
            return 0;
        } else {
            echo 'Table: ' . $this->get_config('remotetablecat') . '<br>';
        }
        if (!$this->get_config('remotetablecourses')) {
            echo 'Courses Table not defined.<br>';
            return 0;
        } else {
            echo 'Table: ' . $this->get_config('remotetablecourses') . '<br>';
        }
        if (!$this->get_config('remotetableenrols')) {
            echo 'Enrolments Table not defined.<br>';
            return 0;
        } else {
            echo 'Table: ' . $this->get_config('remotetableenrols') . '<br>';
        }

        echo 'Starting connection...<br>';

        // Report connection error if occurs.
        if (!$extdb = $this->db_init()) {
            echo 'Error while communicating with external database <br>';
            return 1;
        }

        /* Get data to create module/course lists for course creation
         * ---------------------------------------------------------- */
        /* Categories - to create overarching pages within School/SubjComm/Domain (ExtDb)
         */
        $catsites = array();
        if ($this->get_config('remotetablecat')) {
            // Get external table name.
            $cattable = $this->get_config('remotetablecat');
            // Read data from table.
            $sql = $this->db_get_sql($cattable, array(), array(), true);
            if ($catrs = $extdb->Execute($sql)) {
                if (!$catrs->EOF) {
                    while ($cats = $catrs->FetchRow()) {
                        $cats = array_change_key_case($cats, CASE_LOWER);
                        $cats = $this->db_decode($cats);
                        $catsites[] = $cats;
                    }
                }
                $catrs->Close();
            } else {
                // Report error if required.
                $extdb->Close();
                echo 'Error reading data from the external course table<br>';
                return 4;
            }
        }
        /* catsites() ->
         *               id
         *               category_name
         *               category_idnumber
         *               parent_cat_idnumber
         *               deleted
         */

        /* Compare enrolments and MAV list to create Taught Module list. (ExtDb)
         * This only includes MAVs on SITS with at least 1 person enrolled
         * whether Mod/Mav Tutor, Co-tutor or student. */
        $enrolledcourses = array();
        if ($this->get_config('remotetableenrols')) {
            // Get external table name.
            $enrolstable = $this->get_config('remotetableenrols');
            $coursetable = $this->get_config('remotetablecourses');

            // Read data from table.
            $sql = "SELECT * FROM " . $coursetable . " WHERE course_idnumber IN
                (SELECT distinct course FROM " . $enrolstable .")";
            if ($enrcrsrs = $extdb->Execute($sql)) {
                if (!$enrcrsrs->EOF) {
                    while ($encrs = $enrcrsrs->FetchRow()) {
                        $encrs = array_change_key_case($encrs, CASE_LOWER);
                        $encrs = $this->db_decode($encrs);
                        $enrolledcourses[] = $encrs;
                    }
                }
                $enrcrsrs->Close();
            } else {
                // Report error if required.
                $extdb->Close();
                echo 'Error reading data from the external course table<br>';
                return 4;
            }
        }
        /* enrolledcourses() ->
         *                       course_idnumber
         *                       course_fullname
         *                       course_shortname
         *                       course_startdate
         *                       category_idnumber
         */

        /* Staff Sandbox Pages.
         * -------------------- */
        // Find id of the staff_SB category. If there isn't one then bypass whole section.
        $sandboxes = array();
        if ($DB->get_record('course_categories', array('idnumber' => 'staff_SB'))) {
            $sbcategory = $DB->get_record('course_categories',
                        array('idnumber' => 'staff_SB'));

            // Array of staff user data.
            $sourcetable2 = 'user'; // No data definition as this is a standard mdl table.
            $select = "email LIKE '%@glos.ac.uk'"; // Pattern match for staff email accounts.
            $sandboxes = $DB->get_records_select($sourcetable2, $select);
        }
        /* sandboxes() ->
         *                       * FROM mdl_user
         *                       idnumber
         */

        /* Make courses list to add to course creation table.
         * NOTE: This script simply creates a table for Moodle to create *new* pages
         * It does NOT manage them into correct categories if there are changes.
         * ------------------------------------------------------------------------- */

        $newsite = array();
        $sites = array();
        // Loop through categories array to create course site details for each category.
        foreach ($catsites as $page) {
            $pageidnumber = 'CRS-' . $page['category_idnumber'];
            if (!$DB->get_records('course',
                array('idnumber' => $pageidnumber))) { // Only add if doesn't already exist in mdl_course.
                $newsite['fullname'] = $page['category_name'];
                // Category sites do not have both shortname and idnumber so use category idnumber for both.
                // Prefix with CRS for ease of identifying in front end UI and Db searches.
                $newsite['shortname'] = $pageidnumber;
                $newsite['idnumber'] = $pageidnumber;
                // Get category id for the relevant category idnumber - this is what is needed in the table.
                $categoryidnumber = $page['category_idnumber'];
                $category = $DB->get_record('course_categories', array('idnumber' => $categoryidnumber));
                $newsite['categoryid'] = $category->id;
            }
            $sites[] = $newsite;
        }
        // Loop through taughtmodules array to create course site details for each category.
        foreach ($enrolledcourses as $page) {
            $pageidnumber = $page['course_idnumber'];
            if (!$DB->get_records('course',
                array('idnumber' => $pageidnumber))) { // Only add if doesn't already exist in mdl_course.
                    $newsite['fullname'] = $page['course_fullname'];
                    $newsite['shortname'] = $page['course_shortname'];
                    $newsite['idnumber'] = $pageidnumber;
                    // Get category id for the relevant category idnumber - this is what is needed in the table.
                    $categoryidnumber = $page['category_idnumber'];
                    $category = $DB->get_record('course_categories', array('idnumber' => $categoryidnumber));
                    $newsite['categoryid'] = $category->id;
            }
            $sites[] = $newsite;
        }
        // Loop through staff sandbox array to create course site details for each category.
        // Find id of the staff_SB category. If there isn't one then bypass whole section.
        if ($DB->get_record('course_categories', array('idnumber' => 'staff_SB'))) {
            $sbcategory = $DB->get_record('course_categories',
                        array('idnumber' => 'staff_SB'));
            foreach ($sandboxes as $page) {
                $pageidnumber = $page['idnumber'];
                if (!$DB->get_records('course',
                    array('idnumber' => $pageidnumber))) { // Only add if doesn't already exist.
                    $newsite['fullname'] = $pageidnumber . " Sandbox Test Page";
                    $newsite['shortname'] = $pageidnumber . "_SB";
                    $newsite['idnumber'] = $pageidnumber . "_SB";
                    $newsite['categoryid'] = $sbcategory;
                }
                $sites[] = $newsite;
            }
        }

        /* Write $sites[] to external database table.
         * ========================================== */
        // Get external table to write to.
        if ($this->get_config('remotetablewrite')) {
            $writetable = $this->get_config('remotetablewrite');
            foreach ($sites as $ns) {
                $fullname = $ns['fullname'];
                $shortname = $ns['shortname'];
                $idnumber = $ns['idnumber'];
                $categoryid = $ns['categoryid'];

                // Strip special characters from fullname and shortname.
                // Remove ' from fullname if present (prevents issues with sql line).
                $fullname = str_replace("'", "", $fullname);
                $shortname = str_replace("'", "", $shortname);

                // Remove em dash, replace with -.
                $emdash = html_entity_decode('&#x2013;', ENT_COMPAT, 'UTF-8');
                $fullname = str_replace($emdash, '-', $fullname);
                $shortname = str_replace($emdash, '-', $shortname);

                $emdash2 = html_entity_decode('&#8212;', ENT_COMPAT, 'UTF-8');
                $fullname = str_replace($emdash2, '-', $fullname);
                $shortname = str_replace($emdash2, '-', $shortname);

                $fullname = str_replace('\u2014', '-', $fullname);
                $shortname = str_replace('\u2014', '-', $shortname);

                // Remove ? from fullname.
                $fullname = str_replace("?", "", $fullname);
                $shortname = str_replace("?", "", $shortname);

                // Remove &.
                $fullname = str_replace("&", " and ", $fullname);
                $shortname = str_replace("&", " and ", $shortname);

                // Set new coursesite in table by inserting the data created above.
                $sql = "INSERT INTO " . $writetable . " (course_fullname,course_shortname,course_idnumber,category_id)
                    VALUES ('" . $fullname . "','" . $shortname . "','" . $idnumber . "','" .$categoryid . "')";
                $extdb->Execute($sql);
            }
        }
        // Free memory.
        $extdb->Close();
        // End of External Database data section.

    }

    /* Db functions cloned from enrol/db plugin.
     * ========================================= */

    /**
     * Tries to make connection to the external database.
     *
     * @return null|ADONewConnection
     */
    public function db_init() {
        global $CFG;

        require_once($CFG->libdir.'/adodb/adodb.inc.php');

        // Connect to the external database (forcing new connection).
        $extdb = ADONewConnection($this->get_config('dbtype'));
        if ($this->get_config('debugdb')) {
            $extdb->debug = true;
            ob_start(); // Start output buffer to allow later use of the page headers.
        }

        // The dbtype my contain the new connection URL, so make sure we are not connected yet.
        if (!$extdb->IsConnected()) {
            $result = $extdb->Connect($this->get_config('dbhost'),
                $this->get_config('dbuser'),
                $this->get_config('dbpass'),
                $this->get_config('dbname'), true);
            if (!$result) {
                return null;
            }
        }

        $extdb->SetFetchMode(ADODB_FETCH_ASSOC);
        if ($this->get_config('dbsetupsql')) {
            $extdb->Execute($this->get_config('dbsetupsql'));
        }
        return $extdb;
    }

    public function db_addslashes($text) {
        // Use custom made function for now - it is better to not rely on adodb or php defaults.
        if ($this->get_config('dbsybasequoting')) {
            $text = str_replace('\\', '\\\\', $text);
            $text = str_replace(array('\'', '"', "\0"), array('\\\'', '\\"', '\\0'), $text);
        } else {
            $text = str_replace("'", "''", $text);
        }
        return $text;
    }

    public function db_encode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = $this->db_encode($value);
            }
            return $text;
        } else {
            return core_text::convert($text, 'utf-8', $dbenc);
        }
    }

    public function db_decode($text) {
        $dbenc = $this->get_config('dbencoding');
        if (empty($dbenc) or $dbenc == 'utf-8') {
            return $text;
        }
        if (is_array($text)) {
            foreach ($text as $k => $value) {
                $text[$k] = $this->db_decode($value);
            }
            return $text;
        } else {
            return core_text::convert($text, $dbenc, 'utf-8');
        }
    }

    public function db_get_sql($table, array $conditions, array $fields, $distinct = false, $sort = "") {
        $fields = $fields ? implode(',', $fields) : "*";
        $where = array();
        if ($conditions) {
            foreach ($conditions as $key => $value) {
                $value = $this->db_encode($this->db_addslashes($value));

                $where[] = "$key = '$value'";
            }
        }
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        $sort = $sort ? "ORDER BY $sort" : "";
        $distinct = $distinct ? "DISTINCT" : "";
        $sql = "SELECT $distinct $fields
                  FROM $table
                 $where
                  $sort";
        return $sql;
    }

    public function db_get_sql_like($table2, array $conditions, array $fields, $distinct = false, $sort = "") {
        $fields = $fields ? implode(',', $fields) : "*";
        $where = array();
        if ($conditions) {
            foreach ($conditions as $key => $value) {
                $value = $this->db_encode($this->db_addslashes($value));

                $where[] = "$key LIKE '%$value%'";
            }
        }
        $where = $where ? "WHERE ".implode(" AND ", $where) : "";
        $sort = $sort ? "ORDER BY $sort" : "";
        $distinct = $distinct ? "DISTINCT" : "";
        $sql2 = "SELECT $distinct $fields
                  FROM $table2
                 $where
                  $sort";
        return $sql2;
    }


    /**
     * Returns plugin config value
     * @param  string $name
     * @param  string $default value if config does not exist yet
     * @return string value or default
     */
    public function get_config($name, $default = null) {
        $this->load_config();
        return isset($this->config->$name) ? $this->config->$name : $default;
    }

    /**
     * Sets plugin config value
     * @param  string $name name of config
     * @param  string $value string config value, null means delete
     * @return string value
     */
    public function set_config($name, $value) {
        $pluginname = $this->get_name();
        $this->load_config();
        if ($value === null) {
            unset($this->config->$name);
        } else {
            $this->config->$name = $value;
        }
        set_config($name, $value, "local_$pluginname");
    }

    /**
     * Makes sure config is loaded and cached.
     * @return void
     */
    public function load_config() {
        if (!isset($this->config)) {
            $name = $this->get_name();
            $this->config = get_config("local_$name");
        }
    }
}

