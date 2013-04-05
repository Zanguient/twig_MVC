<?php
/*if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');

}*/
require_once(dirname(__FILE__) . '/IObservable.php');
require_login();
/**
 * This models our Course 
 */
class CourseModel implements IObservable{
	
    private $observers = array();
    //private $id = -1;
    private $userid = -1;
    private $courses = array();
    private $db = null;


   
   /**
	* Constructor
	**/
	public function __construct($userid, $db){
        //$this->id = $id;
        $this->userid = $userid;
        $this->db = $db;
	}
    
    /**
     *
     * @return array 
     */
    public function getCourses() {
        return $this->courses;
    }
    
    /**
     *
     * @param IView $view 
     */
    public function registerObserver(IObserver $view) {
        $this->observers[$view->get_name()] = $view; #array_push($this->observers, $view);
    }
   /**
    *
    * @param IView $view 
    */
    public function removeObserver(IObserver $view) {
        foreach($this->observers as $observerKey => $observer){
            if($observerKey === $view->get_name()){
                #$this->observers[$observerKey] = null;
                unset($this->observers[$observerKey]);
            }
        }
    }
    
    public function courseListRequested(){
        #Course Logic here
       // $courses = enrol_get_all_users_courses();
        //{$CFG->prefix}
        $course_sql = "SELECT c.id AS course_id, c.fullname AS course_name, cc.name AS cat_name, cc.id AS cat_id FROM {course} c 
                       LEFT JOIN {course_categories} cc ON c.category = cc.id 
                       ORDER BY c.fullname ASC";
        $this->courses = $this->db->get_records_sql($course_sql);
        $this->notifyObservers();
    }
    
    /**
     *
     * 
     */
    public function notifyObservers(){
        foreach($this->observers as $observerKey => $observer){
            #call update method of view observers
            $observer->update($this->courses);
        }
    }
    
    
   public function loadCourses(){
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