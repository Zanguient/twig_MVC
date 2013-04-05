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
 * Main administration script.
 *
 * @package    core
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


// Check that config.php exists, if not then call the install script
if (!file_exists('../config.php')) {
    header('Location: ../install.php');
    die();
}

// Check that PHP is of a sufficient version as soon as possible
if (version_compare(phpversion(), '5.3.2') < 0) {
    $phpversion = phpversion();
    // do NOT localise - lang strings would not work here and we CAN NOT move it to later place
    echo "Totara 2.2 or later requires at least PHP 5.3.2 (currently using version $phpversion).<br />";
    echo "Please upgrade your server software or install an older Totara version.";
    die();
}

// make sure iconv is available and actually works
if (!function_exists('iconv')) {
    // this should not happen, this must be very borked install
    echo 'Totara requires the iconv PHP extension. Please install or enable the iconv extension.';
    die();
}
if (iconv('UTF-8', 'UTF-8//IGNORE', 'abc') !== 'abc') {
    // known to be broken in mid-2011 MAMP installations
    echo 'Broken iconv PHP extension detected, installation/upgrade can not continue.';
    die();
}

define('NO_OUTPUT_BUFFERING', true);

require('../config.php');
require_once($CFG->libdir . '/adminlib.php');    // various admin-only functions
require_once($CFG->libdir . '/upgradelib.php');  // general upgrade/install related functions
require_once($CFG->dirroot . '/version.php');


$id             = optional_param('id', '', PARAM_TEXT);
$confirmupgrade = optional_param('confirmupgrade', 0, PARAM_BOOL);
$confirmrelease = optional_param('confirmrelease', 0, PARAM_BOOL);
$confirmplugins = optional_param('confirmplugincheck', 0, PARAM_BOOL);
$showallplugins = optional_param('showallplugins', 0, PARAM_BOOL);
$agreelicense   = optional_param('agreelicense', 0, PARAM_BOOL);
$geterrors = optional_param('geterrors', 0, PARAM_BOOL);
// Check some PHP server settings

$PAGE->set_url('/admin/index.php');
$PAGE->set_pagelayout('admin'); // Set a default pagelayout

$documentationlink = '<a href="http://docs.moodle.org/en/Installation">Installation docs</a>';

if (ini_get_bool('session.auto_start')) {
    print_error('phpvaroff', 'debug', '', (object)array('name'=>'session.auto_start', 'link'=>$documentationlink));
}

if (ini_get_bool('magic_quotes_runtime')) {
    print_error('phpvaroff', 'debug', '', (object)array('name'=>'magic_quotes_runtime', 'link'=>$documentationlink));
}

if (!ini_get_bool('file_uploads')) {
    print_error('phpvaron', 'debug', '', (object)array('name'=>'file_uploads', 'link'=>$documentationlink));
}

if (is_float_problem()) {
    print_error('phpfloatproblem', 'admin', '', $documentationlink);
}

// Set some necessary variables during set-up to avoid PHP warnings later on this page
if (!isset($CFG->release)) {
    $CFG->release = '';
}
if (!isset($CFG->version)) {
    $CFG->version = '';
}

$version = null;
$release = null;
require("$CFG->dirroot/version.php");       // defines $version, $release and $maturity
$CFG->target_release = $release;            // used during installation and upgrades

if (!$version or !$release) {
    print_error('withoutversion', 'debug'); // without version, stop
}

if (!isset($maturity)) {
    // Fallback for now. Should probably be removed in the future.
    $maturity = MATURITY_STABLE;
}

// Totara upgrade version checks - only certain upgrade paths are permitted
// do this early to ensure upgrade hasn't started yet
//
// we also need to prevent attempts to downgrade from Moodle release that
// is later than current totara version (e.g. Moodle 2.3 -> Totara 2.2)
// This is already handled by the core upgrade code as it would detected a
// core downgrade and throw and exception
if (!empty($CFG->local_postinst_hasrun)) {
    $canupgrade = true;
    if (isset($CFG->totara_release)) {
        //Totara 1.1.0 or greater
        $parts = explode(" ", $CFG->totara_release);
        $canupgrade = version_compare(trim($parts[0]), '1.1.17', '>=');
    } else {
        //Totara 1.0 does not set any Totara release info, however if local_postinst_hasrun is true then this is Totara
        //cannot upgrade from any 1.0
        $canupgrade = false;
    }
    // if upgrading from totara, require v1.1.17 or greater
    if (!$canupgrade) {
         echo 'You cannot upgrade to Totara 2.x from this version of Totara. Please upgrade to Totara 1.1.17 or greater first.';
         die();
    }
} else if (empty($CFG->local_postinst_hasrun) &&
        !empty($CFG->version) && $CFG->version < 2010112400) {
    // if upgrading from moodle, require at least v2.0.0
    echo 'You cannot upgrade to Totara 2.x from a Moodle version prior to 2.0 Please upgrade to Totara 1.1.17+ or Moodle 2 first.';
    die();
}

// Turn off xmlstrictheaders during upgrade.
$origxmlstrictheaders = !empty($CFG->xmlstrictheaders);
$CFG->xmlstrictheaders = false;

if (!core_tables_exist()) {
    $PAGE->set_pagelayout('maintenance');
    $PAGE->set_popup_notification_allowed(false);

    // fake some settings
    $CFG->docroot = 'http://docs.moodle.org';

    $strinstallation = get_string('installation', 'install');

    // remove current session content completely
    session_get_instance()->terminate_current();

    if (empty($agreelicense)) {
        $strlicense = get_string('license');

        $PAGE->navbar->add($strlicense);
        $PAGE->set_title($strinstallation.' - Totara '.$TOTARA->release);
        $PAGE->set_heading($strinstallation);
        $PAGE->set_cacheable(false);

        $output = $PAGE->get_renderer('core', 'admin');
        echo $output->install_licence_page();
        die();
    }
    if (empty($confirmrelease)) {
        require_once($CFG->libdir.'/environmentlib.php');
        list($envstatus, $environment_results) = check_moodle_environment(normalize_version($release), ENV_SELECT_RELEASE);
        $strcurrentrelease = get_string('currentrelease');

        $PAGE->navbar->add($strcurrentrelease);
        $PAGE->set_title($strinstallation);
        $PAGE->set_heading($strinstallation . ' - Totara ' . $TOTARA->release);
        $PAGE->set_cacheable(false);

        $output = $PAGE->get_renderer('core', 'admin');
        echo $output->install_environment_page($maturity, $envstatus, $environment_results, $TOTARA->release);
        die();
    }

    //TODO: add a page with list of non-standard plugins here

    $strdatabasesetup = get_string('databasesetup');
    upgrade_init_javascript();

    $PAGE->navbar->add($strdatabasesetup);
    $PAGE->set_title($strinstallation.' - Totara '.$TOTARA->release);
    $PAGE->set_heading($strinstallation);
    $PAGE->set_cacheable(false);

    $output = $PAGE->get_renderer('core', 'admin');
    echo $output->header();

    if (!$DB->setup_is_unicodedb()) {
        if (!$DB->change_db_encoding()) {
            // If could not convert successfully, throw error, and prevent installation
            print_error('unicoderequired', 'admin');
        }
    }

    install_core($version, true);
}


// Check version of Moodle code on disk compared with database
// and upgrade if possible.

$stradministration = get_string('administration');
$PAGE->set_context(context_system::instance());

if (empty($CFG->version)) {
    print_error('missingconfigversion', 'debug');
}

if ($version > $CFG->version
            || (isset($CFG->totara_build)
            && $TOTARA->build > $CFG->totara_build)) {  // upgrade

    purge_all_caches();
    $PAGE->set_pagelayout('maintenance');
    $PAGE->set_popup_notification_allowed(false);

    $a = new stdClass();
    $a->oldversion = '';
    $a->newversion = '';

    // If a Moodle core upgrade:
    if ($version > $CFG->version) {
        $prefix = get_string('moodlecore', 'totara_core').':';
        $a->oldversion .= "{$prefix}<br />{$CFG->release} ({$CFG->version})";
        $a->newversion .= "{$prefix}<br />{$release} ({$version})";
    }

    // If a Totara core upgrade
    if (!isset($CFG->totara_build) || $TOTARA->build > $CFG->totara_build) {
        $prefix = get_string('totaracore','totara_core').':';

        // If a Moodle and a Totara upgrade, tidy up the markup
        if ($version > $CFG->version) {
            $a->oldversion .= '<br /><br />';
            $a->newversion .= '<br /><br />';
        }

        if (!isset($CFG->totara_build)) {
            $a->oldversion .= $prefix.'<br />'.get_string('totarapre11', 'totara_core');
            $a->newversion .= "{$prefix}<br />{$TOTARA->release}";
        } else {
            $a->oldversion .= "{$prefix}<br />{$CFG->totara_release}";
            $a->newversion .= "{$prefix}<br />{$TOTARA->release}";
        }
    }

    if (empty($confirmupgrade)) {
        $strdatabasechecking = get_string('databasechecking', '', $a);

        $PAGE->set_title($stradministration);
        $PAGE->set_heading($strdatabasechecking);
        $PAGE->set_cacheable(false);

        $output = $PAGE->get_renderer('core', 'admin');
        echo $output->upgrade_confirm_page($a->newversion, $maturity);
        die();

    } else if (empty($confirmrelease)){
        require_once($CFG->libdir.'/environmentlib.php');
        list($envstatus, $environment_results) = check_moodle_environment($release, ENV_SELECT_RELEASE);
        $strcurrentrelease = get_string('currentrelease');

        $PAGE->navbar->add($strcurrentrelease);
        $PAGE->set_title($strcurrentrelease);
        $PAGE->set_heading($strcurrentrelease);
        $PAGE->set_cacheable(false);

        $output = $PAGE->get_renderer('core', 'admin');
        echo $output->upgrade_environment_page($TOTARA->release, $envstatus, $environment_results);
        die();

    } else if (empty($confirmplugins)) {
        $strplugincheck = get_string('plugincheck');

        $PAGE->navbar->add($strplugincheck);
        $PAGE->set_title($strplugincheck);
        $PAGE->set_heading($strplugincheck);
        $PAGE->set_cacheable(false);

        $output = $PAGE->get_renderer('core', 'admin');
        echo $output->upgrade_plugin_check_page(plugin_manager::instance(), $version, $showallplugins,
                new moodle_url('/admin/index.php', array('confirmupgrade' => 1, 'confirmrelease' => 1)),
                new moodle_url('/admin/index.php', array('confirmupgrade'=>1, 'confirmrelease'=>1, 'confirmplugincheck'=>1)));
        die();

    } else {
        // Launch main upgrade
        upgrade_core($version, true);
    }
} else if ($version < $CFG->version) {
    // better stop here, we can not continue with plugin upgrades or anything else
    throw new moodle_exception('downgradedcore', 'error', new moodle_url('/admin/'));
}

// Updated human-readable release version if necessary
if ($release <> $CFG->release) {  // Update the release version
    set_config('release', $release);
}

if ( (!isset($CFG->totara_release) || $CFG->totara_release <> $TOTARA->release)
    || (!isset($CFG->totara_build) || $CFG->totara_build <> $TOTARA->build)
    || (!isset($CFG->totara_version) || $CFG->totara_version <> $TOTARA->version)) {
    // Also set Totara release (human readable version)
    set_config("totara_release", $TOTARA->release);
    set_config("totara_build", $TOTARA->build);
    set_config("totara_version", $TOTARA->version);
}

if (moodle_needs_upgrading()) {
    if (!$PAGE->headerprinted) {
        // means core upgrade or installation was not already done
        if (!$confirmplugins) {
            $strplugincheck = get_string('plugincheck');

            $PAGE->set_pagelayout('maintenance');
            $PAGE->set_popup_notification_allowed(false);
            $PAGE->navbar->add($strplugincheck);
            $PAGE->set_title($strplugincheck);
            $PAGE->set_heading($strplugincheck);
            $PAGE->set_cacheable(false);

            $output = $PAGE->get_renderer('core', 'admin');
            echo $output->upgrade_plugin_check_page(plugin_manager::instance(), $version, $showallplugins,
                    new moodle_url('/admin/index.php'),
                    new moodle_url('/admin/index.php', array('confirmplugincheck'=>1)));
            die();
        }
    }
    // install/upgrade all plugins and other parts
    upgrade_noncore(true);
}

// If this is the first install, indicate that this site is fully configured
// except the admin password
if (during_initial_install()) {
    set_config('rolesactive', 1); // after this, during_initial_install will return false.
    set_config('adminsetuppending', 1);
    // we need this redirect to setup proper session
    upgrade_finished("index.php?sessionstarted=1&amp;lang=$CFG->lang");
}

// make sure admin user is created - this is the last step because we need
// session to be working properly in order to edit admin account
 if (!empty($CFG->adminsetuppending)) {
    $sessionstarted = optional_param('sessionstarted', 0, PARAM_BOOL);
    if (!$sessionstarted) {
        redirect("index.php?sessionstarted=1&lang=$CFG->lang");
    } else {
        $sessionverify = optional_param('sessionverify', 0, PARAM_BOOL);
        if (!$sessionverify) {
            $SESSION->sessionverify = 1;
            redirect("index.php?sessionstarted=1&sessionverify=1&lang=$CFG->lang");
        } else {
            if (empty($SESSION->sessionverify)) {
                print_error('installsessionerror', 'admin', "index.php?sessionstarted=1&lang=$CFG->lang");
            }
            unset($SESSION->sessionverify);
        }
    }

    // at this stage there can be only one admin unless more were added by install - users may change username, so do not rely on that
    $adminuser = get_complete_user_data('id', reset(explode(',', $CFG->siteadmins)));

    if ($adminuser->password === 'adminsetuppending') {
        // prevent installation hijacking
        if ($adminuser->lastip !== getremoteaddr()) {
            print_error('installhijacked', 'admin');
        }
        // login user and let him set password and admin details
        $adminuser->newadminuser = 1;
        complete_user_login($adminuser);
        redirect("$CFG->wwwroot/user/editadvanced.php?id=$adminuser->id"); // Edit thyself

    } else {
        unset_config('adminsetuppending');
    }

} else {
    // just make sure upgrade logging is properly terminated
    upgrade_finished('upgradesettings.php');
}

// Turn xmlstrictheaders back on now.
$CFG->xmlstrictheaders = $origxmlstrictheaders;
unset($origxmlstrictheaders);

// Check for valid admin user - no guest autologin
require_login(0, false);
$context = context_system::instance();
require_capability('moodle/site:config', $context);

// check that site is properly customized
$site = get_site();
if (empty($site->shortname)) {
    // probably new installation - lets return to frontpage after this step
    // remove settings that we want uninitialised
    unset_config('registerauth');
    redirect('upgradesettings.php?return=site');
}

// setup critical warnings before printing admin tree block
$insecuredataroot = is_dataroot_insecure(true);
$SESSION->admin_critical_warning = ($insecuredataroot==INSECURE_DATAROOT_ERROR);

$adminroot = admin_get_root();

// Check if there are any new admin settings which have still yet to be set
if (any_new_admin_settings($adminroot)){
    redirect('upgradesettings.php');
}

// Everything should now be set up, and the user is an admin
// Check to see if we are downloading latest errors
if ($geterrors) {
    totara_errors_download();
    die();
}
// Print default admin page with notifications.
$errorsdisplayed = defined('WARN_DISPLAY_ERRORS_ENABLED');

$lastcron = $DB->get_field_sql('SELECT MAX(lastcron) FROM {modules}');
$cronoverdue = ($lastcron < time() - 3600 * 24);
$dbproblems = $DB->diagnose();
$maintenancemode = !empty($CFG->maintenance_enabled);

admin_externalpage_setup('adminnotifications');

//get Totara specific info
$oneyearago = time() - 60*60*24*365;
// See MDL-22481 for why currentlogin is used instead of lastlogin
$sql = "SELECT COUNT(id)
          FROM {user}
         WHERE currentlogin > ?";
$activeusers = $DB->count_records_sql($sql, array($oneyearago));
// Check if any errors in log
$errorrecords = $DB->get_records_sql("SELECT id, timeoccured FROM {errorlog} ORDER BY id DESC", null, 0, 1);

$latesterror = array_shift($errorrecords);
$output = $PAGE->get_renderer('core', 'admin');
echo $output->admin_notifications_page($maturity, $insecuredataroot, $errorsdisplayed,
        $cronoverdue, $dbproblems, $maintenancemode, $latesterror, $activeusers, $TOTARA->release);
