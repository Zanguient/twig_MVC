<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2010-2012 Totara Learning Solutions LTD
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
 * @author Jonathan Newman <jonathan.newman@catalyst.net.nz>
 * @author Ciaran Irvine <ciaran.irvine@totaralms.com>
 * @package totara
 * @subpackage totara_core
 */

/**
 * Local db upgrades for Totara Core
 */

require_once($CFG->dirroot.'/totara/core/db/utils.php');


/**
 * Local database upgrade script
 *
 * @param   integer $oldversion Current (pre-upgrade) local db version timestamp
 * @return  boolean $result
 */
function xmldb_totara_core_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    if ($oldversion < 2012052802) {
        // add the archetype field to the staff manager role
        $sql = 'UPDATE {role} SET archetype = ? WHERE shortname = ?';
        $DB->execute($sql, array('staffmanager', 'staffmanager'));

        // rename the moodle 'manager' fullname to "Site Manager" to make it
        // distinct from the totara "Staff Manager"
        if ($managerroleid = $DB->get_field('role', 'id', array('shortname' => 'manager', 'name' => get_string('manager', 'role')))) {
            $todb = new stdClass();
            $todb->id = $managerroleid;
            $todb->name = get_string('sitemanager', 'totara_core');
            $DB->update_record('role', $todb);
        }

        totara_upgrade_mod_savepoint(true, 2012052802, 'totara_core');
    }

    if ($oldversion < 2012061200) {
        // Add RPL column to course_completions table
        $table = new xmldb_table('course_completions');

        // Define field rpl to be added to course_completions
        $field = new xmldb_field('rpl', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'reaggregate');

        // Conditionally launch add field rpl
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Add RPL column to course_completion_crit_compl table
        $table = new xmldb_table('course_completion_crit_compl');

        // Define field rpl to be added to course_completion_crit_compl
        $field = new xmldb_field('rpl', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'unenroled');

        // Conditionally launch add field rpl
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        totara_upgrade_mod_savepoint(true, 2012061200, 'totara_core');
    }

    /*
     * Move Totara 1.1 dashlets to Totara 2.x mymoodle
     */
    if ($oldversion < 2012062900) {
        // get the id of the default mylearning and myteam quicklinks block instances
        $quicklinks_defaultinstances = $DB->get_fieldset_sql("
            SELECT bi.id
            FROM {dashb_instance_dashlet} did
            INNER JOIN {dashb_instance} di ON did.dashb_instance_id = di.id
            INNER JOIN {dashb} d on d.id = di.dashb_id
            INNER JOIN {block_instances} bi on did.block_instance_id = bi.id
            WHERE di.userid = 0
                AND d.shortname IN ('mylearning', 'myteam')
                AND bi.blockname = 'totara_quicklinks'
        ");
        // first get all default quicklinks
        if (!empty($quicklinks_defaultinstances)) {
            list($insql, $inparams) = $DB->get_in_or_equal($quicklinks_defaultinstances);
            $alllinks = $DB->get_records_select('block_quicklinks', "block_instance_id $insql", $inparams, 'displaypos ASC');
        } else {
            $alllinks = array();
        }
        // now loop through and remove duplicates with same url and title
        $links = array();
        foreach ($alllinks as $l) {
            $key = $l->url . '-' . $l->title;
            $links[$key] = $l;
        }

        // Change default my_pages for My Moodle
        if ($mypageid = $DB->get_field_sql('SELECT id FROM {my_pages} WHERE userid IS null AND private = 1')) {

            $blockinstance = new stdClass;
            $blockinstance->parentcontextid = SYSCONTEXTID;
            $blockinstance->showinsubcontexts = 0;
            $blockinstance->pagetypepattern = 'my-index';
            $blockinstance->subpagepattern = $mypageid;
            $blockinstance->configdata = '';
            $blockinstance->defaultweight = 0;

            // List of Totara blocks for default pages
            $defaultblocks = array('totara_quicklinks', 'totara_tasks', 'totara_alerts', 'totara_stats');

            // Install new Totara blocks to default mymoodle page
            foreach ($defaultblocks as $block) {
                // put tasks and alerts in the middle, others on the side
                if ($block == 'totara_tasks' || $block == 'totara_alerts' || $block == 'totara_recent_learning') {
                    $blockinstance->defaultregion = 'content';
                } else {
                    $blockinstance->defaultregion = 'side-post';
                }
                $blockinstance->blockname = $block;
                $blockinstance->id = $DB->insert_record('block_instances', $blockinstance);

                // Add default links to each quicklinks instance
                if ($block == 'totara_quicklinks') {
                    // Add default content for quicklinks block
                    $pos = 0;
                    foreach ($links as $ql) {
                        $ql->userid = 0;
                        $ql->block_instance_id = $blockinstance->id;
                        $ql->displaypos = $pos;
                        $DB->update_record('block_quicklinks', $ql);
                        $pos++;
                    }
                }
            }

        }

        // delete old references in block_instances that refer to old default dashboard blocks
        $old_defaultinstance_ids = $DB->get_fieldset_sql("
            SELECT bi.id
            FROM {dashb_instance_dashlet} did
            INNER JOIN {dashb_instance} di ON did.dashb_instance_id = di.id
            INNER JOIN {dashb} d on d.id = di.dashb_id
            INNER JOIN {block_instances} bi on did.block_instance_id = bi.id
            WHERE di.userid = 0
        ");
        foreach ($old_defaultinstance_ids as $instanceid) {
            $DB->delete_records('block_instances', array('id' => $instanceid));
        }

        // delete the old default quicklink block instances to avoid more duplicates
        foreach ($quicklinks_defaultinstances as $instanceid) {
            $DB->delete_records('block_quicklinks', array('block_instance_id' => $instanceid));
        }

        // get the new default quicklinks, for user pages
        $defaultquicklinks = $DB->get_records('block_quicklinks', array('userid' => 0));

        // get the default page for mymoodle
        $systempage = $DB->get_record('my_pages', array('userid' => null, 'private' => 1));

        // get system context
        $systemcontext = context_system::instance();

        // get default block instances
        $blockinstances = $DB->get_records('block_instances', array('parentcontextid' => $systemcontext->id,
                    'pagetypepattern' => 'my-index',
                    'subpagepattern' => "$systempage->id"));

        // get all totara dashboard users (except deleted users)
        $sql = 'SELECT DISTINCT userid from {dashb_instance} dbi JOIN {user} u ON dbi.userid = u.id WHERE u.deleted = 0';
        $dashusers = $DB->get_records_sql($sql);

        // set up per-user mymoodle pages
        foreach ($dashusers as $user) {
            // Clone the default mymoodle page
            $page = clone($systempage);
            unset($page->id);
            $page->userid = $user->userid;

            // Add a mymoodle page for each dashboard user
            if (!($DB->record_exists('my_pages', array('userid' => $user->userid)))) {
                $page->id = $DB->insert_record('my_pages', $page);

                $usercontext = context_user::instance($user->userid);

                // Get dashboard block instances
                $sql = "SELECT bi.id,bi.blockname
                    FROM {dashb_instance_dashlet} did
                    INNER JOIN {block_instances} bi
                    ON did.block_instance_id = bi.id
                    INNER JOIN {dashb_instance} di
                    ON di.id = did.dashb_instance_id
                    WHERE di.userid = ?";

                $dashletinstances = $DB->get_records_sql($sql, array($user->userid));
                $userblocks = array();

                // Move per-user dashlets to mymoodle blocks
                foreach ($dashletinstances as $instance) {
                    $instance->parentcontextid = $usercontext->id;
                    $instance->subpagepattern =  $page->id;
                    $instance->pagetypepattern = 'my-index';

                    // put tasks and alerts in the middle, others on the side
                    if ($instance->blockname == 'totara_alerts' || $instance->blockname == 'totara_tasks' || $instance->blockname == 'totara_recent_learning') {
                        $instance->defaultregion = 'content';
                    } else {
                        $instance->defaultregion = 'side-post';
                    }

                    // check if user already has this block
                    if (!(in_array($instance->blockname, $userblocks))) {
                        // if not migrate it across
                        $DB->update_record('block_instances', $instance);
                    } else {
                        // delete any duplicates to avoid leaving stray records in block instance table
                        $DB->delete_records('block_instances', array('id' => $instance->id));
                        if ($instance->blockname == 'totara_quicklinks') {
                            $DB->delete_records('block_quicklinks', array('block_instance_id' => $instance->id));
                        }
                    }
                    $userblocks[] = $instance->blockname;
                }

                // Add default blocks to each users page.
                foreach ($blockinstances as $instance) {
                    // check if user already has this block
                    if (!(in_array($instance->blockname, $userblocks))) {
                        unset($instance->id);
                        $instance->parentcontextid = $usercontext->id;
                        $instance->subpagepattern = $page->id;
                        // put tasks and alerts in the middle, others on the side
                        if ($instance->blockname == 'totara_alerts' || $instance->blockname == 'totara_tasks' || $instance->blockname == 'totara_recent_learning') {
                            $instance->defaultregion = 'content';
                        } else {
                            $instance->defaultregion = 'side-post';
                        }
                        $instance->id = $DB->insert_record('block_instances', $instance);

                        // Add default links to each quicklinks instance
                        if ($instance->blockname == 'totara_quicklinks') {
                            // Add default content for quicklinks block
                            foreach ($defaultquicklinks as $ql) {
                                unset($ql->id);
                                $ql->block_instance_id = $instance->id;
                                $ql->userid = $user->userid;
                                $DB->insert_record('block_quicklinks', $ql);
                            }
                        }
                    }
                }
            }
        }

        // Clean up - delete the obsolete dashboard tables
        $dbman = $DB->get_manager();

        $tables = array('dashb', 'dashb_instance', 'dashb_instance_dashlet');
        foreach ($tables as $tablename) {
            $table = new xmldb_table($tablename);
            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }

        // delete old default dashboard blocks
        $DB->delete_records('block_instances', array('pagetypepattern' => 'totara_dashboard'));

        totara_upgrade_mod_savepoint(true, 2012062900, 'totara_core');
    }


    if ($oldversion < 2012080100) {
        // readd totara specific course completion changes for anyone
        // who has already upgraded from moodle 2.2.2+
        totara_readd_course_completion_changes();
        totara_upgrade_mod_savepoint(true, 2012080100, 'totara_core');
    }

    if ($oldversion < 2012080101) {
        // remove OAuth plugin
        // Google fusion export will use repository/gdrive integration instead
        uninstall_plugin('totara', 'oauth');
        totara_upgrade_mod_savepoint(true, 2012080101, 'totara_core');
    }

    if ($oldversion < 2012081300) {
        //turn off forceunique for any filepicker totara customfields
        $tables = array('course', 'pos_type', 'org_type', 'comp_type');
        foreach ($tables as $table) {
            $DB->execute("UPDATE {{$table}_info_field} SET forceunique = ? WHERE datatype = ?", array(0, 'file'));
        }
        totara_upgrade_mod_savepoint(true, 2012081300, 'totara_core');
    }

    if ($oldversion < 2012090500) {
        // backport of SCORM directview patch MDL-33755 from 2.3
        // we removed the directview column in upgrade_pre20 but we may have orphaned data that needs fixed
        $DB->execute("UPDATE {scorm} SET popup = ?, skipview = ? WHERE popup = ?", array(1, 2, 2));
        totara_upgrade_mod_savepoint(true, 2012090500, 'totara_core');
    }

    if ($oldversion < 2012102400) {
        //fix broken stats for course completions
        require_once($CFG->dirroot.'/lib/completion/completion_completion.php');
        $completions = $DB->get_recordset('course_completions', array('status' => COMPLETION_STATUS_COMPLETE));
        foreach ($completions as $completion) {
            $data = array();
            $data['userid'] = $completion->userid;
            $data['eventtype'] = STATS_EVENT_COURSE_COMPLETE;
            $data['data2'] = $completion->course;
            if (!$DB->record_exists('block_totara_stats', $data)) {
                totara_stats_add_event($completion->timecompleted, $data['userid'], STATS_EVENT_COURSE_COMPLETE, '', $data['data2']);
            }
        }
        $completions->close();
        totara_upgrade_mod_savepoint(true, 2012102400, 'totara_core');
    }
    return true;
}
