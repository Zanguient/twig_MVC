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
 * @author Eugene Venter <eugene@catalyst.net.nz>
 * @package totara
 * @subpackage course
 */

require_once(dirname(dirname(dirname(dirname(__FILE__)))).'/config.php');
require_once($CFG->dirroot .'/cohort/lib.php');
require_once($CFG->dirroot .'/lib/pear/HTML/AJAX/JSON.php');

require_login();
$context = context_system::instance();
require_capability('moodle/cohort:manage', $context);
$PAGE->set_context($context);

$itemids = required_param('itemid', PARAM_SEQUENCE);
$itemids = explode(',', $itemids);

$ccohort = new totara_cohort_course_cohorts();

$items = array();
$rows = array();
$users = 0;
foreach ($itemids as $itemid) {
    $item = $ccohort->get_item(intval($itemid));
    $users += $ccohort->user_affected_count($item);

    $items[] = $item;
    $row = $ccohort->build_row($item);

    $rowhtml = html_writer::start_tag('tr');
    $colcount = 0;
    foreach ($row as $cell) {
        $rowhtml .= html_writer::tag('td', $cell, array('class' => 'col'.$colcount));
        $colcount++;
    }
    $rowhtml .= html_writer::end_tag('tr');

    $rows[] = $rowhtml;
}

// Build the html to display in the confirmation dialog
$num = count($items);
$itemnames = '';
if ($num == 1) {
    $itemnames .= '"'.$items[0]->fullname.'"';
} else {
    for ($i = 0; $i < $num; $i++) {
        // If not last item
        if ($i == 0) {
            $itemnames .= ' "'.$items[$i]->fullname.'"';
        } else if ($i != $num-1) {
            $itemnames .= ', "'.$items[$i]->fullname.'"';
        } else {
            $itemnames .= ' and "'.$items[$i]->fullname.'"';
        }
    }
}
$a = new stdClass();
$a->itemnames = $itemnames;
$a->affectedusers = $users;
$html = get_string('youhaveadded', 'totara_cohort', $a);

$data = array(
'html'      => $html,
'rows'      => $rows
);

echo json_encode($data);
