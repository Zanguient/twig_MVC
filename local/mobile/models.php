<?php

namespace mobile;

defined('MOODLE_INTERNAL') || die;

class enrolled_courses_model {

    /**
     * @var integer
     */
    protected $_userid;

    /**
     * @var object
     */
    protected $_courses;

    /**
     * c'tor
     * @param integer $userid
     */
    public function __construct($userid) {
        $this->_userid = $userid;
        $this->_load();
    }

    /**
     * gets courses
     * @return object
     */
    public function getCourses() {
        return $this->_courses;
    }

    /**
     * loads data
     */
    protected function _load() {
        global $CFG;

        // get connection
        $conn = \external\doctrine::get_connection();

        // get student roleid
        $roleid = $conn->fetchColumn(\external\doctrine::mdlize('SELECT id FROM {role} WHERE shortname = :shortname'), array('shortname' => 'student'));

        // restrict to only enrolled courses
        $sql = "SELECT c.id, c.fullname, c.summary, c.startdate FROM {course} c" .
            " INNER JOIN {context} ct ON ct.contextlevel = :contextlevel AND ct.instanceid = c.id" .
            " INNER JOIN {role_assignments} ra ON ra.contextid = ct.id AND ra.userid = :userid AND ra.roleid = :roleid" .
            " WHERE c.category > :category" .
            " ORDER BY c.fullname";

        // execute statement and create a collection of courses
        $stmt = $conn->prepare(\external\doctrine::mdlize($sql));
        $stmt->bindValue('category', 0, 'integer');
        $stmt->bindValue('contextlevel', CONTEXT_COURSE, 'integer');
        $stmt->bindValue('userid', $this->_userid, 'integer');
        $stmt->bindValue('roleid', $roleid, 'integer');
        $stmt->execute();
        $this->_courses = array();
        while ($course = $stmt->fetch()) {
            $this->_courses[] = array(
                'id' => $course['id'],
                'fullname' => $course['fullname'],
                'summary' => $course['summary'],
                'startdate' => $course['startdate'],
                'link' => "{$CFG->wwwroot}/local/mobile/?view=course&id={$course['id']}",
            );
        }
    }

}

class course_model {

    /**
     * @var integer
     */
    protected $_id;

    /**
     * @var integer
     */
    protected $_userid;

    /**
     * @var object
     */
    protected $_course;

    /**
     * @var array
     */
    protected $_activities;

    /**
     * c'tor
     * @param integer $id
     * @param integer $userid
     */
    public function __construct($id, $userid) {
        $this->_id = $id;
        $this->_userid = $userid;
        $this->_load();
    }

    /**
     * gets a course
     * @return object
     */
    public function getCourse() {
        return $this->_course;
    }

    /**
     * gets a course's activities
     */
    public function getActivities() {
        return $this->_activities;
    }

    /**
     * loads data
     */
    protected function _load() {
        global $CFG;

        // get connection
        $conn = \external\doctrine::get_connection();

        // load course
        $this->_course = $conn->fetchAssoc(\external\doctrine::mdlize('SELECT * FROM {course} WHERE id = :courseid'), array('courseid' => $this->_id));
        if ($this->_course === false) {
            throw new \moodle_exception('invalidcourseid');
        }

        // load modules
        $sql = 'SELECT id, name FROM {modules} WHERE visible = :visible';
        $stmt = $conn->prepare(\external\doctrine::mdlize($sql));
        $stmt->bindValue('visible', 1, 'integer');
        $stmt->execute();
        $modulemap = array();
        while ($module = $stmt->fetch()) {
            $modulemap[$module['id']] = $module['name'];
        }

        // create map of completion statuses to codes which point to a translatable string
        require_once "{$CFG->libdir}/completionlib.php";
        $map = array(
            COMPLETION_INCOMPLETE => 'n',
            COMPLETION_COMPLETE => 'y',
            COMPLETION_COMPLETE_PASS => 'pass',
            COMPLETION_COMPLETE_FAIL => 'fail',
            COMPLETION_COMPLETE_RPL => 'y',
        );

        // get (visible) activities in the course
        $sql = "SELECT cm.id, cm.module, cm.instance, cmc.completionstate" .
        " FROM {course_modules} cm" .
        " LEFT JOIN {course_modules_completion} cmc ON cmc.coursemoduleid = cm.id AND cmc.userid = :userid" .
        " WHERE cm.course = :courseid AND cm.visible = :visible";
        $stmt = $conn->prepare(\external\doctrine::mdlize($sql));
        $stmt->bindValue('courseid', $this->_id, 'integer');
        $stmt->bindValue('visible', 1, 'integer');
        $stmt->bindValue('userid', $this->_userid, 'integer');
        $stmt->execute();
        $this->_activities = array();
        while ($activity = $stmt->fetch()) {
            $instance = $conn->fetchAssoc(\external\doctrine::mdlize('SELECT id, name, intro FROM {' . $modulemap[$activity['module']] . '} WHERE id = :id'), array('id' => $activity['instance']));
            $link = "{$CFG->wwwroot}/mod/{$modulemap[$activity['module']]}/view.php?id={$activity['id']}";
            $complete = array_key_exists($activity['completionstate'], $map) ? $map[$activity['completionstate']] : 'n';
            $this->_activities[] = array(
                'cmid' => $activity['id'],
                'name' => $instance['name'],
                'intro' => $instance['intro'],
                'complete' => "completion-{$complete}",
                'link' => $link,
            );
        }
    }

}
