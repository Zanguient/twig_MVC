<?php
require_once(dirname(__FILE__) . '/IController.php');

class CourseController implements IController{
    
    private $courseView;
    private $courseModel;
    
    public function __construct(IObservable $courseModel) {
       $this->courseModel = $courseModel;
       $this->courseView = new CourseView($courseModel, $this, 'courseview');
       $this->courseModel->registerObserver($this->courseView);
    }
    
    /**
     * 
     **/
    public function handleRequest($task, $format){
        $output = 'The task you are requesting has not been found in the system.';
        if(method_exists($this, $task)){
            $output = $this->{$task}();
        }
        return $output;
    }
    
    public function load($template_file){
      $this->courseModel->courseListRequested(); 
      $this->courseView->render($template_file);
    }
}
