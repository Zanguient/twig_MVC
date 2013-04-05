<?php
require_once(dirname(__FILE__) . '/IObserver.php');
require_login();
	
class CourseView implements IObserver{
    
    /**
     * @var Twig_Environment
     */
    private $twig;
    /**
     * @var IModel 
     */
    private $model;
    /**
     * @var IController 
     */
    private $controller;
    /**
     *
     * @var string 
     */
    private $name;
    /**
     *
     * @var string 
     */
    private $template_dir = '/templates/';
    /**
     *
     * @var array 
     */
    private $courses = array();
    /**
     *
     * @var boolean 
     */
    private $courseListChanged = false;
    
    
    //Getters and setters
    /**
     *
     * @return string 
     */
    public function get_name() {
        return $this->name;
    }
    
    public function get_twig($twig) {
        return $this->twig;
    }

    public function set_twig(Twig_Environment $twig) {
        $this->twig = $twig;
    }

    /**
     *
     * @param Twig_Environment $twig 
     * @param String $name
     */
    public function __construct(IObservable $model, IController $controlller, $name) {
        $this->model = $model;
        $this->controller = $controlller;
        $this->name = $name;
    }
    
    /**
     * 
     *
     */
    public function update(){
      $this->courses = $this->model->getCourses();
    }
    
    /**
     * @param string $template_file
     * @throws Exception 
     */    
    public function render($template_file){
        /*$template_path = dirname(dirname(__FILE__)) . $this->template_dir . $template_file;
        if (file_exists($template_path)) {
            include $template_path;
        }else {
            throw new Exception('No template file ' . $template_file . ' present in directory ' . $template_path);
        }
        //Twig_Template instance
        $template = $this->twig->loadTemplate($template_file);
        $template->display(array('courses'=>$this->courses));*/
        $context = array('courses' => $this->courses);
        echo \external\twig::get_environment()->render($template_file, $context);
        //echo $twig->render('index.html', array('the' => 'variables', 'go' => 'here'));
        // populate context and render template

    }
    
  private function formatCourseData(array $courses){
       foreach ($this->courses as $courseKey => $course){
           $this->courses['course'] = $course;
       }
       
   }  
}
