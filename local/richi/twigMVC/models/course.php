<?php
 if (!defined('MOODLE_INTERNAL')) {
    die('Direct access to this script is forbidden.');

}

require_once(dirname(__FILE__) . '/../../config.php');
require_login();
/**
 * This models our Course 
 */
class Course{
	
    
   /**
	* Constructor
	**/
	public function __construct($id, $user_id){
        $this->id = $id;
        $this->user_id = $user_id;
	}
   
   /**
    * 
    **/
    public function loadCoursesByLang($lang){
        $lang = current_language();
        $langWhere = !empty($lang) ? 'WHERE c.lang="' .$lang. '"' : '';
        $courses = get_records_sql("SELECT c.id AS courseid, c.fullname, c.category FROM {$CFG->prefix}course c ".$langWhere." ORDER BY fullname");
        if(!empty($courses)){
            
        }
    }

     
    

}
?>