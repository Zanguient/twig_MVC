<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010, 2011 Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Alastair Munro <alastair.munro@totaralms.com>
 * @author Aaron Barnes <aaron.barnes@totaralms.com>
 * @author Francois Marier <francois@catalyst.net.nz>
 * @package modules
 * @subpackage facetoface
 */
defined('MOODLE_INTERNAL') || die();


require_once($CFG->libdir.'/gradelib.php');
require_once($CFG->dirroot.'/grade/lib.php');
require_once($CFG->dirroot.'/lib/adminlib.php');
require_once($CFG->dirroot . '/user/selector/lib.php');
if (file_exists($CFG->libdir.'/completionlib.php')) {
    require_once($CFG->libdir.'/completionlib.php');
}

/**
 * Definitions for setting notification types
 */
/**
 * Utility definitions
 */
define('MDL_F2F_ICAL',          1);
define('MDL_F2F_TEXT',          2);
define('MDL_F2F_BOTH',          3);
define('MDL_F2F_INVITE',        4);
define('MDL_F2F_CANCEL',        8);

/**
 * Definitions for use in forms
 */
define('MDL_F2F_INVITE_BOTH',        7);     // Send a copy of both 4+1+2
define('MDL_F2F_INVITE_TEXT',        6);     // Send just a plain email 4+2
define('MDL_F2F_INVITE_ICAL',        5);     // Send just a combined text/ical message 4+1
define('MDL_F2F_CANCEL_BOTH',        11);    // Send a copy of both 8+2+1
define('MDL_F2F_CANCEL_TEXT',        10);    // Send just a plan email 8+2
define('MDL_F2F_CANCEL_ICAL',        9);     // Send just a combined text/ical message 8+1

// Custom field related constants
define('CUSTOMFIELD_DELIMITER', '##SEPARATOR##');
define('CUSTOMFIELD_TYPE_TEXT',        0);
define('CUSTOMFIELD_TYPE_SELECT',      1);
define('CUSTOMFIELD_TYPE_MULTISELECT', 2);

// Calendar-related constants
define('CALENDAR_MAX_NAME_LENGTH', 15);
define('F2F_CAL_NONE',      0);
define('F2F_CAL_COURSE',    1);
define('F2F_CAL_SITE',      2);

// Signup status codes (remember to update $MDL_F2F_STATUS)
define('MDL_F2F_STATUS_USER_CANCELLED',     10);
// SESSION_CANCELLED is not yet implemented
define('MDL_F2F_STATUS_SESSION_CANCELLED',  20);
define('MDL_F2F_STATUS_DECLINED',           30);
define('MDL_F2F_STATUS_REQUESTED',          40);
define('MDL_F2F_STATUS_APPROVED',           50);
define('MDL_F2F_STATUS_WAITLISTED',         60);
define('MDL_F2F_STATUS_BOOKED',             70);
define('MDL_F2F_STATUS_NO_SHOW',            80);
define('MDL_F2F_STATUS_PARTIALLY_ATTENDED', 90);
define('MDL_F2F_STATUS_FULLY_ATTENDED',     100);

// This array must match the status codes above, and the values
// must equal the end of the constant name but in lower case
global $MDL_F2F_STATUS;
$MDL_F2F_STATUS = array(
    MDL_F2F_STATUS_USER_CANCELLED       => 'user_cancelled',
//  SESSION_CANCELLED is not yet implemented
//    MDL_F2F_STATUS_SESSION_CANCELLED    => 'session_cancelled',
    MDL_F2F_STATUS_DECLINED             => 'declined',
    MDL_F2F_STATUS_REQUESTED            => 'requested',
    MDL_F2F_STATUS_APPROVED             => 'approved',
    MDL_F2F_STATUS_WAITLISTED           => 'waitlisted',
    MDL_F2F_STATUS_BOOKED               => 'booked',
    MDL_F2F_STATUS_NO_SHOW              => 'no_show',
    MDL_F2F_STATUS_PARTIALLY_ATTENDED   => 'partially_attended',
    MDL_F2F_STATUS_FULLY_ATTENDED       => 'fully_attended',
);


/**
 * Returns the human readable code for a face-to-face status
 *
 * @param int $statuscode One of the MDL_F2F_STATUS* constants
 * @return string Human readable code
 */
function facetoface_get_status($statuscode) {
    global $MDL_F2F_STATUS;
    // Check code exists
    if (!isset($MDL_F2F_STATUS[$statuscode])) {
        print_error('F2F status code does not exist: '.$statuscode);
    }

    // Get code
    $string = $MDL_F2F_STATUS[$statuscode];

    // Check to make sure the status array looks to be up-to-date
    if (constant('MDL_F2F_STATUS_'.strtoupper($string)) != $statuscode) {
        print_error('F2F status code array does not appear to be up-to-date: '.$statuscode);
    }

    return $string;
}

/**
 * Prints the cost amount along with the appropriate currency symbol.
 *
 * To set your currency symbol, set the appropriate 'locale' in
 * lang/en_utf8/langconfig.php (or the equivalent file for your
 * language).
 *
 * @param $amount      Numerical amount without currency symbol
 * @param $htmloutput  Whether the output is in HTML or not
 */
function format_cost($amount, $htmloutput=true) {
    setlocale(LC_MONETARY, get_string('locale', 'langconfig'));
    $localeinfo = localeconv();

    $symbol = $localeinfo['currency_symbol'];
    if (empty($symbol)) {
        // Cannot get the locale information, default to en_US.UTF-8
        return '$' . $amount;
    }

    // Character between the currency symbol and the amount
    $separator = '';
    if ($localeinfo['p_sep_by_space']) {
        $separator = $htmloutput ? '&nbsp;' : ' ';
    }

    // The symbol can come before or after the amount
    if ($localeinfo['p_cs_precedes']) {
        return $symbol . $separator . $amount;
    }
    else {
        return $amount . $separator . $symbol;
    }
}

/**
 * Returns the effective cost of a session depending on the presence
 * or absence of a discount code.
 *
 * @param class $sessiondata contains the discountcost and normalcost
 */
function facetoface_cost($userid, $sessionid, $sessiondata, $htmloutput=true) {

    global $CFG,$DB;

    $count = $DB->count_records_sql("SELECT COUNT(*)
                               FROM {facetoface_signups} su,
                                    {facetoface_sessions} se
                              WHERE su.sessionid = ?
                                AND su.userid = ?
                                AND su.discountcode IS NOT NULL
                                AND su.sessionid = se.id", array($sessionid, $userid));
    if ($count > 0) {
        return format_cost($sessiondata->discountcost, $htmloutput);
    } else {
        return format_cost($sessiondata->normalcost, $htmloutput);
    }
}

/**
 * Human-readable version of the duration field used to display it to
 * users
 *
 * @param   integer $duration duration in hours
 * @return  string
 */
function format_duration($duration) {

    $components = explode(':', $duration);

    // Default response
    $string = '';

    // Check for bad characters
    if (trim(preg_match('/[^0-9:\.\s]/', $duration))) {
        return $string;
    }

    if ($components and count($components) > 1) {
        // e.g. "1:30" => "1 hour and 30 minutes"
        $hours = round($components[0]);
        $minutes = round($components[1]);
    }
    else {
        // e.g. "1.5" => "1 hour and 30 minutes"
        $hours = floor($duration);
        $minutes = round(($duration - floor($duration)) * 60);
    }

    // Check if either minutes is out of bounds
    if ($minutes >= 60) {
        return $string;
    }

    if (1 == $hours) {
        $string = get_string('onehour', 'facetoface');
    } elseif ($hours > 1) {
        $string = get_string('xhours', 'facetoface', $hours);
    }

    // Insert separator between hours and minutes
    if ($string != '') {
        $string .= ' ';
    }

    if (1 == $minutes) {
        $string .= get_string('oneminute', 'facetoface');
    } elseif ($minutes > 0) {
        $string .= get_string('xminutes', 'facetoface', $minutes);
    }

    return $string;
}

/**
 * Converts minutes to hours
 */
function facetoface_minutes_to_hours($minutes) {

    if (!intval($minutes)) {
        return 0;
    }

    if ($minutes > 0) {
        $hours = floor($minutes / 60.0);
        $mins = $minutes - ($hours * 60.0);
        return "$hours:$mins";
    }
    else {
        return $minutes;
    }
}

/**
 * Converts hours to minutes
 */
function facetoface_hours_to_minutes($hours)
{
    $components = explode(':', $hours);
    if ($components and count($components) > 1) {
        // e.g. "1:45" => 105 minutes
        $hours = $components[0];
        $minutes = $components[1];
        return $hours * 60.0 + $minutes;
    }
    else {
        // e.g. "1.75" => 105 minutes
        return round($hours * 60.0);
    }
}

/**
 * Turn undefined manager messages into empty strings and deal with checkboxes
 */
function facetoface_fix_settings($facetoface) {

    if (empty($facetoface->emailmanagerconfirmation)) {
        $facetoface->confirmationinstrmngr = null;
    }
    if (empty($facetoface->emailmanagerreminder)) {
        $facetoface->reminderinstrmngr = null;
    }
    if (empty($facetoface->emailmanagercancellation)) {
        $facetoface->cancellationinstrmngr = null;
    }
    if (empty($facetoface->usercalentry)) {
        $facetoface->usercalentry = 0;
    }
    if (empty($facetoface->thirdpartywaitlist)) {
        $facetoface->thirdpartywaitlist = 0;
    }
    if (empty($facetoface->approvalreqd)) {
        $facetoface->approvalreqd = 0;
    }
}

/**
 * Given an object containing all the necessary data, (defined by the
 * form in mod.html) this function will create a new instance and
 * return the id number of the new instance.
 */
function facetoface_add_instance($facetoface) {
    global $DB;
    $facetoface->timemodified = time();

    facetoface_fix_settings($facetoface);
    if ($facetoface->id = $DB->insert_record('facetoface', $facetoface)) {
        facetoface_grade_item_update($facetoface);
    }

    //update any calendar entries
    if ($sessions = facetoface_get_sessions($facetoface->id)) {
        foreach ($sessions as $session) {
            facetoface_update_calendar_entries($session, $facetoface);
        }
    }

    return $facetoface->id;
}

/**
 * Given an object containing all the necessary data, (defined by the
 * form in mod.html) this function will update an existing instance
 * with new data.
 */
function facetoface_update_instance($facetoface, $instanceflag = true) {
    global $DB;

    if ($instanceflag) {
        $facetoface->id = $facetoface->instance;
    }

   facetoface_fix_settings($facetoface);
   if ($return = $DB->update_record('facetoface', $facetoface)) {
        facetoface_grade_item_update($facetoface);

        //update any calendar entries
        if ($sessions = facetoface_get_sessions($facetoface->id)) {
            foreach ($sessions as $session) {
                facetoface_update_calendar_entries($session, $facetoface);
            }
        }
    }
    return $return;
}

/**
 * Given an ID of an instance of this module, this function will
 * permanently delete the instance and any data that depends on it.
 */
function facetoface_delete_instance($id) {
    global $CFG, $DB;

    if (!$facetoface = $DB->get_record('facetoface', array('id' => $id))) {
        return false;
    }

    $result = true;

    $transaction = $DB->start_delegated_transaction();

    $DB->delete_records_select(
        'facetoface_signups_status',
        "signupid IN
        (
            SELECT
            id
            FROM
    {facetoface_signups}
    WHERE
    sessionid IN
    (
        SELECT
        id
        FROM
    {facetoface_sessions}
    WHERE
    facetoface = ? ))
    ", array($facetoface->id));

    $DB->delete_records_select('facetoface_signups', "sessionid IN (SELECT id FROM {facetoface_sessions} WHERE facetoface = ?)", array($facetoface->id));

    $DB->delete_records_select('facetoface_sessions_dates', "sessionid in (SELECT id FROM {facetoface_sessions} WHERE facetoface = ?)", array($facetoface->id));

    $DB->delete_records('facetoface_sessions', array('facetoface' => $facetoface->id));

    $DB->delete_records('facetoface', array('id' => $facetoface->id));

    $DB->delete_records('event', array('modulename' => 'facetoface', 'instance' => $facetoface->id));

    facetoface_grade_item_delete($facetoface);

    $transaction->allow_commit();

    return $result;
}

/**
 * Prepare the user data to go into the database.
 */
function cleanup_session_data($session) {

    // Convert hours (expressed like "1.75" or "2" or "3.5") to minutes
    $session->duration = facetoface_hours_to_minutes($session->duration);

    // Only numbers allowed here
    $session->capacity = preg_replace('/[^\d]/', '', $session->capacity);
    $MAX_CAPACITY = 100000;
    if ($session->capacity < 1) {
        $session->capacity = 1;
    }
    elseif ($session->capacity > $MAX_CAPACITY) {
        $session->capacity = $MAX_CAPACITY;
    }

    // Get the decimal point separator
    setlocale(LC_MONETARY, get_string('locale', 'langconfig'));
    $localeinfo = localeconv();
    $symbol = $localeinfo['decimal_point'];
    if (empty($symbol)) {
        // Cannot get the locale information, default to en_US.UTF-8
        $symbol = '.';
    }

    // Only numbers or decimal separators allowed here
    $session->normalcost = round(preg_replace("/[^\d$symbol]/", '', $session->normalcost));
    $session->discountcost = round(preg_replace("/[^\d$symbol]/", '', $session->discountcost));

    return $session;
}

/**
 * Create a new entry in the facetoface_sessions table
 */
function facetoface_add_session($session, $sessiondates) {
    global $USER, $DB;

    $session->timecreated = time();
    $session = cleanup_session_data($session);

    $eventname = $DB->get_field('facetoface', 'name,id', array('id' => $session->facetoface));

    $session->id = $DB->insert_record('facetoface_sessions', $session);

    if (empty($sessiondates)) {
        // Insert a dummy date record
        $date = new stdClass();
        $date->sessionid = $session->id;
        $date->timestart = 0;
        $date->timefinish = 0;

        $DB->insert_record('facetoface_sessions_dates', $date);
    }
    else {
        foreach ($sessiondates as $date) {
            $date->sessionid = $session->id;

            $DB->insert_record('facetoface_sessions_dates', $date);
        }
    }

    //create any calendar entries
    $session->sessiondates = $sessiondates;
    facetoface_update_calendar_entries($session);

    return $session->id;
}

/**
 * Modify an entry in the facetoface_sessions table
 */
function facetoface_update_session($session, $sessiondates) {
    global $DB;

    $session->timemodified = time();
    $session = cleanup_session_data($session);
    $transaction = $DB->start_delegated_transaction();

    $DB->update_record('facetoface_sessions', $session);
    $DB->delete_records('facetoface_sessions_dates', array('sessionid' => $session->id));

    if (empty($sessiondates)) {
        // Insert a dummy date record
        $date = new stdClass();
        $date->sessionid = $session->id;
        $date->timestart = 0;
        $date->timefinish = 0;
        $DB->insert_record('facetoface_sessions_dates', $date);
    }
    else {
        foreach ($sessiondates as $date) {
            $date->sessionid = $session->id;
            $DB->insert_record('facetoface_sessions_dates', $date);
        }
    }

    //update any calendar entries
    $session->sessiondates = $sessiondates;

    facetoface_update_calendar_entries($session);

    $transaction->allow_commit();

    return facetoface_update_attendees($session);
}

function facetoface_update_calendar_entries($session, $facetoface = null){
    global $USER, $DB;

    if (empty($facetoface)) {
        $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));
    }

    //remove from all calendars
    facetoface_delete_user_calendar_events($session, 'booking');
    facetoface_delete_user_calendar_events($session, 'session');
    facetoface_remove_session_from_calendar($session, $facetoface->course);
    facetoface_remove_session_from_calendar($session, SITEID);

    if (empty($facetoface->showoncalendar) && empty($facetoface->usercalentry)) {
        return true;
    }

    //add to NEW calendartype
    if ($facetoface->usercalentry) {
    //get ALL enrolled/booked users
        $users  = facetoface_get_attendees($session->id);
        if (!in_array($USER->id, $users)) {
            facetoface_add_session_to_calendar($session, $facetoface, 'user', $USER->id, 'session');
        }

        foreach ($users as $user) {
            $eventtype = $user->statuscode == MDL_F2F_STATUS_BOOKED ? 'booking' : 'session';
            facetoface_add_session_to_calendar($session, $facetoface, 'user', $user->id, $eventtype);
        }
    }

    if ($facetoface->showoncalendar == F2F_CAL_COURSE) {
        facetoface_add_session_to_calendar($session, $facetoface, 'course');
    } else if ($facetoface->showoncalendar == F2F_CAL_SITE) {
        facetoface_add_session_to_calendar($session, $facetoface, 'site');
    }

    return true;
}

/**
 * Update attendee list status' on booking size change
 */
function facetoface_update_attendees($session) {
    global $USER, $DB;

    // Get facetoface
    $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));

    // Get course
    $course = $DB->get_record('course', array('id' => $facetoface->course));

    // Update user status'
    $users = facetoface_get_attendees($session->id);

    if ($users) {
        // No/deleted session dates
        if (empty($session->datetimeknown)) {

            // Convert any bookings to waitlists
            foreach ($users as $user) {
                if ($user->statuscode == MDL_F2F_STATUS_BOOKED) {

                    if (!facetoface_user_signup($session, $facetoface, $course, $user->discountcode, $user->notificationtype, MDL_F2F_STATUS_WAITLISTED, $user->id)) {
                        // rollback_sql();
                        return false;
                    }
                }
            }

        // Session dates exist
        } else {
            // Convert earliest signed up users to booked, and make the rest waitlisted
            $capacity = $session->capacity;

            // Count number of booked users
            $booked = 0;
            foreach ($users as $user) {
                if ($user->statuscode == MDL_F2F_STATUS_BOOKED) {
                    $booked++;
                }
            }

            // If booked less than capacity, book some new users
            if ($booked < $capacity) {
                foreach ($users as $user) {
                    if ($booked >= $capacity) {
                        break;
                    }

                    if ($user->statuscode == MDL_F2F_STATUS_WAITLISTED) {

                        if (!facetoface_user_signup($session, $facetoface, $course, $user->discountcode, $user->notificationtype, MDL_F2F_STATUS_BOOKED, $user->id)) {
                            // rollback_sql();
                            return false;
                        }
                        $booked++;
                    }
                }
            }
        }
    }

    return $session->id;
}

/**
 * Return an array of all facetoface activities in the current course
 */
function facetoface_get_facetoface_menu() {
    global $CFG, $DB;
    if ($facetofaces = $DB->get_records_sql("SELECT f.id, c.shortname, f.name
                                            FROM {course} c, {facetoface} f
                                            WHERE c.id = f.course
                                            ORDER BY c.shortname, f.name")) {
        $i=1;
        foreach ($facetofaces as $facetoface) {
            $f = $facetoface->id;
            $facetofacemenu[$f] = $facetoface->shortname.' --- '.$facetoface->name;
            $i++;
        }

        return $facetofacemenu;

    } else {

        return '';

    }
}

/**
 * Delete entry from the facetoface_sessions table along with all
 * related details in other tables
 *
 * @param object $session Record from facetoface_sessions
 */
function facetoface_delete_session($session) {
    global $CFG, $DB;

    $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));

    // Cancel user signups (and notify users)
    $signedupusers = $DB->get_records_sql(
        "
            SELECT DISTINCT
                userid
            FROM
                {facetoface_signups} s
            LEFT JOIN
                {facetoface_signups_status} ss
             ON ss.signupid = s.id
            WHERE
                s.sessionid = ?
            AND ss.superceded = 0
            AND ss.statuscode >= ?
        ", array($session->id, MDL_F2F_STATUS_REQUESTED));

    if ($signedupusers and count($signedupusers) > 0) {
        foreach ($signedupusers as $user) {
            if (facetoface_user_cancel($session, $user->userid, true)) {
                facetoface_send_cancellation_notice($facetoface, $session, $user->userid);
            }
            else {
                return false; // Cannot rollback since we notified users already
            }
        }
    }

    $transaction = $DB->start_delegated_transaction();

    // Remove entries from the teacher calendars
    $DB->delete_records_select('event', "modulename = 'facetoface' AND
                                         eventtype = 'facetofacesession' AND
                                         instance = ? AND description LIKE ?",
                                         array($facetoface->id, "%attendees.php?s={$session->id}%"));

    if ($facetoface->showoncalendar == F2F_CAL_COURSE) {
        // Remove entry from course calendar
        facetoface_remove_session_from_calendar($session, $facetoface->course);
    } else if ($facetoface->showoncalendar == F2F_CAL_SITE) {
        // Remove entry from site-wide calendar
        facetoface_remove_session_from_calendar($session, SITEID);
    }

    // Delete session details
    $DB->delete_records('facetoface_sessions', array('id' => $session->id));

    $DB->delete_records('facetoface_sessions_dates', array('sessionid' => $session->id));

    $DB->delete_records_select(
        'facetoface_signups_status',
        "signupid IN
        (
            SELECT
                id
            FROM
                {facetoface_signups}
            WHERE
                sessionid = {$session->id}
        )
        ");

    $DB->delete_records('facetoface_signups', array('sessionid' => $session->id));

    $transaction->allow_commit();

    return true;
}

/**
 * Subsitute the placeholders in email templates for the actual data
 *
 * Expects the following parameters in the $data object:
 * - datetimeknown
 * - details
 * - discountcost
 * - duration
 * - normalcost
 * - sessiondates
 *
 * @access  public
 * @param   string  $msg            Email message
 * @param   string  $facetofacename F2F name
 * @param   int     $reminderperiod Num business days before event to send reminder
 * @param   obj     $user           The subject of the message
 * @param   obj     $data           Session data
 * @param   int     $sessionid      Session ID
 * @param   bool    $plaintext      Whether the message should contain markup or not
 * @return  string
 */
function facetoface_email_substitutions($msg, $facetofacename, $reminderperiod, $user, $data, $sessionid, $plaintext=true) {
    global $CFG, $DB;

    if (empty($msg)) {
        return '';
    }

    if ($data->datetimeknown) {
        // Scheduled session
        $sessiondate = userdate($data->sessiondates[0]->timestart, get_string('strftimedate'));
        $starttime = userdate($data->sessiondates[0]->timestart, get_string('strftimetime'));
        $finishtime = userdate($data->sessiondates[0]->timefinish, get_string('strftimetime'));

        $alldates = '';
        foreach ($data->sessiondates as $date) {
            if ($alldates != '') {
                $alldates .= "\n";
            }
            $alldates .= userdate($date->timestart, get_string('strftimedate')).', ';
            $alldates .= userdate($date->timestart, get_string('strftimetime')).
                ' to '.userdate($date->timefinish, get_string('strftimetime'));
        }
    }
    else {
        // Wait-listed session
        $sessiondate = get_string('unknowndate', 'facetoface');
        $alldates    = get_string('unknowndate', 'facetoface');
        $starttime   = get_string('unknowntime', 'facetoface');
        $finishtime  = get_string('unknowntime', 'facetoface');
    }

    $msg = str_replace(get_string('placeholder:facetofacename', 'facetoface'), $facetofacename, $msg);
    $msg = str_replace(get_string('placeholder:firstname', 'facetoface'), $user->firstname, $msg);
    $msg = str_replace(get_string('placeholder:lastname', 'facetoface'), $user->lastname, $msg);
    $msg = str_replace(get_string('placeholder:cost', 'facetoface'), facetoface_cost($user->id, $sessionid, $data, false), $msg);
    $msg = str_replace(get_string('placeholder:alldates', 'facetoface'), $alldates, $msg);
    $msg = str_replace(get_string('placeholder:sessiondate', 'facetoface'), $sessiondate, $msg);
    $msg = str_replace(get_string('placeholder:starttime', 'facetoface'), $starttime, $msg);
    $msg = str_replace(get_string('placeholder:finishtime', 'facetoface'), $finishtime, $msg);
    $msg = str_replace(get_string('placeholder:duration', 'facetoface'), format_duration($data->duration), $msg);
    if (empty($data->details)) {
        $msg = str_replace(get_string('placeholder:details', 'facetoface'), '', $msg);
    }
    else {
        $msg = str_replace(get_string('placeholder:details', 'facetoface'), html_to_text($data->details), $msg);
    }
    $msg = str_replace(get_string('placeholder:reminderperiod', 'facetoface'), $reminderperiod, $msg);

    // Replace more meta data
    if ($plaintext) {
        $msg = str_replace(get_string('placeholder:attendeeslink', 'facetoface'), $CFG->wwwroot.'/mod/facetoface/attendees.php?s='.$data->id.'#unapproved', $msg);
    } else {
        $msg = str_replace(get_string('placeholder:attendeeslink', 'facetoface'), '<a href="'.$CFG->wwwroot.'/mod/facetoface/attendees.php?s='.$data->id.'#unapproved">'.
            $CFG->wwwroot.'/mod/facetoface/attendees.php?s='.$data->id.'#unapproved</a>', $msg);
    }
    // Custom session fields (they look like "session:shortname" in the templates)
    $customfields = facetoface_get_session_customfields();
    $customdata = $DB->get_records('facetoface_session_data', array('sessionid' => $data->id), '', 'fieldid, data');
    foreach ($customfields as $field) {
        $placeholder = "[session:{$field->shortname}]";
        $value = '';
        if (!empty($customdata[$field->id])) {
            if (CUSTOMFIELD_TYPE_MULTISELECT == $field->type) {
                $value = str_replace(CUSTOMFIELD_DELIMITER, ', ', $customdata[$field->id]->data);
            } else {
                $value = $customdata[$field->id]->data;
            }
        }

        $msg = str_replace($placeholder, $value, $msg);
    }

    return $msg;
}

/**
 * Function to be run periodically according to the moodle cron
 * Finds all facetoface notifications that have yet to be mailed out, and mails them.
 */
function facetoface_cron() {
    global $CFG, $USER,$DB;

    $signupsdata = facetoface_get_unmailed_reminders();
    if (!$signupsdata) {
        echo "\n".get_string('noremindersneedtobesent', 'facetoface')."\n";
        return true;
    }

    $timenow = time();

    foreach ($signupsdata as $signupdata) {
        if (facetoface_has_session_started($signupdata, $timenow)) {
            // Too late, the session already started
            // Mark the reminder as being sent already
            $newsubmission = new stdClass();
            $newsubmission->id = $signupdata->id;
            $newsubmission->mailedreminder = 1; // magic number to show that it was not actually sent
            if (!$DB->update_record('facetoface_signups', $newsubmission)) {
                echo "ERROR: could not update mailedreminder for submission ID $signupdata->id";
            }
            continue;
        }

        $earlieststarttime = $signupdata->sessiondates[0]->timestart;
        foreach ($signupdata->sessiondates as $date) {
            if ($date->timestart < $earlieststarttime) {
                $earlieststarttime = $date->timestart;
            }
        }

        $reminderperiod = $signupdata->reminderperiod;

        // Convert the period from business days (no weekends) to calendar days
        for ($reminderday = 0; $reminderday < $reminderperiod + 1; $reminderday++ ) {
            $reminderdaytime = $earlieststarttime - ($reminderday * 24 * 3600);
            //use %w instead of %u for Windows compatability
            $reminderdaycheck = userdate($reminderdaytime, '%w');
            // note w runs from Sun=0 to Sat=6
            if ($reminderdaycheck == 0 || $reminderdaycheck == 6) {
                // Saturdays and Sundays are not included in the
                // reminder period as entered by the user, extend
                // that period by 1
                $reminderperiod++;
            }
        }

        $remindertime = $earlieststarttime - ($reminderperiod * 24 * 3600);
        if ($timenow < $remindertime) {
            // Too early to send reminder
            continue;
        }

        if (!$user = $DB->get_record('user', array('id' => $signupdata->userid))) {
            continue;
        }

        // Hack to make sure that the timezone and languages are set properly in emails
        // (i.e. it uses the language and timezone of the recipient of the email)
        $USER->lang = $user->lang;
        $USER->timezone = $user->timezone;

        if (!$course = $DB->get_record('course', array('id' => $signupdata->course))) {
            continue;
        }
        if (!$facetoface = $DB->get_record('facetoface', array('id' => $signupdata->facetofaceid))) {
            continue;
        }

        $postsubject = '';
        $posttext = '';
        $posttextmgrheading = '';

        if (empty($signupdata->mailedreminder)) {
            $postsubject = $facetoface->remindersubject;
            $posttext = $facetoface->remindermessage;
            $posttextmgrheading = $facetoface->reminderinstrmngr;
        }

        if (empty($posttext)) {
            // The reminder message is not set, don't send anything
            continue;
        }

        $postsubject = facetoface_email_substitutions($postsubject, $signupdata->facetofacename, $signupdata->reminderperiod,
                                                      $user, $signupdata, $signupdata->sessionid);
        $posttext = facetoface_email_substitutions($posttext, $signupdata->facetofacename, $signupdata->reminderperiod,
                                                   $user, $signupdata, $signupdata->sessionid);
        $posttextmgrheading = facetoface_email_substitutions($posttextmgrheading, $signupdata->facetofacename, $signupdata->reminderperiod,
                                                             $user, $signupdata, $signupdata->sessionid);

        $posthtml = ''; // FIXME
        $fromaddress = get_config(NULL, 'facetoface_fromaddress');
        if (!$fromaddress) {
            $fromaddress = '';
        }

        if (email_to_user($user, $fromaddress, $postsubject, $posttext, $posthtml)) {
            echo "\n".get_string('sentreminderuser', 'facetoface').": $user->firstname $user->lastname $user->email";

            $newsubmission = new stdClass();
            $newsubmission->id = $signupdata->id;
            $newsubmission->mailedreminder = $timenow;
            if (!$DB->update_record('facetoface_signups', $newsubmission)) {
                echo "ERROR: could not update mailedreminder for submission ID $signupdata->id";
            }

            if (empty($posttextmgrheading)) {
                continue; // no manager message set
            }

            $manager = $user;
            $manager->email = facetoface_get_manageremail($user->id);

            if (empty($manager->email)) {
                continue; // don't know who the manager is
            }

            $copybelow = facetoface_email_substitutions(get_string('setting:defaultreminderinstrmngrcopybelow', 'facetoface'),
                $signupdata->facetofacename, $signupdata->reminderperiod, $user, $signupdata, $signupdata->sessionid) . "\n";
            $managertext  = $posttextmgrheading . $posttext . $copybelow;

            // Send email to mamager
            if (email_to_user($manager, $fromaddress, $postsubject, $managertext, $posthtml)) {
                echo "\n".get_string('sentremindermanager', 'facetoface').": $user->firstname $user->lastname $manager->email";
            }
            else {
                $errormsg = array();
                $errormsg['submissionid'] = $signupdata->id;
                $errormsg['userid'] = $user->id;
                $errormsg['manageremail'] = $manager->email;
                echo get_string('error:cronprefix', 'facetoface').' '.get_string('error:cannotemailmanager', 'facetoface', $errormsg)."\n";
            }
        }
        else {
            $errormsg = array();
            $errormsg['submissionid'] = $signupdata->id;
            $errormsg['userid'] = $user->id;
            $errormsg['useremail'] = $user->email;
            echo get_string('error:cronprefix', 'facetoface').' '.get_string('error:cannotemailuser', 'facetoface', $errormsg)."\n";
        }
    }

    print "\n";
    return true;
}

/**
 * Returns true if the session has started, that is if one of the
 * session dates is in the past.
 *
 * @param class $session record from the facetoface_sessions table
 * @param integer $timenow current time
 */
function facetoface_has_session_started($session, $timenow) {

    if (!$session->datetimeknown) {
        return false; // no date set
    }

    foreach ($session->sessiondates as $date) {
        if ($date->timestart < $timenow) {
            return true;
        }
    }
    return false;
}

/**
 * Returns true if the session has started and has not yet finished.
 *
 * @param class $session record from the facetoface_sessions table
 * @param integer $timenow current time
 */
function facetoface_is_session_in_progress($session, $timenow) {
    if (!$session->datetimeknown) {
        return false;
    }
    foreach ($session->sessiondates as $date) {
        if ($date->timefinish > $timenow && $date->timestart < $timenow) {
            return true;
        }
    }
    return false;
}

/**
 * Get all of the dates for a given session
 */
function facetoface_get_session_dates($sessionid) {
    global $DB;
    $ret = array();

    if ($dates = $DB->get_records('facetoface_sessions_dates', array('sessionid' => $sessionid),'timestart')) {
        $i = 0;
        foreach ($dates as $date) {
            $ret[$i++] = $date;
        }
    }

    return $ret;
}

/**
 * Get a record from the facetoface_sessions table
 *
 * @param integer $sessionid ID of the session
 */
function facetoface_get_session($sessionid) {
    global $DB;
    $session = $DB->get_record('facetoface_sessions', array('id' => $sessionid));

    if ($session) {
        $session->sessiondates = facetoface_get_session_dates($sessionid);
        $session->duration = facetoface_minutes_to_hours($session->duration);
    }

    return $session;
}

/**
 * Get all records from facetoface_sessions for a given facetoface activity and location
 *
 * @param integer $facetofaceid ID of the activity
 * @param string $location location filter (optional)
 */
function facetoface_get_sessions($facetofaceid, $location='') {
    global $CFG,$DB;

    $fromclause = "FROM {facetoface_sessions} s";
    $locationwhere = '';
    $locationparams = array();
    if (!empty($location)) {
        $fromclause = "FROM {facetoface_session_data} d
                       JOIN {facetoface_sessions} s ON s.id = d.sessionid";
        $locationwhere .= " AND d.data = ?";
        $locationparams[] = $location;
    }
    $sessions = $DB->get_records_sql("SELECT s.*
                                   $fromclause
                        LEFT OUTER JOIN (SELECT sessionid, min(timestart) AS mintimestart
                                           FROM {facetoface_sessions_dates} GROUP BY sessionid) m ON m.sessionid = s.id
                                  WHERE s.facetoface = ?
                                        $locationwhere
                               ORDER BY s.datetimeknown, m.mintimestart", array_merge(array($facetofaceid), $locationparams));

    if ($sessions) {
        foreach ($sessions as $key => $value) {
            $sessions[$key]->duration = facetoface_minutes_to_hours($sessions[$key]->duration);
            $sessions[$key]->sessiondates = facetoface_get_session_dates($value->id);
        }
    }
    return $sessions;
}

/**
 * Get a grade for the given user from the gradebook.
 *
 * @param integer $userid        ID of the user
 * @param integer $courseid      ID of the course
 * @param integer $facetofaceid  ID of the Face-to-face activity
 *
 * @returns object String grade and the time that it was graded
 */
function facetoface_get_grade($userid, $courseid, $facetofaceid) {

    $ret = new stdClass();
    $ret->grade = 0;
    $ret->dategraded = 0;

    $grading_info = grade_get_grades($courseid, 'mod', 'facetoface', $facetofaceid, $userid);
    if (!empty($grading_info->items)) {
        $ret->grade = $grading_info->items[0]->grades[$userid]->str_grade;
        $ret->dategraded = $grading_info->items[0]->grades[$userid]->dategraded;
    }

    return $ret;
}

/**
 * Get list of users attending a given session
 *
 * @access public
 * @param integer Session ID
 * @return array
 */
function facetoface_get_attendees($sessionid) {
    global $CFG,$DB;
    $records = $DB->get_records_sql("
        SELECT
            u.id,
            su.id AS submissionid,
            u.firstname,
            u.lastname,
            u.email,
            s.discountcost,
            su.discountcode,
            su.notificationtype,
            f.id AS facetofaceid,
            f.course,
            ss.grade,
            ss.statuscode,
            sign.timecreated
        FROM
            {facetoface} f
        JOIN
            {facetoface_sessions} s
         ON s.facetoface = f.id
        JOIN
            {facetoface_signups} su
         ON s.id = su.sessionid
        JOIN
            {facetoface_signups_status} ss
         ON su.id = ss.signupid
        LEFT JOIN
            (
            SELECT
                ss.signupid,
                MAX(ss.timecreated) AS timecreated
            FROM
                {facetoface_signups_status} ss
            INNER JOIN
                {facetoface_signups} s
             ON s.id = ss.signupid
            AND s.sessionid = ?
            WHERE
                ss.statuscode IN (?,?)
            GROUP BY
                ss.signupid
            ) sign
         ON su.id = sign.signupid
        JOIN
            {user} u
         ON u.id = su.userid
        WHERE
            s.id = ?
        AND ss.superceded != 1
        AND ss.statuscode >= ?
        ORDER BY
            sign.timecreated ASC,
            ss.timecreated ASC
    ", array ($sessionid, MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED, $sessionid, MDL_F2F_STATUS_APPROVED));

    return $records;
}

/**
 * Get a single attendee of a session
 *
 * @access public
 * @param integer Session ID
 * @param integer User ID
 * @return false|object
 */
function facetoface_get_attendee($sessionid, $userid) {
    global $CFG, $DB;
    $record = $DB->get_record_sql("
        SELECT
            u.id,
            su.id AS submissionid,
            u.firstname,
            u.lastname,
            u.email,
            s.discountcost,
            su.discountcode,
            su.notificationtype,
            f.id AS facetofaceid,
            f.course,
            ss.grade,
            ss.statuscode
        FROM
            {facetoface} f
        JOIN
            {facetoface_sessions} s
         ON s.facetoface = f.id
        JOIN
            {facetoface_signups} su
         ON s.id = su.sessionid
        JOIN
            {facetoface_signups_status} ss
         ON su.id = ss.signupid
        JOIN
            {user} u
         ON u.id = su.userid
        WHERE
            s.id = ?
        AND ss.superceded != 1
        AND u.id = ?
    ", array($sessionid, $userid));

    if (!$record) {
        return false;
    }

    return $record;
}

/**
 * Return all user fields to include in exports
 */
function facetoface_get_userfields() {
    global $CFG;

    static $userfields = null;
    if (null == $userfields) {
        $userfields = array();

        if (function_exists('grade_export_user_fields')) {
            $fieldnames = grade_export_user_fields();
            foreach ($fieldnames as $key => $obj) {
                $userfields[$obj->shortname] = $obj->fullname;
            }
        }
        else {
            // Set default fields if the grade export patch is not
            // detected (see MDL-17346)
            $fieldnames = array('firstname', 'lastname', 'email', 'city',
                                'idnumber', 'institution', 'department', 'address');
            foreach ($fieldnames as $shortname) {
                $userfields[$shortname] = get_string($shortname);
            }
            $userfields['managersemail'] = get_string('manageremail', 'facetoface');
        }
    }

    return $userfields;
}

/**
 * Download the list of users attending at least one of the sessions
 * for a given facetoface activity
 */
function facetoface_download_attendance($facetofacename, $facetofaceid, $location, $format) {
    global $CFG;

    $timenow = time();
    $timeformat = str_replace(' ', '_', get_string('strftimedate', 'langconfig'));
    $downloadfilename = clean_filename($facetofacename.'_'.userdate($timenow, $timeformat));

    $dateformat = 0;
    if ('ods' === $format) {
        // OpenDocument format (ISO/IEC 26300)
        require_once($CFG->dirroot.'/lib/odslib.class.php');
        $downloadfilename .= '.ods';
        $workbook = new MoodleODSWorkbook('-');
    } else {
        // Excel format
        require_once($CFG->dirroot.'/lib/excellib.class.php');
        $downloadfilename .= '.xls';
        $workbook = new MoodleExcelWorkbook('-');
        $dateformat =& $workbook->add_format();
        $dateformat->set_num_format('d mmm yy'); // TODO: use format specified in language pack
    }

    $workbook->send($downloadfilename);
    $worksheet =& $workbook->add_worksheet('attendance');
    facetoface_write_worksheet_header($worksheet);
    facetoface_write_activity_attendance($worksheet, 1, $facetofaceid, $location, '', '', $dateformat);
    $workbook->close();
    exit;
}

/**
 * Add the appropriate column headers to the given worksheet
 *
 * @param object $worksheet  The worksheet to modify (passed by reference)
 * @returns integer The index of the next column
 */
function facetoface_write_worksheet_header(&$worksheet)
{
    $pos=0;
    $customfields = facetoface_get_session_customfields();
    foreach ($customfields as $field) {
        if (!empty($field->showinsummary)) {
            $worksheet->write_string(0, $pos++, $field->name);
        }
    }
    $worksheet->write_string(0, $pos++, get_string('date', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('timestart', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('timefinish', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('duration', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('status', 'facetoface'));

    if ($trainerroles = facetoface_get_trainer_roles()) {
        foreach ($trainerroles as $role) {
            $worksheet->write_string(0, $pos++, get_string('role').': '.$role->name);
        }
    }

    $userfields = facetoface_get_userfields();
    foreach ($userfields as $shortname => $fullname) {
        $worksheet->write_string(0, $pos++, $fullname);
    }

    $worksheet->write_string(0, $pos++, get_string('attendance', 'facetoface'));
    $worksheet->write_string(0, $pos++, get_string('datesignedup', 'facetoface'));

    return $pos;
}

/**
 * Write in the worksheet the given facetoface attendance information
 * filtered by location.
 *
 * This function includes lots of custom SQL because it's otherwise
 * way too slow.
 *
 * @param object  $worksheet    Currently open worksheet
 * @param integer $startingrow  Index of the starting row (usually 1)
 * @param integer $facetofaceid ID of the facetoface activity
 * @param string  $location     Location to filter by
 * @param string  $coursename   Name of the course (optional)
 * @param string  $activityname Name of the facetoface activity (optional)
 * @param object  $dateformat   Use to write out dates in the spreadsheet
 * @returns integer Index of the last row written
 */
function facetoface_write_activity_attendance(&$worksheet, $startingrow, $facetofaceid, $location,
                                              $coursename, $activityname, $dateformat)
{
    global $CFG, $DB;

    $trainerroles = facetoface_get_trainer_roles();
    $userfields = facetoface_get_userfields();
    $customsessionfields = facetoface_get_session_customfields();
    $timenow = time();
    $i = $startingrow;

    $locationcondition = '';
    $locationparam = array();
    if (!empty($location)) {
        $locationcondition = "AND s.location = ?";
        $locationparam = array($location);
    }

    // Fast version of "facetoface_get_attendees()" for all sessions
    $sessionsignups = array();
    $signups = $DB->get_records_sql("
        SELECT
            su.id AS submissionid,
            s.id AS sessionid,
            u.*,
            f.course AS courseid,
            ss.grade,
            sign.timecreated
        FROM
            {facetoface} f
        JOIN
            {facetoface_sessions} s
         ON s.facetoface = f.id
        JOIN
            {facetoface_signups} su
         ON s.id = su.sessionid
        JOIN
            {facetoface_signups_status} ss
         ON su.id = ss.signupid
        LEFT JOIN
            (
            SELECT
                ss.signupid,
                MAX(ss.timecreated) AS timecreated
            FROM
                {facetoface_signups_status} ss
            INNER JOIN
                {facetoface_signups} s
             ON s.id = ss.signupid
            INNER JOIN
                {facetoface_sessions} se
             ON s.sessionid = se.id
            AND se.facetoface = $facetofaceid
            WHERE
                ss.statuscode IN (?,?)
            GROUP BY
                ss.signupid
            ) sign
         ON su.id = sign.signupid
        JOIN
            {user} u
         ON u.id = su.userid
        WHERE
            f.id = ?
        AND ss.superceded != 1
        AND ss.statuscode >= ?
        ORDER BY
            s.id, u.firstname, u.lastname
    ", array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED, $facetofaceid, MDL_F2F_STATUS_APPROVED));

    if ($signups) {
        // Get all grades at once
        $userids = array();
        foreach ($signups as $signup) {
            if ($signup->id > 0) {
                $userids[] = $signup->id;
            }
        }
        $grading_info = grade_get_grades(reset($signups)->courseid, 'mod', 'facetoface',
                                         $facetofaceid, $userids);

        foreach ($signups as $signup) {
            $userid = $signup->id;

            if ($customuserfields = facetoface_get_user_customfields($userid, $userfields)) {
                foreach ($customuserfields as $fieldname => $value) {
                    if (!isset($signup->$fieldname)) {
                        $signup->$fieldname = $value;
                    }
                }
            }

            // Set grade
            if (!empty($grading_info->items) and !empty($grading_info->items[0]->grades[$userid])) {
                $signup->grade = $grading_info->items[0]->grades[$userid]->str_grade;
            }

            $sessionsignups[$signup->sessionid][$signup->id] = $signup;
        }
    }

    // Fast version of "facetoface_get_sessions($facetofaceid, $location)"
    $sql = "SELECT d.id as dateid, s.id, s.datetimeknown, s.capacity,
                   s.duration, d.timestart, d.timefinish
              FROM {facetoface_sessions} s
              JOIN {facetoface_sessions_dates} d ON s.id = d.sessionid
              WHERE
                s.facetoface = ?
              AND d.sessionid = s.id
                   $locationcondition
                   ORDER BY s.datetimeknown, d.timestart";

    $sessions = $DB->get_records_sql($sql, array_merge(array($facetofaceid), $locationparam));

    $i = $i - 1; // will be incremented BEFORE each row is written

    foreach ($sessions as $session) {
        $customdata = $DB->get_records('facetoface_session_data', array('sessionid' => $session->id), '', 'fieldid, data');

        $sessiondate = false;
        $starttime   = get_string('wait-listed', 'facetoface');
        $finishtime  = get_string('wait-listed', 'facetoface');
        $status      = get_string('wait-listed', 'facetoface');

        $sessiontrainers = facetoface_get_trainers($session->id);

        if ($session->datetimeknown) {
            // Display only the first date
            if (method_exists($worksheet, 'write_date')) {
                // Needs the patch in MDL-20781
                $sessiondate = (int)$session->timestart;
            }
            else {
                $sessiondate = userdate($session->timestart, get_string('strftimedate', 'langconfig'));
            }
            $starttime   = userdate($session->timestart, get_string('strftimetime', 'langconfig'));
            $finishtime  = userdate($session->timefinish, get_string('strftimetime', 'langconfig'));

            if ($session->timestart < $timenow) {
                $status = get_string('sessionover', 'facetoface');
            }
            else {
                $signupcount = 0;
                if (!empty($sessionsignups[$session->id])) {
                    $signupcount = count($sessionsignups[$session->id]);
                }

                if ($signupcount >= $session->capacity) {
                    $status = get_string('bookingfull', 'facetoface');
                } else {
                    $status = get_string('bookingopen', 'facetoface');
                }
            }
        }

        if (!empty($sessionsignups[$session->id])) {
            foreach ($sessionsignups[$session->id] as $attendee) {
                $i++; $j=0;

                // Custom session fields
                foreach ($customsessionfields as $field) {
                    if (empty($field->showinsummary)) {
                        continue; // skip
                    }

                    $data = '-';
                    if (!empty($customdata[$field->id])) {
                        if (CUSTOMFIELD_TYPE_MULTISELECT == $field->type) {
                            $data = str_replace(CUSTOMFIELD_DELIMITER, "\n", $customdata[$field->id]->data);
                        } else {
                            $data = $customdata[$field->id]->data;
                        }
                    }
                    $worksheet->write_string($i, $j++, $data);
                }

                if (empty($sessiondate)) {
                    $worksheet->write_string($i, $j++, $status); // session date
                }
                else {
                    if (method_exists($worksheet, 'write_date')) {
                        $worksheet->write_date($i, $j++, $sessiondate, $dateformat);
                    }
                    else {
                        $worksheet->write_string($i, $j++, $sessiondate);
                    }
                }
                $worksheet->write_string($i,$j++,$starttime);
                $worksheet->write_string($i,$j++,$finishtime);
                $worksheet->write_number($i,$j++,(int)$session->duration);
                $worksheet->write_string($i,$j++,$status);

                if ($trainerroles) {
                    foreach (array_keys($trainerroles) as $roleid) {
                        if (!empty($sessiontrainers[$roleid])) {
                            $trainers = array();
                            foreach ($sessiontrainers[$roleid] as $trainer) {
                                $trainers[] = fullname($trainer);
                            }

                            $trainers = implode(', ', $trainers);
                        }
                        else {
                            $trainers = '-';
                        }

                        $worksheet->write_string($i, $j++, $trainers);
                    }
                }

                foreach ($userfields as $shortname => $fullname) {
                    $value = '-';
                    if (!empty($attendee->$shortname)) {
                        $value = $attendee->$shortname;
                    }

                    if ('firstaccess' == $shortname or 'lastaccess' == $shortname or
                        'lastlogin' == $shortname or 'currentlogin' == $shortname) {

                            if (method_exists($worksheet, 'write_date')) {
                                $worksheet->write_date($i, $j++, (int)$value, $dateformat);
                            }
                            else {
                                $worksheet->write_string($i, $j++, userdate($value, get_string('strftimedate', 'langconfig')));
                            }
                        }
                    else {
                        $worksheet->write_string($i,$j++,$value);
                    }
                }
                $worksheet->write_string($i,$j++,$attendee->grade);

                if (method_exists($worksheet,'write_date')) {
                    $worksheet->write_date($i, $j++, (int)$attendee->timecreated, $dateformat);
                } else {
                    $signupdate = userdate($attendee->timecreated, get_string('strftimedatetime', 'langconfig'));
                    if (empty($signupdate)) {
                        $signupdate = '-';
                    }
                    $worksheet->write_string($i,$j++, $signupdate);
                }

                if (!empty($coursename)) {
                    $worksheet->write_string($i, $j++, $coursename);
                }
                if (!empty($activityname)) {
                    $worksheet->write_string($i, $j++, $activityname);
                }
            }
        }
        else {
            // no one is sign-up, so let's just print the basic info
            $i++; $j=0;

            // Custom session fields
            foreach ($customsessionfields as $field) {
                if (empty($field->showinsummary)) {
                    continue; // skip
                }

                $data = '-';
                if (!empty($customdata[$field->id])) {
                    if (CUSTOMFIELD_TYPE_MULTISELECT == $field->type) {
                        $data = str_replace(CUSTOMFIELD_DELIMITER, "\n", $customdata[$field->id]->data);
                    } else {
                        $data = $customdata[$field->id]->data;
                    }
                }
                $worksheet->write_string($i, $j++, $data);
            }

            if (empty($sessiondate)) {
                $worksheet->write_string($i, $j++, $status); // session date
            }
            else {
                if (method_exists($worksheet, 'write_date')) {
                    $worksheet->write_date($i, $j++, $sessiondate, $dateformat);
                }
                else {
                    $worksheet->write_string($i, $j++, $sessiondate);
                }
            }
            $worksheet->write_string($i,$j++,$starttime);
            $worksheet->write_string($i,$j++,$finishtime);
            $worksheet->write_number($i,$j++,(int)$session->duration);
            $worksheet->write_string($i,$j++,$status);
            foreach ($userfields as $unused) {
                $worksheet->write_string($i,$j++,'-');
            }
            $worksheet->write_string($i,$j++,'-');

            if (!empty($coursename)) {
                $worksheet->write_string($i, $j++, $coursename);
            }
            if (!empty($activityname)) {
                $worksheet->write_string($i, $j++, $activityname);
            }
        }
    }

    return $i;
}

/**
 * Return an object with all values for a user's custom fields.
 *
 * This is about 15 times faster than the custom field API.
 *
 * @param array $fieldstoinclude Limit the fields returned/cached to these ones (optional)
 */
function facetoface_get_user_customfields($userid, $fieldstoinclude=false)
{
    global $CFG, $DB;

    // Cache all lookup
    static $customfields = null;
    if (null == $customfields) {
        $customfields = array();
    }

    if (!empty($customfields[$userid])) {
        return $customfields[$userid];
    }

    $ret = new stdClass();

    $sql = "SELECT uif.shortname, id.data
              FROM {user_info_field} uif
              JOIN {user_info_data} id ON id.fieldid = uif.id
              WHERE id.userid = ?";

    $customfields = $DB->get_records_sql($sql, array($userid));
    foreach ($customfields as $field) {
        $fieldname = $field->shortname;
        if (false === $fieldstoinclude or !empty($fieldstoinclude[$fieldname])) {
            $ret->$fieldname = $field->data;
        }
    }

    $customfields[$userid] = $ret;
    return $ret;
}

/**
 * Return list of marked submissions that have not been mailed out for currently enrolled students
 */
function facetoface_get_unmailed_reminders() {
    global $CFG, $DB;

    $submissions = $DB->get_records_sql("
        SELECT
            su.*,
            f.course,
            f.id as facetofaceid,
            f.name as facetofacename,
            f.reminderperiod,
            se.duration,
            se.normalcost,
            se.discountcost,
            se.details,
            se.datetimeknown
        FROM
            {facetoface_signups} su
        INNER JOIN
            {facetoface_signups_status} sus
         ON su.id = sus.signupid
        AND sus.superceded = 0
        AND sus.statuscode = ?
        JOIN
            {facetoface_sessions} se
         ON su.sessionid = se.id
        JOIN
            {facetoface} f
         ON se.facetoface = f.id
        WHERE
            su.mailedreminder = 0
        AND se.datetimeknown = 1
    ", array(MDL_F2F_STATUS_BOOKED));

    if ($submissions) {
        foreach ($submissions as $key => $value) {
            $submissions[$key]->duration = facetoface_minutes_to_hours($submissions[$key]->duration);
            $submissions[$key]->sessiondates = facetoface_get_session_dates($value->sessionid);
        }
    }

    return $submissions;
}

/**
 * Add a record to the facetoface submissions table and sends out an
 * email confirmation
 *
 * @param class $session record from the facetoface_sessions table
 * @param class $facetoface record from the facetoface table
 * @param class $course record from the course table
 * @param string $discountcode code entered by the user
 * @param integer $notificationtype type of notifications to send to user
 * @see {{MDL_F2F_INVITE}}
 * @param integer $statuscode Status code to set
 * @param integer $userid user to signup
 * @param bool $notifyuser whether or not to send an email confirmation
 * @param bool $displayerrors whether or not to return an error page on errors
 */
function facetoface_user_signup($session, $facetoface, $course, $discountcode,
                                $notificationtype, $statuscode, $userid = false,
                                $notifyuser = true) {

    global $CFG, $DB;

    // Get user id
    if (!$userid) {
        global $USER;
        $userid = $USER->id;
    }

    $return = false;
    $timenow = time();

    // Check to see if a signup already exists
    if ($existingsignup = $DB->get_record('facetoface_signups', array('sessionid' => $session->id, 'userid' => $userid))) {
        $usersignup = $existingsignup;
    } else {
        // Otherwise, prepare a signup object
        $usersignup = new stdclass;
        $usersignup->sessionid = $session->id;
        $usersignup->userid = $userid;
    }

    $usersignup->mailedreminder = 0;
    $usersignup->notificationtype = $notificationtype;

    $usersignup->discountcode = trim(strtoupper($discountcode));
    if (empty($usersignup->discountcode)) {
        $usersignup->discountcode = null;
    }

 //   begin_sql();

    // Update/insert the signup record
    if (!empty($usersignup->id)) {
        $success =  $DB->update_record('facetoface_signups', $usersignup);
    } else {
        $usersignup->id =  $DB->insert_record('facetoface_signups', $usersignup);
        $success = (bool)$usersignup->id;
    }

    if (!$success) {
        //rollback_sql();
        print_error('error:couldnotupdatef2frecord', 'facetoface');
        return false;
    }

    // Work out which status to use

    // If approval not required
    if (!$facetoface->approvalreqd) {
        $new_status = $statuscode;
    } else {
        // If approval required

        // Get current status (if any)
        $current_status =  $DB->get_field('facetoface_signups_status', 'statuscode', array('signupid' => $usersignup->id, 'superceded' => 0));

        // If approved, then no problem
        if ($current_status == MDL_F2F_STATUS_APPROVED) {
            $new_status = $statuscode;
        } else if ($session->datetimeknown) {
        // Otherwise, send manager request
            $new_status = MDL_F2F_STATUS_REQUESTED;
        } else {
            $new_status = MDL_F2F_STATUS_WAITLISTED;
        }
    }

    // Update status
    if (!facetoface_update_signup_status($usersignup->id, $new_status, $userid)) {
        //rollback_sql();
        print_error('error:f2ffailedupdatestatus', 'facetoface');
        return false;
    }

    // Add to user calendar -- if facetoface usercalentry is set to true
    if ($facetoface->usercalentry) {
        if (in_array($new_status, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED))) {
            facetoface_add_session_to_calendar($session, $facetoface, 'user', $userid, 'booking');
        }
    }

    // Course completion
    if (in_array($new_status, array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED))) {

        $completion = new completion_info($course);
        if ($completion->is_enabled()) {

            $ccdetails = array(
                'course'        => $course->id,
                'userid'        => $userid,
            );

            $cc = new completion_completion($ccdetails);
            $cc->mark_inprogress($timenow);
        }
    }

    // If session has already started, do not send a notification
    if (facetoface_has_session_started($session, $timenow)) {
        $notifyuser = false;
    }

    // Send notification
    if ($notifyuser) {
        // If booked/waitlisted
        switch ($new_status) {
            case MDL_F2F_STATUS_BOOKED:
                $error = facetoface_send_confirmation_notice($facetoface, $session, $userid, $notificationtype, false);
                break;

            case MDL_F2F_STATUS_WAITLISTED:
                $error = facetoface_send_confirmation_notice($facetoface, $session, $userid, $notificationtype, true);
                break;

            case MDL_F2F_STATUS_REQUESTED:
                $error = facetoface_send_request_notice($facetoface, $session, $userid);
                break;
        }

        if (!empty($error)) {
            // rollback_sql();
            print_error($error, 'facetoface');
            return false;
        }

        if (!$DB->update_record('facetoface_signups', $usersignup)) {
            //rollback_sql();
            print_error('error:couldnotupdatef2frecord', 'facetoface');
            return false;
        }
    }

    //commit_sql();

    // Send notification again - this time using Totara not/rem
    if ($notifyuser) {
        // If booked/waitlisted/approval
        $error = facetoface_send_totaramessage($facetoface, $session, $userid, $new_status);
    }

    return true;
}

/**
 * Send booking request notice to user and their manager
 *
 * @param   object  $facetoface Facetoface instance
 * @param   object  $session    Session instance
 * @param   int     $userid     ID of user requesting booking
 * @return  string  Error string, empty on success
 */
function facetoface_send_request_notice($facetoface, $session, $userid) {
    global $DB;
    if (!$manageremail = facetoface_get_manageremail($userid)) {
        return 'error:nomanagersemailset';
    }

    $user = $DB->get_record('user', array('id' => $userid));
    if (!$user) {
        return 'error:invaliduserid';
    }

    $fromaddress = get_config(NULL, 'facetoface_fromaddress');
    if (!$fromaddress) {
        $fromaddress = '';
    }

    $postsubject = facetoface_email_substitutions(
            $facetoface->requestsubject,
            $facetoface->name,
            $facetoface->reminderperiod,
            $user,
            $session,
            $session->id
    );

    $copybelow = facetoface_email_substitutions(
            get_string('setting:defaultrequestinstrmngrcopybelow', 'facetoface'),
            $facetoface->name,
            $facetoface->reminderperiod,
            $user,
            $session,
            $session->id
    );

    $posttext = facetoface_email_substitutions(
            $facetoface->requestmessage,
            $facetoface->name,
            $facetoface->reminderperiod,
            $user,
            $session,
            $session->id
    );

    $posttextmgrheading = facetoface_email_substitutions(
            $facetoface->requestinstrmngr,
            $facetoface->name,
            $facetoface->reminderperiod,
            $user,
            $session,
            $session->id
    );

    // Send to user
    if (!email_to_user($user, $fromaddress, $postsubject, $posttext)) {
        return 'error:cannotsendrequestuser';
    }

    // Send to manager
    $user->email = $manageremail;

    if (!email_to_user($user, $fromaddress, $postsubject, $posttextmgrheading . $copybelow . $posttext)) {
        return 'error:cannotsendrequestmanager';
    }

    return '';
}


/**
 * Update the signup status of a particular signup
 *
 * @param integer $signupid ID of the signup to be updated
 * @param integer $statuscode Status code to be updated to
 * @param integer $createdby User ID of the user causing the status update
 * @param string $note Cancellation reason or other notes
 * @param int $grade Grade
 * @param bool $usetransaction Set to true if database transactions are to be used
 *
 * @returns integer ID of newly created signup status, or false
 *
 */
function facetoface_update_signup_status($signupid, $statuscode, $createdby, $note='', $grade=NULL) {
    global $DB;
    $timenow = time();

    $signupstatus = new stdclass;
    $signupstatus->signupid = $signupid;
    $signupstatus->statuscode = $statuscode;
    $signupstatus->createdby = $createdby;
    $signupstatus->timecreated = $timenow;
    $signupstatus->note = $note;
    $signupstatus->grade = $grade;
    $signupstatus->superceded = 0;
    $signupstatus->mailed = 0;

    $transaction = $DB->start_delegated_transaction();

    if ($statusid = $DB->insert_record('facetoface_signups_status', $signupstatus)) {
        // mark any previous signup_statuses as superceded
        $where = "signupid = ? AND ( superceded = 0 OR superceded IS NULL ) AND id != ?";
        $whereparams = array($signupid, $statusid);
        $DB->set_field_select('facetoface_signups_status', 'superceded', 1, $where, $whereparams);
        $transaction->allow_commit();
        return $statusid;
    } else {
        return false;
    }
}

/**
 * Cancel a user who signed up earlier
 *
 * @param class $session       Record from the facetoface_sessions table
 * @param integer $userid      ID of the user to remove from the session
 * @param bool $forcecancel    Forces cancellation of sessions that have already occurred
 * @param string $errorstr     Passed by reference. For setting error string in calling function
 * @param string $cancelreason Optional justification for cancelling the signup
 */
function facetoface_user_cancel($session, $userid=false, $forcecancel=false, &$errorstr=null, $cancelreason='') {
    if (!$userid) {
        global $USER;
        $userid = $USER->id;
    }

    // if $forcecancel is set, cancel session even if already occurred
    // used by facetotoface_delete_session()
    if (!$forcecancel) {
        $timenow = time();
        // don't allow user to cancel a session that has already occurred
        if (facetoface_has_session_started($session, $timenow)) {
            $errorstr = get_string('error:eventoccurred', 'facetoface');
            return false;
        }
    }

    if (facetoface_user_cancel_submission($session->id, $userid, $cancelreason)) {
        facetoface_remove_session_from_calendar($session, 0, $userid);

        facetoface_update_attendees($session);

        return true;
    }

    $errorstr = get_string('error:cancelbooking', 'facetoface');
    return false;
}

/**
 * Common code for sending confirmation and cancellation notices
 *
 * @param string $postsubject Subject of the email
 * @param string $posttext Plain text contents of the email
 * @param string $posttextmgrheading Header to prepend to $posttext in manager email
 * @param string $notificationtype The type of notification to send
 * @see {{MDL_F2F_INVITE}}
 * @param class $facetoface record from the facetoface table
 * @param class $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @returns string Error message (or empty string if successful)
 */
function facetoface_send_notice($postsubject, $posttext, $posttextmgrheading,
                                $notificationtype, $facetoface, $session, $userid) {
    global $CFG, $DB;

    $user = $DB->get_record('user', array('id' => $userid));
    if (!$user) {
        return 'error:invaliduserid';
    }

    if (empty($postsubject) || empty($posttext)) {
        return '';
    }

    // If no notice type is defined (TEXT or ICAL)
    if (!($notificationtype & MDL_F2F_BOTH)) {
        // If none, make sure they at least get a text email
        $notificationtype |= MDL_F2F_TEXT;
    }

    // If we are cancelling, check if ical cancellations are disabled
    if (($notificationtype & MDL_F2F_CANCEL) &&
        get_config(NULL, 'facetoface_disableicalcancel')) {
        $notificationtype |= MDL_F2F_TEXT; // add a text notification
        $notificationtype &= ~MDL_F2F_ICAL; // remove the iCalendar notification
    }

    // If we are sending an ical attachment, set file name
    if ($notificationtype & MDL_F2F_ICAL) {
        if ($notificationtype & MDL_F2F_INVITE) {
            $icaltype = 'ical:invite';
        } else if ($notificationtype & MDL_F2F_CANCEL) {
            $icaltype = 'ical:cancel';
        }
    }

    // Do iCal attachement stuff
    $icalattachments = array();
    if ($notificationtype & MDL_F2F_ICAL) {
        if (get_config(NULL, 'facetoface_oneemailperday')) {
            // Keep track of all sessiondates
            $sessiondates = $session->sessiondates;

            foreach ($sessiondates as $sessiondate) {
                $session->sessiondates = array($sessiondate); // one day at a time

                $icalattachments[] = facetoface_get_ical_text($notificationtype, $facetoface, $session, $user);
            }

            // Restore session dates
            $session->sessiondates = $sessiondates;
        } else {
            $icalattachments[] = facetoface_get_ical_text($notificationtype, $facetoface, $session, $user);
        }
    }

    // Fill-in the email placeholders
    $postsubject = facetoface_email_substitutions($postsubject, $facetoface->name, $facetoface->reminderperiod,
                                                  $user, $session, $session->id);
    $posttext = facetoface_email_substitutions($posttext, $facetoface->name, $facetoface->reminderperiod,
                                               $user, $session, $session->id);
    $posttextmgrheading = facetoface_email_substitutions($posttextmgrheading, $facetoface->name, $facetoface->reminderperiod,
                                                         $user, $session, $session->id);

    $posthtml = ''; // FIXME
    $fromaddress = get_config(NULL, 'facetoface_fromaddress');
    if (!$fromaddress) {
        $fromaddress = '';
    }

    $usercheck = $DB->get_record('user', array('id' => $userid));

    // Send iCal appointment email (ical only - no accompanying additional text)
    if ($notificationtype & MDL_F2F_ICAL) {
        foreach ($icalattachments as $ical) {
            if (!email_to_user($user, $fromaddress, $postsubject, $ical, $icaltype)) {
                return get_string('error:cannotsendconfirmationuser', 'facetoface');
            }
        }
    }

    // Send plain text email
    if ($notificationtype & MDL_F2F_TEXT) {
        if (!email_to_user($user, $fromaddress, $postsubject, $posttext, $posthtml)) {
            return get_string('error:cannotsendconfirmationuser', 'facetoface');
        }
    }

    // Manager notification
    $manageremail = facetoface_get_manageremail($userid);
    if (!empty($posttextmgrheading) and !empty($manageremail) and $session->datetimeknown) {
        if ($notificationtype & MDL_F2F_CANCEL) {
            $copybelowstring = 'setting:defaultcancellationinstrmngrcopybelow';
        }
       if ($notificationtype & MDL_F2F_INVITE) {
            $copybelowstring = 'setting:defaultconfirmationinstrmngrcopybelow';
        }
        $copybelow = facetoface_email_substitutions(get_string($copybelowstring, 'facetoface'), $facetoface->name,
            $facetoface->reminderperiod, $user, $session, $session->id) . "\n";

        $managertext = $posttextmgrheading . $copybelow . $posttext;
        $manager = $user;
        $manager->email = $manageremail;

        // Leave out the ical attachments in the managers notification
        if (!email_to_user($manager, $fromaddress, $postsubject, $managertext, $posthtml)) {
            return get_string('error:cannotsendconfirmationmanager', 'facetoface');
        }
    }

    // Third-party notification
    if (!empty($facetoface->thirdparty) &&
        ($session->datetimeknown || !empty($facetoface->thirdpartywaitlist))) {

        $thirdparty = $user;
        $recipients = explode(',', $facetoface->thirdparty);
        foreach ($recipients as $recipient) {
            $thirdparty->email = trim($recipient);

            // Leave out the ical attachments in the 3rd parties notification
            if (!email_to_user($thirdparty, $fromaddress, $postsubject, $posttext, $posthtml)) {
                return get_string('error:cannotsendconfirmationthirdparty', 'facetoface');
            }
        }
    }
}

/**
 * Send a confirmation email to the user and manager
 *
 * @param class $facetoface record from the facetoface table
 * @param class $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @param integer $notificationtype Type of notifications to be sent @see {{MDL_F2F_INVITE}}
 * @param boolean $iswaitlisted If the user has been waitlisted
 * @returns string Error message (or empty string if successful)
 */
function facetoface_send_confirmation_notice($facetoface, $session, $userid, $notificationtype, $iswaitlisted) {

    $posttextmgrheading = $facetoface->confirmationinstrmngr;

    if (!$iswaitlisted) {
        $postsubject = $facetoface->confirmationsubject;
        $posttext = $facetoface->confirmationmessage;
    } else {
        $postsubject = $facetoface->waitlistedsubject;
        $posttext = $facetoface->waitlistedmessage;

        // Don't send an iCal attachement when we don't know the date!
        $notificationtype |= MDL_F2F_TEXT; // add a text notification
        $notificationtype &= ~MDL_F2F_ICAL; // remove the iCalendar notification
    }

    // Set invite bit
    $notificationtype |= MDL_F2F_INVITE;

    return facetoface_send_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $facetoface, $session, $userid);
}

/**
 * Send a confirmation email to the user and manager regarding the
 * cancellation
 *
 * @param class $facetoface record from the facetoface table
 * @param class $session record from the facetoface_sessions table
 * @param integer $userid ID of the recipient of the email
 * @returns string Error message (or empty string if successful)
 */
function facetoface_send_cancellation_notice($facetoface, $session, $userid) {
    global $DB;

    $postsubject = $facetoface->cancellationsubject;
    $posttext = $facetoface->cancellationmessage;
    $posttextmgrheading = $facetoface->cancellationinstrmngr;

    // Lookup what type of notification to send
    $notificationtype = $DB->get_field('facetoface_signups', 'notificationtype',
                                  array('sessionid' => $session->id, 'userid' => $userid));

    // Set cancellation bit
    $notificationtype |= MDL_F2F_CANCEL;

    return facetoface_send_notice($postsubject, $posttext, $posttextmgrheading,
                                  $notificationtype, $facetoface, $session, $userid);
}

/**
 * Returns true if the user has registered for a session in the given
 * facetoface activity
 *
 * @global class $USER used to get the current userid
 * @returns integer The session id that we signed up for, false otherwise
 */
function facetoface_check_signup($facetofaceid) {

    global $USER;

    if ($submissions = facetoface_get_user_submissions($facetofaceid, $USER->id)) {
        return reset($submissions)->sessionid;
    } else {
        return false;
    }
}

/**
 * Return the email address of the user's manager if it is
 * defined. Otherwise return an empty string.
 *
 * @param integer $userid User ID of the staff member
 */
function facetoface_get_manageremail($userid) {
    global $DB;

    $sql = "SELECT managerid
        FROM {pos_assignment} pa
        WHERE pa.userid = ? AND pa.type = 1"; // just use primary position for now
    $res = $DB->get_record_sql($sql, array($userid));
    if ($res && isset($res->managerid)) {
        return $DB->get_field('user', 'email', array('id' => $res->managerid));
    } else {
        return ''; // No manager set
    }
}

/**
 * Human-readable version of the format of the manager's email address
 */
function facetoface_get_manageremailformat() {

    $addressformat = get_config(NULL, 'facetoface_manageraddressformat');

    if (!empty($addressformat)) {
        $readableformat = get_config(NULL, 'facetoface_manageraddressformatreadable');
        return get_string('manageremailformat', 'facetoface', $readableformat);
    }

    return '';
}

/**
 * Returns true if the given email address follows the format
 * prescribed by the site administrator
 *
 * @param string $manageremail email address as entered by the user
 */
function facetoface_check_manageremail($manageremail) {

    $addressformat = get_config(NULL, 'facetoface_manageraddressformat');

    if (empty($addressformat) || strpos($manageremail, $addressformat)) {
        return true;
    } else {
        return false;
    }
}

/**
 * Mark the fact that the user attended the facetoface session by
 * giving that user a grade of 100
 *
 * @param array $data array containing the sessionid under the 's' key
 *                    and every submission ID to mark as attended
 *                    under the 'submissionid_XXXX' keys where XXXX is
 *                     the ID of the signup
 */
function facetoface_take_attendance($data) {
    global $USER;

    $sessionid = $data->s;

    // Load session
    if (!$session = facetoface_get_session($sessionid)) {
        error_log('F2F: Could not load facetoface session');
        return false;
    }

    // Check facetoface has finished
    if ($session->datetimeknown && !facetoface_has_session_started($session, time())) {
        error_log('F2F: Can not take attendance for a session that has not yet started');
        return false;
    }

    // Record the selected attendees from the user interface - the other attendees will need their grades set
    // to zero, to indicate non attendance, but only the ticked attendees come through from the web interface.
    // Hence the need for a diff
    $selectedsubmissionids = array();

    // FIXME: This is not very efficient, we should do the grade
    // query outside of the loop to get all submissions for a
    // given Face-to-face ID, then call
    // facetoface_grade_item_update with an array of grade
    // objects.
    foreach ($data as $key => $value) {

        $submissionidcheck = substr($key, 0, 13);
        if ($submissionidcheck == 'submissionid_') {
            $submissionid = substr($key, 13);
            $selectedsubmissionids[$submissionid]=$submissionid;

            // Update status
            switch ($value) {

                case MDL_F2F_STATUS_NO_SHOW:
                    $grade = 0;
                    break;

                case MDL_F2F_STATUS_PARTIALLY_ATTENDED:
                    $grade = 50;
                    break;

                case MDL_F2F_STATUS_FULLY_ATTENDED:
                    $grade = 100;
                    break;

                default:
                    // This use has not had attendance set
                    // Jump to the next item in the foreach loop
                    continue 2;
            }

            facetoface_update_signup_status($submissionid, $value, $USER->id, '', $grade);

            if (!facetoface_take_individual_attendance($submissionid, $grade)) {
                error_log("F2F: could not mark '$submissionid' as ".$value);
                return false;
            }
        }
    }

    return true;
}

/**
 * Mark users' booking requests as declined or approved
 *
 * @param array $data array containing the sessionid under the 's' key
 *                    and an array of request approval/denies
 */
function facetoface_approve_requests($data) {
    global $USER, $DB;

    // Check request data
    if (empty($data->requests) || !is_array($data->requests)) {
        error_log('F2F: No request data supplied');
        return false;
    }

    $sessionid = $data->s;

    // Load session
    if (!$session = facetoface_get_session($sessionid)) {
        error_log('F2F: Could not load facetoface session');
        return false;
    }

    // Load facetoface
    if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
        error_log('F2F: Could not load facetoface instance');
        return false;
    }

    // Load course
    if (!$course = $DB->get_record('course', array('id' => $facetoface->course))) {
        error_log('F2F: Could not load course');
        return false;
    }

    // Loop through requests
    foreach ($data->requests as $key => $value) {

        // Check key/value
        if (!is_numeric($key) || !is_numeric($value)) {
            continue;
        }

        // Load user submission
        if (!$attendee = facetoface_get_attendee($sessionid, $key)) {
            error_log('F2F: User '.$key.' not an attendee of this session');
            continue;
        }

        // Update status
        switch ($value) {

            // Decline
            case 1:
                facetoface_update_signup_status(
                        $attendee->submissionid,
                        MDL_F2F_STATUS_DECLINED,
                        $USER->id
                );

                // Send a cancellation notice to the user
                facetoface_send_cancellation_notice($facetoface, $session, $attendee->id);

                break;

            // Approve
            case 2:
                facetoface_update_signup_status(
                        $attendee->submissionid,
                        MDL_F2F_STATUS_APPROVED,
                        $USER->id
                );

                if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $course->id)) {
                    print_error('error:incorrectcoursemodule', 'facetoface');
                }

                $contextmodule = context_module::instance($cm->id);

                // Check if there is capacity
                if (facetoface_session_has_capacity($session, $contextmodule)) {
                    $status = MDL_F2F_STATUS_BOOKED;
                } else {
                    if ($session->allowoverbook) {
                        $status = MDL_F2F_STATUS_WAITLISTED;
                    }
                }

                // Signup user
                if (!facetoface_user_signup(
                        $session,
                        $facetoface,
                        $course,
                        $attendee->discountcode,
                        $attendee->notificationtype,
                        $status,
                        $attendee->id
                    )) {
                    continue;
                }

                break;

            case 0:
            default:
                // Change nothing
                continue;
        }
    }

    return true;
}

/*
 * Set the grading for an individual submission, to either 0 or 100 to indicate attendance
 * @param $submissionid The id of the submission in the database
 * @param $grading Grade to set
 */
function facetoface_take_individual_attendance($submissionid, $grading) {
    global $USER, $CFG, $DB;

    $timenow = time();

    $record = $DB->get_record_sql("SELECT f.*, s.userid
                                FROM {facetoface_signups} s
                                JOIN {facetoface_sessions} fs ON s.sessionid = fs.id
                                JOIN {facetoface} f ON f.id = fs.facetoface
                                JOIN {course_modules} cm ON cm.instance = f.id
                                JOIN {modules} m ON m.id = cm.module
                                WHERE s.id = ? AND m.name='facetoface'",
                            array($submissionid));

    $grade = new stdclass();
    $grade->userid = $record->userid;
    $grade->rawgrade = $grading;
    $grade->rawgrademin = 0;
    $grade->rawgrademax = 100;
    $grade->timecreated = $timenow;
    $grade->timemodified = $timenow;
    $grade->usermodified = $USER->id;

    return facetoface_grade_item_update($record, $grade);
}

/**
 * Used by course/lib.php to display a few sessions besides the
 * facetoface activity on the course page
 *
 * @global class $USER used to get the current userid
 * @global class $CFG used to get the path to the module
 */
function facetoface_print_coursemodule_info($coursemodule) {
    global $CFG, $USER, $DB, $OUTPUT;

    $contextmodule = context_module::instance($coursemodule->id);
    if (!has_capability('mod/facetoface:view', $contextmodule)) {
        return ''; // not allowed to view this activity
    }
    $contextcourse = context_course::instance($coursemodule->course);
    // can view attendees
    $viewattendees = has_capability('mod/facetoface:viewattendees', $contextcourse);

    $table = '';
    $timenow = time();
    $facetofacepath = "$CFG->wwwroot/mod/facetoface";

    $facetofaceid = $coursemodule->instance;
    $facetoface = $DB->get_record('facetoface', array('id' => $facetofaceid));
    if (!$facetoface) {
        error_log("facetoface: ask to print coursemodule info for a non-existent activity ($facetofaceid)");
        return '';
    }

    $htmlactivitynameonly = $OUTPUT->pix_icon('icon', $facetoface->name, 'facetoface', array('class' => 'activityicon')) . $facetoface->name;
    $strviewallsessions = get_string('viewallsessions', 'facetoface');
    $sessions_url = new moodle_url('/mod/facetoface/view.php', array('f' => $facetofaceid));
    $htmlviewallsessions = html_writer::link($sessions_url, $strviewallsessions, array('class' => 'f2fsessionlinks f2fviewallsessions', 'title' => $strviewallsessions));

    if ($submissions = facetoface_get_user_submissions($facetofaceid, $USER->id)) {
        // User has signedup for the instance
        $submission = array_shift($submissions);

        if ($session = facetoface_get_session($submission->sessionid)) {
            $sessiondate = '';
            $sessiontime = '';

            if ($session->datetimeknown) {
                foreach ($session->sessiondates as $date) {
                    if (!empty($sessiondate)) {
                        $sessiondate .= html_writer::empty_tag('br');
                    }
                    $sessiondate .= userdate($date->timestart, get_string('strftimedate'));
                    if (!empty($sessiontime)) {
                        $sessiontime .= html_writer::empty_tag('br');
                    }
                    $sessiontime .= userdate($date->timestart, get_string('strftimetime')) .
                        ' - ' . userdate($date->timefinish, get_string('strftimetime'));
                }
            }
            else {
                $sessiondate = get_string('wait-listed', 'facetoface');
                $sessiontime = get_string('wait-listed', 'facetoface');
            }

            // don't include the link to cancel a session if it has already occurred
            $cancellink = '';
            if (!facetoface_has_session_started($session, $timenow)) {
                $strcancelbooking = get_string('cancelbooking', 'facetoface');
                $cancel_url = new moodle_url('/mod/facetoface/cancelsignup.php', array('s' => $session->id));
                $cancellink = html_writer::tag('tr', html_writer::tag('td', html_writer::link($cancel_url, $strcancelbooking, array('title' => $strcancelbooking))));
            }

            $strmoreinfo = get_string('moreinfo', 'facetoface');
            $strseeattendees = get_string('seeattendees', 'facetoface');

            $location = '&nbsp;';
            $venue = '&nbsp;';
            $customfielddata = facetoface_get_customfielddata($session->id);
            if (!empty($customfielddata['location'])) {
                $location = $customfielddata['location']->data;
            }
            if (!empty($customfielddata['venue'])) {
                $venue = $customfielddata['venue']->data;
            }

            // don't include the link to view attendees if user is lacking capability
            $attendeeslink = '';
            if ($viewattendees) {
                $attendees_url = new moodle_url('/mod/facetoface/attendees.php', array('s' => $session->id));
                $attendeeslink = html_writer::tag('tr', html_writer::tag('td', html_writer::link($attendees_url, $strseeattendees, array('class' => 'f2fsessionlinks f2fviewattendees', 'title' => $strseeattendees))));
            }

            $signup_url = new moodle_url('/mod/facetoface/signup.php', array('s' => $session->id));

            $table = html_writer::start_tag('table', array('class' => 'table90 inlinetable'))
                .html_writer::start_tag('tr', array('class' => 'f2factivityname'))
                .html_writer::tag('td', $htmlactivitynameonly, array('class' => 'f2fsessionnotice', 'colspan' => '4'))
                .html_writer::end_tag('tr')
                .html_writer::start_tag('tr')
                .html_writer::tag('td', get_string('bookingstatus', 'facetoface'), array('class' => 'f2fsessionnotice', 'colspan' => '4'))
                .html_writer::tag('td', html_writer::tag('span', get_string('options', 'facetoface').':', array('class' => 'f2fsessionnotice')))
                .html_writer::end_tag('tr')
                .html_writer::start_tag('tr', array('class' => 'f2fsessioninfo'))
                .html_writer::tag('td', $location)
                .html_writer::tag('td', $venue)
                .html_writer::tag('td', $sessiondate)
                .html_writer::tag('td', $sessiontime)
                .html_writer::tag('td', html_writer::start_tag('table', array('border' => '0')) . html_writer::start_tag('tr') . html_writer::tag('td', html_writer::link($signup_url, $strmoreinfo, array('class' => 'f2fsessionlinks f2fsessioninfolink', 'title' => $strmoreinfo))))
                .html_writer::end_tag('tr')
                .$attendeeslink
                .$cancellink
                .html_writer::start_tag('tr')
                .html_writer::tag('td', $htmlviewallsessions)
                .html_writer::end_tag('tr')
                .html_writer::end_tag('table') . html_writer::end_tag('td') . html_writer::end_tag('tr')
                .html_writer::end_tag('table');
        }
    } else if ($facetoface->display > 0 && $sessions = facetoface_get_sessions($facetofaceid) ) {

        $table = html_writer::start_tag('table', array('class' => 'f2fsession inlinetable'))
            .html_writer::start_tag('tr', array('class' => 'f2factivityname'))
            .html_writer::tag('td', $htmlactivitynameonly, array('class' => 'f2fsessionnotice', 'colspan' => '2'))
            .html_writer::end_tag('tr')
            .html_writer::start_tag('tr')
            .html_writer::tag('td', get_string('signupforsession', 'facetoface'), array('class' => 'f2fsessionnotice', 'colspan' => '2'))
            .html_writer::end_tag('tr');

        $i=0;
        foreach ($sessions as $session) {
            if ($session->datetimeknown && (facetoface_has_session_started($session, $timenow))) {
                continue;
            }

            if (!facetoface_session_has_capacity($session, $contextmodule)) {
                continue;
            }

            $multiday = '';
            $sessiondate = '';
            $sessiontime = '';

            if ($session->datetimeknown) {
                if (empty($session->sessiondates)) {
                    $sessiondate = get_string('unknowndate', 'facetoface');
                    $sessiontime = get_string('unknowntime', 'facetoface');
                }
                else {
                    $sessiondate = userdate($session->sessiondates[0]->timestart, get_string('strftimedate'));
                    $sessiontime = userdate($session->sessiondates[0]->timestart, get_string('strftimetime')).
                        ' - '.userdate($session->sessiondates[0]->timefinish, get_string('strftimetime'));
                    if (count($session->sessiondates) > 1) {
                        $multiday = ' ('.get_string('multiday', 'facetoface').')';
                    }
                }
            }
            else {
                $sessiondate = get_string('wait-listed', 'facetoface');
            }

            if ($i == 0) {
                $table .= html_writer::start_tag('tr');
                $i++;
            }
            else if ($i++ % 2 == 0) {
                if ($i > $facetoface->display) {
                    break;
                }
                $table .= html_writer::end_tag('tr');
                $table .= html_writer::start_tag('tr');
            }

            $locationstring = '';
            $customfielddata = facetoface_get_customfielddata($session->id);
            if (!empty($customfielddata['location']) && trim($customfielddata['location']->data) != '') {
                $locationstring = $customfielddata['location']->data . ', ';
            }

            if ($coursemodule->uservisible) {
                $signup_url = new moodle_url('/mod/facetoface/signup.php', array('s' => $session->id));
                $table .= html_writer::tag('td', html_writer::link($signup_url, $locationstring . $sessiondate . html_writer::empty_tag('br') . $sessiontime . $multiday, array('class' => 'f2fsessiontime')));
            } else {
                $table .= html_writer::tag('td', html_writer::tag('span', $locationstring . $sessiondate . html_writer::empty_tag('br') . $sessiontime . $multiday, array('class' => 'f2fsessiontime')));
            }
        }
        if ($i++ % 2 == 0) {
            $table .= html_writer::tag('td', "&nbsp;");
        }

        $table .= html_writer::end_tag('tr')
            .html_writer::start_tag('tr')
            .html_writer::tag('td', $coursemodule->uservisible ? $htmlviewallsessions : $strviewallsessions, array('colspan' => '2'))
            .html_writer::end_tag('tr')
            .html_writer::end_tag('table');
    }
    elseif (has_capability('mod/facetoface:viewemptyactivities', $contextmodule)) {
        return html_writer::tag('span', $htmlactivitynameonly . html_writer::empty_tag('br') . $htmlviewallsessions, array('class' => 'f2fsessionnotice f2factivityname f2fonepointfive'));
    }
    else {
        // Nothing to display to this user
    }

    return $table;
}

/**
 * Returns the ICAL data for a facetoface meeting.
 *
 * @param integer $method The method, @see {{MDL_F2F_INVITE}}
 * @param object $facetoface The mod instance
 * @param object $session The session within the facetoface instance
 * @param object $user The user booked onto the session
 * @return string the ical text properly formatted to open in Outlook
 */
function facetoface_get_ical_text($method, $facetoface, $session, $user) {
    global $CFG, $DB;

    // First, generate all the VEVENT blocks
    $VEVENTS = '';
    foreach ($session->sessiondates as $date) {
        // Date that this representation of the calendar information was created -
        // we use the time the session was created
        // http://www.kanzaki.com/docs/ical/dtstamp.html
        $DTSTAMP = facetoface_ical_generate_timestamp($session->timecreated);

        // UIDs should be globally unique
        $urlbits = parse_url($CFG->wwwroot);
        $sql = "SELECT COUNT(*)
            FROM {facetoface_signups} su
            INNER JOIN {facetoface_signups_status} sus ON su.id = sus.signupid
            WHERE su.userid = ?
                AND su.sessionid = ?
                AND sus.superceded = 1
                AND sus.statuscode = ? ";
        $params = array($user->id, $session->id, MDL_F2F_STATUS_USER_CANCELLED);
        $UID =
            $DTSTAMP .
            '-' . substr(md5($CFG->siteidentifier . $session->id . $date->id), -8) .   // Unique identifier, salted with site identifier
            '-' . $DB->count_records_sql($sql, $params) .                              // New UID if this is a re-signup ;)
            '@' . $urlbits['host'];                                                    // Hostname for this moodle installation

        $DTSTART = facetoface_ical_generate_timestamp($date->timestart);
        $DTEND   = facetoface_ical_generate_timestamp($date->timefinish);

        // FIXME: currently we are not sending updates if the times of the
        // sesion are changed. This is not ideal!
        $SEQUENCE = ($method & MDL_F2F_CANCEL) ? 1 : 0;

        $SUMMARY     = str_replace("\\n", "\\n ", facetoface_ical_escape($facetoface->name, true));
        $DESCRIPTION = facetoface_ical_escape(get_string('icaldescription', 'facetoface', $facetoface), true);

        // Get the location data from custom fields if they exist
        $customfielddata = facetoface_get_customfielddata($session->id);
        $locationstring = '';
        if (!empty($customfielddata['room'])) {
            $locationstring .= $customfielddata['room']->data;
        }
        if (!empty($customfielddata['venue'])) {
            if (!empty($locationstring)) {
                $locationstring .= "\n";
            }
            $locationstring .= $customfielddata['venue']->data;
        }
        if (!empty($customfielddata['location'])) {
            if (!empty($locationstring)) {
                $locationstring .= "\n";
            }
            $locationstring .= $customfielddata['location']->data;
        }

        // NOTE: Newlines are meant to be encoded with the literal sequence
        // '\n'. But evolution presents a single line text field for location,
        // and shows the newlines as [0x0A] junk. So we switch it for commas
        // here. Remember commas need to be escaped too.
        $LOCATION    = str_replace('\n', '\, ', facetoface_ical_escape($locationstring));

        $ORGANISEREMAIL = get_config(NULL, 'facetoface_fromaddress');

        $ROLE = 'REQ-PARTICIPANT';
        $CANCELSTATUS = '';
        if ($method & MDL_F2F_CANCEL) {
            $ROLE = 'NON-PARTICIPANT';
            $CANCELSTATUS = "\nSTATUS:CANCELLED";
        }

        $icalmethod = ($method & MDL_F2F_INVITE) ? 'REQUEST' : 'CANCEL';

        // FIXME: if the user has input their name in another language, we need
        // to set the LANGUAGE property parameter here
        $USERNAME = fullname($user);
        $MAILTO   = $user->email;

        // The extra newline at the bottom is so multiple events start on their
        // own lines. The very last one is trimmed outside the loop
        $VEVENTS .= "
BEGIN:VEVENT
ORGANIZER;CN={$ORGANISEREMAIL}:MAILTO:{$ORGANISEREMAIL}
DTSTART:{$DTSTART}
DTEND:{$DTEND}
LOCATION:{$LOCATION}
TRANSP:OPAQUE{$CANCELSTATUS}
SEQUENCE:{$SEQUENCE}
UID:{$UID}
DTSTAMP:{$DTSTAMP}
DESCRIPTION:{$DESCRIPTION}
SUMMARY:{$SUMMARY}
PRIORITY:5
CLASS:PRIVATE
ATTENDEE;CUTYPE=INDIVIDUAL;ROLE={$ROLE};PARTSTAT=NEEDS-ACTION;
 RSVP=FALSE;CN={$USERNAME};LANGUAGE=en:MAILTO:{$MAILTO}
END:VEVENT

";
    }

    $VEVENTS = trim($VEVENTS);

    $template = "
BEGIN:VCALENDAR
PRODID:-//Moodle//NONSGML Facetoface//EN
VERSION:2.0
METHOD:{$icalmethod}
{$VEVENTS}
END:VCALENDAR
";

    return $template;
}


/**
* Returns a date string properly formatted for an iCal request in Outlook
*
* @param integer $timestamp standard Unix timestamp
* @return string The timestamp as a readable iCal date string
*/
function facetoface_ical_generate_timestamp($timestamp) {
    return gmdate("Ymd\THis\Z", $timestamp);
}

/**
 * Escapes data of the text datatype in ICAL documents.
 *
 * See RFC2445 or http://www.kanzaki.com/docs/ical/text.html or a more readable definition
 */
function facetoface_ical_escape($text, $converthtml=false) {
    if (empty($text)) {
        return '';
    }

    if ($converthtml) {
        $text = html_to_text($text);
    }

    $text = str_replace('"', '\"', $text);
    $text = str_replace("\\", "\\\\", $text);
    $text = str_replace(",", "\,", $text);
    $text = str_replace(";", "\;", $text);
    $text = str_replace("\n", "\\n", $text);
    // Text should be wordwrapped at 75 octets, and there should be one
    // whitespace after the newline that does the wrapping
    $text = implode("\n ",str_split($text, 50));
    return $text;
}

/**
 * Update grades by firing grade_updated event
 *
 * @param object $facetoface null means all facetoface activities
 * @param int $userid specific user only, 0 mean all (not used here)
 */
function facetoface_update_grades($facetoface=null, $userid=0) {
    global $DB;
    if ($facetoface != null) {
            facetoface_grade_item_update($facetoface);
    } else {
        $sql = "SELECT f.*, cm.idnumber as cmidnumber
                  FROM {facetoface} f
                  JOIN {course_modules} cm ON cm.instance = f.id
                  JOIN {modules} m ON m.id = cm.module
                 WHERE m.name='facetoface'";
        if ($rs = $DB->get_recordset_sql($sql)) {
            foreach ($rs as $facetoface) {
                facetoface_grade_item_update($facetoface);
            }
            $rs->close();
        }
    }

    return true;
}

/**
 * Create grade item for given Face-to-face session
 *
 * @param int facetoface  Face-to-face activity (not the session) to grade
 * @param mixed grades    grades objects or 'reset' (means reset grades in gradebook)
 * @return int 0 if ok, error code otherwise
 */
function facetoface_grade_item_update($facetoface, $grades=NULL) {
    global $CFG, $DB;

    if (!isset($facetoface->cmidnumber)) {

        $sql = "SELECT cm.idnumber as cmidnumber
                  FROM {course_modules} cm
                  JOIN {modules} m ON m.id = cm.module
                 WHERE m.name='facetoface' AND cm.instance = ?";
        $facetoface->cmidnumber = $DB->get_field_sql($sql, array($facetoface->id));
    }

    $params = array('itemname' => $facetoface->name,
                    'idnumber' => $facetoface->cmidnumber);

    $params['gradetype'] = GRADE_TYPE_VALUE;
    $params['grademin']  = 0;
    $params['gradepass'] = 100;
    $params['grademax']  = 100;

    if ($grades  === 'reset') {
        $params['reset'] = true;
        $grades = NULL;
    }

    $retcode = grade_update('mod/facetoface', $facetoface->course, 'mod', 'facetoface',
                            $facetoface->id, 0, $grades, $params);
    return ($retcode === GRADE_UPDATE_OK);
}

/**
 * Delete grade item for given facetoface
 *
 * @param object $facetoface object
 * @return object facetoface
 */
function facetoface_grade_item_delete($facetoface) {
    $retcode = grade_update('mod/facetoface', $facetoface->course, 'mod', 'facetoface',
                            $facetoface->id, 0, NULL, array('deleted' => 1));
    return ($retcode === GRADE_UPDATE_OK);
}

/**
 * Return number of attendees signed up to a facetoface session
 *
 * @param integer $session_id
 * @param integer $status MDL_F2F_STATUS_* constant (optional)
 * @return integer
 */
function facetoface_get_num_attendees($session_id, $status=MDL_F2F_STATUS_BOOKED) {
    global $CFG, $DB;

    $sql = 'SELECT count(ss.id)
        FROM
            {facetoface_signups} su
        JOIN
            {facetoface_signups_status} ss
        ON
            su.id = ss.signupid
        WHERE
            sessionid = ?
        AND
            ss.superceded=0
        AND
        ss.statuscode >= ?';

    // for the session, pick signups that haven't been superceded, or cancelled
    return (int) $DB->count_records_sql($sql, array($session_id, $status));
}

/**
 * Return all of a users' submissions to a facetoface
 *
 * @param integer $facetofaceid
 * @param integer $userid
 * @param boolean $includecancellations
 * @return array submissions | false No submissions
 */
function facetoface_get_user_submissions($facetofaceid, $userid, $includecancellations=false) {
    global $CFG,$DB;

    $whereclause = "s.facetoface = ? AND su.userid = ? AND ss.superceded != 1";
    $whereparams = array($facetofaceid, $userid);

    // If not show cancelled, only show requested and up status'
    if (!$includecancellations) {
        $whereclause .= ' AND ss.statuscode >= ? AND ss.statuscode < ?';
        $whereparams = array_merge($whereparams, array(MDL_F2F_STATUS_REQUESTED, MDL_F2F_STATUS_NO_SHOW));
    }

    //TODO fix mailedconfirmation, timegraded, timecancelled, etc
    return $DB->get_records_sql("
        SELECT
            su.id,
            s.facetoface,
            s.id as sessionid,
            su.userid,
            0 as mailedconfirmation,
            su.mailedreminder,
            su.discountcode,
            ss.timecreated,
            ss.timecreated as timegraded,
            s.timemodified,
            0 as timecancelled,
            su.notificationtype,
            ss.statuscode
        FROM
            {facetoface_sessions} s
        JOIN
            {facetoface_signups} su
         ON su.sessionid = s.id
        JOIN
            {facetoface_signups_status} ss
         ON su.id = ss.signupid
        WHERE
            {$whereclause}
        ORDER BY
            s.timecreated
    ", $whereparams);
}

/**
 * Cancel users' submission to a facetoface session
 *
 * @param integer $sessionid   ID of the facetoface_sessions record
 * @param integer $userid      ID of the user record
 * @param string $cancelreason Short justification for cancelling the signup
 * @return boolean success
 */
function facetoface_user_cancel_submission($sessionid, $userid, $cancelreason='') {
    global $DB;

    $signup = $DB->get_record('facetoface_signups', array('sessionid' => $sessionid, 'userid' => $userid));
    if (!$signup) {
        return true; // not signed up, nothing to do
    }

    $result = facetoface_update_signup_status($signup->id, MDL_F2F_STATUS_USER_CANCELLED, $userid, $cancelreason);

    if ($result) {
        // notify cancelled
        if (!$session = facetoface_get_session($sessionid)) {
            error_log('F2F: Could not load facetoface session');
            return false;
        }
        if (!$facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface))) {
            error_log('F2F: Could not load facetoface instance');
            return false;
        }
        $error = facetoface_send_totaramessage($facetoface, $session, $userid, MDL_F2F_STATUS_USER_CANCELLED);
    }

    return $result;
}

/**
 * A list of actions in the logs that indicate view activity for participants
 */
function facetoface_get_view_actions() {
    return array('view', 'view all');
}

/**
 * A list of actions in the logs that indicate post activity for participants
 */
function facetoface_get_post_actions() {
    return array('cancel booking', 'signup');
}

/**
 * Return a small object with summary information about what a user
 * has done with a given particular instance of this module (for user
 * activity reports.)
 *
 * $return->time = the time they did it
 * $return->info = a short text description
 */
function facetoface_user_outline($course, $user, $mod, $facetoface) {

    $result = new stdClass;

    $grade = facetoface_get_grade($user->id, $course->id, $facetoface->id);
    if ($grade->grade > 0) {
        $result = new stdClass;
        $result->info = get_string('grade') . ': ' . $grade->grade;
        $result->time = $grade->dategraded;
    }
    elseif ($submissions = facetoface_get_user_submissions($facetoface->id, $user->id)) {
        $result->info = get_string('usersignedup', 'facetoface');
        $result->time = reset($submissions)->timecreated;
    }
    else {
        $result->info = get_string('usernotsignedup', 'facetoface');
    }

    return $result;
}

/**
 * Print a detailed representation of what a user has done with a
 * given particular instance of this module (for user activity
 * reports).
 */
function facetoface_user_complete($course, $user, $mod, $facetoface) {

    $grade = facetoface_get_grade($user->id, $course->id, $facetoface->id);

    if ($submissions = facetoface_get_user_submissions($facetoface->id, $user->id, true)) {
        print get_string('grade').': '.$grade->grade . html_writer::empty_tag('br');
        if ($grade->dategraded > 0) {
            $timegraded = trim(userdate($grade->dategraded, get_string('strftimedatetime')));
            print '('.format_string($timegraded).')'. html_writer::empty_tag('br');
        }
        echo html_writer::empty_tag('br');

        foreach ($submissions as $submission) {
            $timesignedup = trim(userdate($submission->timecreated, get_string('strftimedatetime')));
            print get_string('usersignedupon', 'facetoface', format_string($timesignedup)) . html_writer::empty_tag('br');

            if ($submission->timecancelled > 0) {
                $timecancelled = userdate($submission->timecancelled, get_string('strftimedatetime'));
                print get_string('usercancelledon', 'facetoface', format_string($timecancelled)) . html_writer::empty_tag('br');
            }
        }
    }
    else {
        print get_string('usernotsignedup', 'facetoface');
    }

    return true;
}

/**
 * Add a link to the session to the courses calendar.
 *
 * @param class   $session          Record from the facetoface_sessions table
 * @param class   $eventname        Name to display for this event
 * @param string  $calendartype     Which calendar to add the event to (user, course, site)
 * @param int     $userid           Optional param for user calendars
 * @param string  $eventtype        Optional param for user calendar (booking/session)
 */
function facetoface_add_session_to_calendar($session, $facetoface, $calendartype = 'none', $userid = 0, $eventtype = 'session') {
    global $CFG, $DB;

    if (empty($session->datetimeknown)) {
        return true; //date unkown, can't add to calendar
    }

    if (empty($facetoface->showoncalendar) && empty($facetoface->usercalentry)) {
        return true; //facetoface calendar settings prevent calendar
    }

    $description = '';
    if (!empty($facetoface->description)) {
        $description .= html_writer::tag('p', clean_param($facetoface->description, PARAM_CLEANHTML));
    }
    $description .= facetoface_print_session($session, false, true, true);
    $linkurl = new moodle_url('/mod/facetoface/signup.php', array('s' => $session->id));
    $linktext = get_string('signupforthissession', 'facetoface');

    if ($calendartype == 'site' && $facetoface->showoncalendar == F2F_CAL_SITE) {
        $courseid = SITEID;
        $description .= html_writer::link($linkurl, $linktext);
    } else if ($calendartype == 'course' && $facetoface->showoncalendar == F2F_CAL_COURSE) {
        $courseid = $facetoface->course;
        $description .= html_writer::link($linkurl, $linktext);
    } else if ($calendartype == 'user' && $facetoface->usercalentry) {
        $courseid = 0;
        $urlvar = ($eventtype == 'session') ? 'attendees' : 'signup';
        $linkurl = $CFG->wwwroot . "/mod/facetoface/" . $urlvar . ".php?s=$session->id";
        $description .= get_string("calendareventdescription{$eventtype}", 'facetoface', $linkurl);
    } else {
        return true;
    }

    $shortname = $facetoface->shortname;
    if (empty($shortname)) {
        $shortname = substr($facetoface->name, 0, CALENDAR_MAX_NAME_LENGTH);
    }

    $result = true;
    foreach ($session->sessiondates as $date) {
        $newevent = new stdClass();
        $newevent->name = $shortname;
        $newevent->description = $description;
        $newevent->format = FORMAT_HTML;
        $newevent->courseid = $courseid;
        $newevent->groupid = 0;
        $newevent->userid = $userid;
        $newevent->uuid = "{$session->id}";
        $newevent->instance = $session->facetoface;
        $newevent->modulename = 'facetoface';
        $newevent->eventtype = "facetoface{$eventtype}";
        $newevent->timestart = $date->timestart;
        $newevent->timeduration = $date->timefinish - $date->timestart;
        $newevent->visible = 1;
        $newevent->timemodified = time();

        if ($calendartype == 'user' && $eventtype == 'booking') {
            //Check for and Delete the 'created' calendar event to reduce multiple entries for the same event
            $DB->delete_records('event', array('name' => $shortname, 'userid' => $userid,
                'instance' => $session->facetoface, 'eventtype' => 'facetofacesession'));
        }

        $result = $result && $DB->insert_record('event', $newevent);
    }

    return $result;
}

/**
 * Remove all entries in the course calendar which relate to this session.
 *
 * @param class $session    Record from the facetoface_sessions table
 * @param integer $userid   ID of the user
 */
function facetoface_remove_session_from_calendar($session, $courseid = 0, $userid = 0) {
    global $DB;

    $params = array($session->facetoface, $userid, $courseid, $session->id);

    return $DB->delete_records_select('event', "modulename = 'facetoface' AND
                                                instance = ? AND
                                                userid = ? AND
                                                courseid = ? AND
                                                uuid = ?", $params);
}

/**
 * Update the date/time of events in the Moodle Calendar when a
 * session's dates are changed.
 *
 * @param class  $session    Record from the facetoface_sessions table
 * @param string $eventtype  Type of the event (booking or session)
 */
function facetoface_update_user_calendar_events($session, $eventtype) {
    global $DB;

    $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));

    if (empty($facetoface->usercalentry) || $facetoface->usercalentry == 0) {
        return true;
    }

    $users = facetoface_delete_user_calendar_events($session, $eventtype);

    // Add this session to these users' calendar
    foreach ($users as $user) {
        facetoface_add_session_to_calendar($session, $facetoface, 'user', $user->userid, $eventtype);
    }
    return true;
}

/**
 *Delete all user level calendar events for a face to face session
 *
 * @param class     $session    Record from the facetoface_sessions table
 * @param string    $eventtype  Type of the event (booking or session)
 */
function facetoface_delete_user_calendar_events($session, $eventtype) {
    global $CFG, $DB;

    $whereclause = "modulename = 'facetoface' AND
                    eventtype = 'facetoface$eventtype' AND
                    instance = ?";

    $whereparams = array($session->facetoface);

    if ('session' == $eventtype) {
        $likestr = "%attendees.php?s={$session->id}%";
        $like = $DB->sql_like('description', '?');
        $whereclause .= " AND $like";

        $whereparams[] = $likestr;
    }

    //users calendar
    $users = $DB->get_records_sql("SELECT DISTINCT userid
        FROM {event}
        WHERE $whereclause", $whereparams);

    if ($users && count($users) > 0) {
        // Delete the existing events
        $DB->delete_records_select('event', $whereclause, $whereparams);
    }

    return $users;
}

/**
 * Confirm that a user can be added to a session.
 *
 * @param class  $session Record from the facetoface_sessions table
 * @param object $context (optional) A context object (record from context table)
 * @return bool True if user can be added to session
 **/
function facetoface_session_has_capacity($session, $context = false) {

    if (empty($session)) {
        return false;
    }

    $signupcount = facetoface_get_num_attendees($session->id);
    if ($signupcount >= $session->capacity) {
        // if session is full, check if overbooking is allowed for this user
        if (!$context || !has_capability('mod/facetoface:overbook', $context)) {
            return false;
        }
    }

    return true;
}

/**
 * Print the details of a session
 *
 * @param object $session         Record from facetoface_sessions
 * @param boolean $showcapacity   Show the capacity (true) or only the seats available (false)
 * @param boolean $calendaroutput Whether the output should be formatted for a calendar event
 * @param boolean $return         Whether to return (true) the html or print it directly (true)
 * @param boolean $hidesignup     Hide any messages relating to signing up
 */
function facetoface_print_session($session, $showcapacity, $calendaroutput=false, $return=false, $hidesignup=false) {
    global $CFG, $DB;

    $table = new html_table();
    $table->summary = get_string('sessionsdetailstablesummary', 'facetoface');
    $table->attributes['class'] = 'generaltable f2fsession';
    $table->align = array('right', 'left');
    if ($calendaroutput) {
        $table->tablealign = 'left';
    }

    $customfields = facetoface_get_session_customfields();
    $customdata = $DB->get_records('facetoface_session_data', array('sessionid' => $session->id), '', 'fieldid, data');
    foreach ($customfields as $field) {
        $data = '';
        if (!empty($customdata[$field->id])) {
            if (CUSTOMFIELD_TYPE_MULTISELECT == $field->type) {
                $values = explode(CUSTOMFIELD_DELIMITER, format_string($customdata[$field->id]->data));
                $data = implode(html_writer::empty_tag('br'), $values);
            }
            else {
                $data = format_string($customdata[$field->id]->data);
            }
        }
        $table->data[] = array(str_replace(' ', '&nbsp;', format_string($field->name)), $data);
    }

    $strdatetime = str_replace(' ', '&nbsp;', get_string('sessiondatetime', 'facetoface'));
    if ($session->datetimeknown) {
        $html = '';
        foreach ($session->sessiondates as $date) {
            if (!empty($html)) {
                $html .= html_writer::empty_tag('br');
            }
            $timestart = userdate($date->timestart, get_string('strftimedatetime'));
            $timefinish = userdate($date->timefinish, get_string('strftimedatetime'));
            $html .= "$timestart &ndash; $timefinish";
        }
        $table->data[] = array($strdatetime, $html);
    }
    else {
        $table->data[] = array($strdatetime, html_writer::tag('i', get_string('wait-listed', 'facetoface')));
    }

    $signupcount = facetoface_get_num_attendees($session->id);
    $placesleft = $session->capacity - $signupcount;

    if ($showcapacity) {
        if ($session->allowoverbook) {
            $table->data[] = array(get_string('capacity', 'facetoface'), $session->capacity . ' ('.strtolower(get_string('allowoverbook', 'facetoface')).')');
        } else {
            $table->data[] = array(get_string('capacity', 'facetoface'), $session->capacity);
        }
    }
    elseif (!$calendaroutput) {
        $table->data[] = array(get_string('seatsavailable', 'facetoface'), max(0, $placesleft));
    }

    // Display requires approval notification
    $facetoface = $DB->get_record('facetoface', array('id' => $session->facetoface));

    if ($facetoface->approvalreqd) {
        $table->data[] = array('', get_string('sessionrequiresmanagerapproval', 'facetoface'));
    }

    // Display waitlist notification
    if (!$hidesignup && $session->allowoverbook && $placesleft < 1) {
        $table->data[] = array('', get_string('userwillbewaitlisted', 'facetoface'));
    }

    if (!empty($session->duration)) {
        $table->data[] = array(get_string('duration', 'facetoface'), format_duration($session->duration));
    }
    if (!empty($session->normalcost)) {
        $table->data[] = array(get_string('normalcost', 'facetoface'), format_cost($session->normalcost));
    }
    if (!empty($session->discountcost)) {
        $table->data[] = array(get_string('discountcost', 'facetoface'), format_cost($session->discountcost));
    }
    if (!empty($session->details)) {
        $details = clean_text($session->details, FORMAT_HTML);
        $table->data[] = array(get_string('details', 'facetoface'), $details);
    }

    // Display trainers
    $trainerroles = facetoface_get_trainer_roles();

    if ($trainerroles) {
        // Get trainers
        $trainers = facetoface_get_trainers($session->id);

        foreach ($trainerroles as $role => $rolename) {
            $rolename = $rolename->name;

            if (empty($trainers[$role])) {
                continue;
            }

            $trainer_names = array();
            foreach ($trainers[$role] as $trainer) {
                $trainer_url = new moodle_url('/user/view.php', array('id' => $trainer->id));
                $trainer_names[] = html_writer::link($trainer_url, fullname($trainer));
            }

            $table->data[] = array($rolename, implode(', ', $trainer_names));
        }
    }

    return html_writer::table($table, $return);
}

/**
 * Update the value of a customfield for the given session/notice.
 *
 * @param integer $fieldid    ID of a record from the facetoface_session_field table
 * @param string  $data       Value for that custom field
 * @param integer $otherid    ID of a record from the facetoface_(sessions|notice) table
 * @param string  $table      'session' or 'notice' (part of the table name)
 * @returns true if it succeeded, false otherwise
 */
function facetoface_save_customfield_value($fieldid, $data, $otherid, $table) {
    global $DB;

    $dbdata = null;
    if (is_array($data)) {
        $dbdata = trim(implode(CUSTOMFIELD_DELIMITER, $data), ';');
    }
    else {
        $dbdata = trim($data);
    }

    $newrecord = new stdClass();
    $newrecord->data = $dbdata;

    $fieldname = "{$table}id";
    if ($record = $DB->get_record("facetoface_{$table}_data", array('fieldid' => $fieldid, $fieldname => $otherid))) {
        if (empty($dbdata)) {
            // Clear out the existing value
            return $DB->delete_records("facetoface_{$table}_data", array('id' => $record->id));
        }

        $newrecord->id = $record->id;
        return $DB->update_record("facetoface_{$table}_data", $newrecord);
    }
    else {
        if (empty($dbdata)) {
            return true; // no need to store empty values
        }

        $newrecord->fieldid = $fieldid;
        $newrecord->$fieldname = $otherid;
        return $DB->insert_record("facetoface_{$table}_data", $newrecord);
    }
}

/**
 * Return the value of a customfield for the given session/notice.
 *
 * @param object  $field    A record from the facetoface_session_field table
 * @param integer $otherid  ID of a record from the facetoface_(sessions|notice) table
 * @param string  $table    'session' or 'notice' (part of the table name)
 * @returns string The data contained in this custom field (empty string if it doesn't exist)
 */
function facetoface_get_customfield_value($field, $otherid, $table) {
    global $DB;

    if ($record = $DB->get_record("facetoface_{$table}_data", array('fieldid' => $field->id, "{$table}id" => $otherid))) {
        if (!empty($record->data)) {
            if (CUSTOMFIELD_TYPE_MULTISELECT == $field->type) {
                return explode(CUSTOMFIELD_DELIMITER, $record->data);
            }
            return $record->data;
        }
    }
    return '';
}

/**
 * Return the values stored for all custom fields in the given session.
 *
 * @param integer $sessionid  ID of facetoface_sessions record
 * @returns array Indexed by field shortnames
 */
function facetoface_get_customfielddata($sessionid) {
    global $CFG, $DB;

    $sql = "SELECT f.shortname, d.data
              FROM {facetoface_session_field} f
              JOIN {facetoface_session_data} d ON f.id = d.fieldid
              WHERE d.sessionid = ?";

    $records = $DB->get_records_sql($sql, array($sessionid));

    return $records;
}

/**
 * Return a cached copy of all records in facetoface_session_field
 */
function facetoface_get_session_customfields() {
    global $DB;

    static $customfields = null;
    if (null == $customfields) {
        if (!$customfields = $DB->get_records('facetoface_session_field')) {
            $customfields = array();
        }
    }
    return $customfields;
}

/**
 * Display the list of custom fields in the site-wide settings page
 */
function facetoface_list_of_customfields() {
    global $CFG, $USER, $DB, $OUTPUT;

    if ($fields = $DB->get_records('facetoface_session_field', array(), 'name', 'id, name')) {
        $table = new html_table();
        $table->attributes['class'] = 'halfwidthtable';
        foreach ($fields as $field) {
            $fieldname = format_string($field->name);
            $edit_url = new moodle_url('/mod/facetoface/customfield.php', array('id' => $field->id));
            $editlink = $OUTPUT->action_icon($edit_url, new pix_icon('t/edit', get_string('edit')));
            $delete_url = new moodle_url('/mod/facetoface/customfield.php', array('id' => $field->id, 'd' => '1', 'sesskey' => $USER->sesskey));
            $deletelink = $OUTPUT->action_icon($delete_url, new pix_icon('t/delete', get_string('delete')));
            $table->data[] = array($fieldname, $editlink, $deletelink);
        }
        return html_writer::table($table, true);
    }

    return get_string('nocustomfields', 'facetoface');
}

function facetoface_update_trainers($sessionid, $form) {
    global $DB;

    // If we recieved bad data
    if (!is_array($form)) {
        return false;
    }

    // Load current trainers
    $old_trainers = facetoface_get_trainers($sessionid);

    $transaction = $DB->start_delegated_transaction();

    // Loop through form data and add any new trainers
    foreach ($form as $roleid => $trainers) {

        // Loop through trainers in this role
        foreach ($trainers as $trainer) {

            if (!$trainer) {
                continue;
            }

            // If the trainer doesn't exist already, create it
            if (!isset($old_trainers[$roleid][$trainer])) {

                $newtrainer = new stdClass();
                $newtrainer->userid = $trainer;
                $newtrainer->roleid = $roleid;
                $newtrainer->sessionid = $sessionid;

                if (!$DB->insert_record('facetoface_session_roles', $newtrainer)) {
                    print_error('error:couldnotaddtrainer', 'facetoface');
                    $transaction->force_transaction_rollback();
                    return false;
                }
            } else {
                unset($old_trainers[$roleid][$trainer]);
            }
        }
    }

    // Loop through what is left of old trainers, and remove
    // (as they have been deselected)
    if ($old_trainers) {
        foreach ($old_trainers as $roleid => $trainers) {
            // If no trainers left
            if (empty($trainers)) {
                continue;
            }

            // Delete any remaining trainers
            foreach ($trainers as $trainer) {
                if (!$DB->delete_records('facetoface_session_roles', array('sessionid' => $sessionid, 'roleid' => $roleid, 'userid' => $trainer->id))) {
                    print_error('error:couldnotdeletetrainer', 'facetoface');
                    $transaction->force_transaction_rollback();
                    return false;
                }
            }
        }
    }

    $transaction->allow_commit();

    return true;
}


/**
 * Return array of trainer roles configured for face-to-face
 *
 * @return  array
 */
function facetoface_get_trainer_roles() {
    global $CFG, $DB;

    // Check that roles have been selected
    if (empty($CFG->facetoface_session_roles)) {
        return false;
    }

    // Parse roles
    $cleanroles = clean_param($CFG->facetoface_session_roles, PARAM_SEQUENCE);
    list($rolesql, $params) = $DB->get_in_or_equal($cleanroles);

    // Load role names
    $rolenames = $DB->get_records_sql("
        SELECT
            r.id,
            r.name
        FROM
            {role} r
        WHERE
            r.id {$rolesql}
        AND r.id <> 0
    ", $params);

    // Return roles and names
    if (!$rolenames) {
        return array();
    }

    return $rolenames;
}


/**
 * Get all trainers associated with a session, optionally
 * restricted to a certain roleid
 *
 * If a roleid is not specified, will return a multi-dimensional
 * array keyed by roleids, with an array of the chosen roles
 * for each role
 *
 * @param   integer     $sessionid
 * @param   integer     $roleid (optional)
 * @return  array
 */
function facetoface_get_trainers($sessionid, $roleid = null) {
    global $CFG, $DB;

    $sql = "
        SELECT
            u.id,
            u.firstname,
            u.lastname,
            r.roleid
        FROM
            {facetoface_session_roles} r
        LEFT JOIN
            {user} u
         ON u.id = r.userid
        WHERE
            r.sessionid = ?
        ";
    $params = array($sessionid);

    if ($roleid) {
        $sql .= "AND r.roleid = ?";
        $params[] = $roleid;
    }

    $rs = $DB->get_recordset_sql($sql , $params);
    $return = array();
    foreach ($rs as $record) {
        // Create new array for this role
        if (!isset($return[$record->roleid])) {
            $return[$record->roleid] = array();
        }
        $return[$record->roleid][$record->id] = $record;
    }
    $rs->close();

    // If we are only after one roleid
    if ($roleid) {
        if (empty($return[$roleid])) {
            return false;
        }
        return $return[$roleid];
    }

    // If we are after all roles
    if (empty($return)) {
        return false;
    }

    return $return;
}

/**
 * Determines whether an activity requires the user to have a manager (either for
 * manager approval or to send notices to the manager)
 *
 * @param  object $facetoface A database fieldset object for the facetoface activity
 * @return boolean whether a person needs a manager to sign up for that activity
 */
function facetoface_manager_needed($facetoface) {
    return $facetoface->approvalreqd
        || $facetoface->confirmationinstrmngr
        || $facetoface->reminderinstrmngr
        || $facetoface->cancellationinstrmngr;
}

/**
 * Display the list of site notices in the site-wide settings page
 */
function facetoface_list_of_sitenotices() {
    global $CFG, $USER, $DB, $OUTPUT;

    if ($notices = $DB->get_records('facetoface_notice', array(), 'name', 'id, name')) {
        $table = new html_table();
        $table->width = '50%';
        $table->tablealign = 'left';
        $table->data = array();
        $table->size = array('100%');
        foreach ($notices as $notice) {
            $noticename = format_string($notice->name);
            $edit_url = new moodle_url('/mod/facetoface/sitenotice.php', array('id' => $notice->id));
            $editlink = $OUTPUT->action_icon($edit_url, new pix_icon('t/edit', get_string('edit')));
            $delete_url = new moodle_url('/mod/facetoface/sitenotice.php', array('id' => $notice->id, 'd' => '1', 'sesskey' => $USER->sesskey));
            $deletelink = $OUTPUT->action_icon($delete_url, new pix_icon('t/delete', get_string('delete')));
            $table->data[] = array($noticename, $editlink, $deletelink);
        }
        return html_writer::table($table, true);
    }

    return get_string('nositenotices', 'facetoface');
}

/**
 * Add formslib fields for all custom fields defined site-wide.
 * (used by the session add/edit page and the site notices)
 */
function facetoface_add_customfields_to_form(&$mform, $customfields, $alloptional=false)
{
    foreach ($customfields as $field) {
        $fieldname = "custom_$field->shortname";

        $options = array();
        if (!$field->required) {
            $options[''] = get_string('none');
        }
        foreach (explode(CUSTOMFIELD_DELIMITER, $field->possiblevalues) as $value) {
            $v = trim($value);
            if (!empty($v)) {
                $options[$v] = $v;
            }
        }

        switch ($field->type) {
        case CUSTOMFIELD_TYPE_TEXT:
            $mform->addElement('text', $fieldname, $field->name);
            break;
        case CUSTOMFIELD_TYPE_SELECT:
            $mform->addElement('select', $fieldname, $field->name, $options);
            break;
        case CUSTOMFIELD_TYPE_MULTISELECT:
            $select = &$mform->addElement('select', $fieldname, $field->name, $options);
            $select->setMultiple(true);
            break;
        default:
            error_log("facetoface: invalid field type for custom field ID $field->id");
            continue;
        }

        $mform->setType($fieldname, PARAM_TEXT);
        $mform->setDefault($fieldname, $field->defaultvalue);
        if ($field->required and !$alloptional) {
            $mform->addRule($fieldname, null, 'required', null, 'client');
        }
    }
}


/**
 * Get session cancellations
 *
 * @access  public
 * @param   integer $sessionid
 * @return  array
 */
function facetoface_get_cancellations($sessionid) {
    global $CFG, $DB;

    $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');
    $instatus = array(MDL_F2F_STATUS_BOOKED, MDL_F2F_STATUS_WAITLISTED, MDL_F2F_STATUS_REQUESTED);
    list($insql, $inparams) = $DB->get_in_or_equal($instatus);
    // Nasty SQL follows:
    // Load currently cancelled users,
    // include most recent booked/waitlisted time also
    $sql = "
            SELECT
                u.id,
                su.id AS signupid,
                u.firstname,
                u.lastname,
                MAX(ss.timecreated) AS timesignedup,
                c.timecreated AS timecancelled,
                " . $DB->sql_compare_text('c.note') . " AS cancelreason
            FROM
                {facetoface_signups} su
            JOIN
                {user} u
             ON u.id = su.userid
            JOIN
                {facetoface_signups_status} c
             ON su.id = c.signupid
            AND c.statuscode = ?
            AND c.superceded = 0
            LEFT JOIN
                {facetoface_signups_status} ss
             ON su.id = ss.signupid
             AND ss.statuscode $insql
            AND ss.superceded = 1
            WHERE
                su.sessionid = ?
            GROUP BY
                su.id,
                u.id,
                u.firstname,
                u.lastname,
                c.timecreated,
                " . $DB->sql_compare_text('c.note') . "
            ORDER BY
                {$fullname},
                c.timecreated
    ";
    $params = array_merge(array(MDL_F2F_STATUS_USER_CANCELLED), $inparams);
    $params[] = $sessionid;
    return $DB->get_records_sql($sql, $params);
}


/**
 * Get session unapproved requests
 *
 * @access  public
 * @param   integer $sessionid
 * @return  array
 */
function facetoface_get_requests($sessionid) {
    global $CFG, $DB;

    $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');

    $params = array($sessionid, MDL_F2F_STATUS_REQUESTED);

    $sql = "SELECT u.id, su.id AS signupid, u.firstname, u.lastname,
                   ss.timecreated AS timerequested
              FROM {facetoface_signups} su
              JOIN {facetoface_signups_status} ss ON su.id=ss.signupid
              JOIN {user} u ON u.id = su.userid
             WHERE su.sessionid = ? AND ss.superceded != 1 AND ss.statuscode = ?
          ORDER BY $fullname, ss.timecreated";

    return $DB->get_records_sql($sql, $params);
}


/**
 * Get session declined requests
 *
 * @access  public
 * @param   integer $sessionid
 * @return  array
 */
function facetoface_get_declines($sessionid) {
    global $CFG, $DB;

    $fullname = $DB->sql_fullname('u.firstname', 'u.lastname');

    $params = array($sessionid, MDL_F2F_STATUS_DECLINED);

    $sql = "SELECT u.id, su.id AS signupid, u.firstname, u.lastname,
                   ss.timecreated AS timerequested
              FROM {facetoface_signups} su
              JOIN {facetoface_signups_status} ss ON su.id=ss.signupid
              JOIN {user} u ON u.id = su.userid
             WHERE su.sessionid = ? AND ss.superceded != 1 AND ss.statuscode = ?
          ORDER BY $fullname, ss.timecreated";
    return $DB->get_records_sql($sql, $params);
}


/**
 * Returns all other caps used in module
 * @return array
 */
function facetoface_get_extra_capabilities() {
    return array('moodle/site:viewfullnames');
}


/**
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed True if module supports feature, null if doesn't know
 */
function facetoface_supports($feature) {
    switch($feature) {
        case FEATURE_BACKUP_MOODLE2:          return true;
        case FEATURE_GRADE_HAS_GRADE:         return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS: return true;

        default: return null;
    }
}

/**
* facetoface assignment candidates
*/
class facetoface_candidate_selector extends user_selector_base {
    protected $sessionid;

    public function __construct($name, $options) {
        $this->sessionid = $options['sessionid'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     * @param <type> $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        /// All non-signed up system users
        list($wherecondition, $params) = $this->search_sql($search, '{user}');

        $fields      = 'SELECT id, firstname, lastname, email';
        $countfields = 'SELECT COUNT(1)';
        $sql = "
                  FROM {user}
                 WHERE $wherecondition
                   AND id NOT IN
                       (
                       SELECT u.id
                         FROM {facetoface_signups} s
                         JOIN {facetoface_signups_status} ss ON s.id = ss.signupid
                         JOIN {user} u ON u.id=s.userid
                        WHERE s.sessionid = :sessid
                          AND ss.statuscode >= :statusbooked
                          AND ss.superceded = 0
                       )
               ";
        $order = " ORDER BY lastname ASC, firstname ASC";
        $params = array_merge($params, array('sessid' => $this->sessionid, 'statusbooked' => MDL_F2F_STATUS_BOOKED));

        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > 100) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($availableusers)) {
            return array();
        }

        $groupname = get_string('potentialusers', 'role', count($availableusers));

        return array($groupname => $availableusers);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['sessionid'] = $this->sessionid;
        $options['file'] = 'mod/facetoface/lib.php';
        return $options;
    }
}

/**
 * facetoface assignment candidates
 */
class facetoface_existing_selector extends user_selector_base {
    protected $sessionid;

    public function __construct($name, $options) {
        $this->sessionid = $options['sessionid'];
        parent::__construct($name, $options);
    }

    /**
     * Candidate users
     * @param <type> $search
     * @return array
     */
    public function find_users($search) {
        global $DB;
        //by default wherecondition retrieves all users except the deleted, not confirmed and guest
        list($wherecondition, $whereparams) = $this->search_sql($search, 'u');

        $fields = 'SELECT
                        u.id,
                        su.id AS submissionid,
                        u.firstname,
                        u.lastname,
                        u.email,
                        s.discountcost,
                        su.discountcode,
                        su.notificationtype,
                        f.id AS facetofaceid,
                        f.course,
                        ss.grade,
                        ss.statuscode,
                        sign.timecreated';
        $countfields = 'SELECT COUNT(1)';
        $sql = "
            FROM
                {facetoface} f
            JOIN
                {facetoface_sessions} s
             ON s.facetoface = f.id
            JOIN
                {facetoface_signups} su
             ON s.id = su.sessionid
            JOIN
                {facetoface_signups_status} ss
             ON su.id = ss.signupid
            LEFT JOIN
                (
                SELECT
                    ss.signupid,
                    MAX(ss.timecreated) AS timecreated
                FROM
                    {facetoface_signups_status} ss
                INNER JOIN
                    {facetoface_signups} s
                 ON s.id = ss.signupid
                AND s.sessionid = :sessid1
                WHERE
                    ss.statuscode IN (:statusbooked, :statuswaitlisted)
                GROUP BY
                    ss.signupid
                ) sign
             ON su.id = sign.signupid
            JOIN
                {user} u
             ON u.id = su.userid
            WHERE
                $wherecondition
            AND s.id = :sessid2
            AND ss.superceded != 1
            AND ss.statuscode >= :statusapproved
        ";
        $order = " ORDER BY sign.timecreated ASC, ss.timecreated ASC";
        $params = array ('sessid1' => $this->sessionid, 'statusbooked' => MDL_F2F_STATUS_BOOKED, 'statuswaitlisted' => MDL_F2F_STATUS_WAITLISTED);
        $params = array_merge($params, $whereparams);
        $params['sessid2'] = $this->sessionid;
        $params['statusapproved'] = MDL_F2F_STATUS_APPROVED;
        if (!$this->is_validating()) {
            $potentialmemberscount = $DB->count_records_sql($countfields . $sql, $params);
            if ($potentialmemberscount > 100) {
                return $this->too_many_results($search, $potentialmemberscount);
            }
        }

        $availableusers = $DB->get_records_sql($fields . $sql . $order, $params);

        if (empty($availableusers)) {
            return array();
        }

        $groupname = get_string('existingusers', 'role', count($availableusers));
        return array($groupname => $availableusers);
    }

    protected function get_options() {
        $options = parent::get_options();
        $options['sessionid'] = $this->sessionid;
        $options['file'] = 'mod/facetoface/lib.php';
        return $options;
    }
}


/**
 * Send Totara task or alert
 *
 * @param   object  $facetoface Facetoface instance
 * @param   object  $session    Session instance
 * @param   int     $userid     ID of user requesting booking
 * @param   int     $nottype    notification type
 * @return  string  Error string, empty on success
 */
function facetoface_send_totaramessage($facetoface, $session, $userid, $nottype) {
    global $CFG, $USER, $DB;

    require_once($CFG->dirroot.'/totara/message/messagelib.php');
    $string_manager = get_string_manager();
    $newevent = new stdClass();
    $user = $DB->get_record('user', array('id' => $userid));
    $userfrom_link = $CFG->wwwroot.'/user/view.php?id='.$userid;
    $fromname = fullname($user);
    $usermsg = html_writer::link($userfrom_link, $fromname, array('title' => $fromname));
    $newevent->userto           = $user;
    //stop user from emailing themselves, use support instead
    $newevent->userfrom         = ($USER->id == $user->id) ? generate_email_supportuser() : $USER;
    $url = new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id));
    switch ($nottype) {
        case MDL_F2F_STATUS_BOOKED:
            $newevent->fullmessage      = facetoface_email_substitutions(
                                                        $facetoface->confirmationmessage,
                                                        $facetoface->name,
                                                        $facetoface->reminderperiod,
                                                        $user,
                                                        $session,
                                                        $session->id
                                                );
            $newevent->subject          = $string_manager->get_string('bookedforsession', 'facetoface', $facetoface->name, $user->lang);
            $newevent->icon             = 'facetoface-add';
            $newevent->sendemail        = TOTARA_MSG_EMAIL_NO;
            $newevent->msgtype          = TOTARA_MSG_TYPE_FACE2FACE;
            $newevent->urgency          = TOTARA_MSG_URGENCY_NORMAL;
            $newevent->contexturl       = $url;
            tm_alert_send($newevent);
            break;

        case MDL_F2F_STATUS_WAITLISTED:
            $newevent->fullmessage      = facetoface_email_substitutions(
                                                        $facetoface->waitlistedmessage,
                                                        $facetoface->name,
                                                        $facetoface->reminderperiod,
                                                        $user,
                                                        $session,
                                                        $session->id
                                                );
            $newevent->subject          = $string_manager->get_string('waitlistedforsession' ,'facetoface', $facetoface->name, $user->lang);
            $newevent->icon             = 'facetoface-regular';
            $newevent->sendemail        = TOTARA_MSG_EMAIL_NO;
            $newevent->msgtype          = TOTARA_MSG_TYPE_FACE2FACE;
            $newevent->urgency          = TOTARA_MSG_URGENCY_NORMAL;
            $newevent->contexturl       = $url;
            tm_alert_send($newevent);
            break;

        case MDL_F2F_STATUS_USER_CANCELLED:
            $newevent->subject          = $string_manager->get_string('cancelledforsession', 'facetoface', $facetoface->name, $user->lang);
            $newevent->fullmessage      = $newevent->subject;
            $newevent->icon             = 'facetoface-remove';
            $newevent->sendemail        = TOTARA_MSG_EMAIL_NO;
            $newevent->msgtype          = TOTARA_MSG_TYPE_FACE2FACE;
            $newevent->urgency          = TOTARA_MSG_URGENCY_NORMAL;
            tm_alert_send($newevent);
            $managerid = facetoface_get_manager($userid);
            if ($managerid !== false) {
                $userto = $DB->get_record('user', array('id' => $managerid));
                $newevent->userto           = $userto;
                $subjectinfo = new stdClass();
                $subjectinfo->usermsg = $usermsg;
                $subjectinfo->url = html_writer::link($url, $facetoface->name);
                $newevent->subject          = $string_manager->get_string('cancelusersession', 'facetoface', $subjectinfo, $userto->lang);
                $newevent->contexturl       = $url;
                $newevent->fullmessage      = $newevent->subject;
                tm_alert_send($newevent);
            }
            break;

        case MDL_F2F_STATUS_REQUESTED:
            $managerid = facetoface_get_manager($userid);
            $user = $DB->get_record('user', array('id' => $userid));
            if ($managerid !== false) {
                $manager = $DB->get_record('user', array('id' => $managerid));
                $newevent->userto           = $manager;
                //ensure the message is actually coming from $user, default to support
                $newevent->userfrom         = ($USER->id == $user->id) ? $user : generate_email_supportuser();
                $newevent->fullmessage      = facetoface_email_substitutions(
                                                        $facetoface->requestinstrmngr,
                                                        $facetoface->name,
                                                        $facetoface->reminderperiod,
                                                        $user,
                                                        $session,
                                                        $session->id
                                                );
                $newevent->fullmessagehtml  = nl2br(facetoface_email_substitutions(
                                                        $facetoface->requestinstrmngr,
                                                        $facetoface->name,
                                                        $facetoface->reminderperiod,
                                                        $user,
                                                        $session,
                                                        $session->id,
                                                        false
                                                ));
                $newevent->contexturl = $CFG->wwwroot . '/mod/facetoface/attendees.php?s=' . $session->id . '#unapproved';
                $subjectinfo = new stdClass();
                $subjectinfo->usermsg = $usermsg;
                $subjectinfo->url = html_writer::link(new moodle_url('/mod/facetoface/attendees.php', array('s' => $session->id)), $facetoface->name);
                $newevent->subject          = $string_manager->get_string('requestuserattendsession', 'facetoface', $subjectinfo, $manager->lang);
                // do the facetoface workflow event
                $onaccept = new stdClass();
                $onaccept->action = 'facetoface';
                $onaccept->text = $string_manager->get_string('approveinstruction', 'facetoface', null, $manager->lang);
                $onaccept->data = array('userid' => $userid, 'session' => $session, 'facetoface' => $facetoface);
                $newevent->onaccept = $onaccept;
                $onreject = new stdClass();
                $onreject->action = 'facetoface';
                $onreject->text = $string_manager->get_string('rejectinstruction', 'facetoface', null, $manager->lang);
                $onreject->data = array('userid' => $userid, 'session' => $session, 'facetoface' => $facetoface);
                $newevent->onreject = $onreject;
                $newevent->sendemail        = TOTARA_MSG_EMAIL_NO;
                tm_task_send($newevent);
                $newevent = new stdClass();
                $newevent->userto           = $user;
                //stop user from emailing themselves, use support instead
                $newevent->userfrom         = generate_email_supportuser();
                $newevent->subject          = $string_manager->get_string('requestattendsessionsent', 'facetoface', html_writer::link(new moodle_url('/mod/facetoface/view.php', array('f' => $facetoface->id)), $facetoface->name), $user->lang);
                $newevent->fullmessage      = $newevent->subject;
                $newevent->icon             = 'facetoface-request';
                $newevent->sendemail        = TOTARA_MSG_EMAIL_NO;
                $newevent->msgtype          = TOTARA_MSG_TYPE_FACE2FACE;
                $newevent->urgency          = TOTARA_MSG_URGENCY_NORMAL;
                tm_alert_send($newevent);
            }
            break;
    }

    return true;
}


/**
 * Return the id of the user's manager if it is
 * defined. Otherwise return false.
 *
 * @param integer $userid User ID of the staff member
 */
function facetoface_get_manager($userid) {
    global $DB;

    $sql = "SELECT managerid
        FROM {pos_assignment} pa
        WHERE pa.userid = ? AND pa.type = 1"; // just use primary position for now
    $res = $DB->get_record_sql($sql, array($userid));
    if ($res && isset($res->managerid)) {
        return $res->managerid;
    } else {
        return false; // No manager set
    }
}


/**
 * Event that is triggered when a user is deleted.
 *
 * Cancels a user from any future sessions when they are deleted
 * this make sure deleted users aren't using space is sessions when
 * there is limited capacity.
 *
 * @param object $user
 *
 */
function facetoface_eventhandler_user_deleted($user) {
    global $DB;

    if ($signups = $DB->get_records('facetoface_signups', array('userid' => $user->id))) {
        foreach ($signups as $signup) {
            $session = facetoface_get_session($signup->sessionid);
            // using $null, null fails because of passing by reference
            facetoface_user_cancel($session, $user->id, false, $null, get_string('userdeletedcancel', 'facetoface'));
        }
    }
    return true;
}


/**
 * Called when displaying facetoface Task to check
 * capacity of the session.
 *
 * @param array Message data for a facetoface task
 * @return bool True if there is capacity in the session
 */
function facetoface_task_check_capacity($data) {
    $session = $data['session'];
    // Get session from database in case it has been updated
    $session = facetoface_get_session($session->id);
    if (!$session) {
        return false;
    }
    $facetoface = $data['facetoface'];

    if (!$cm = get_coursemodule_from_instance('facetoface', $facetoface->id, $facetoface->course)) {
        print_error('error:incorrectcoursemodule', 'facetoface');
    }
    $contextmodule = context_module::instance($cm->id);

    return (facetoface_session_has_capacity($session, $contextmodule) || $session->allowoverbook);
}