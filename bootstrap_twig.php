<?php

namespace external;

defined('MOODLE_INTERNAL') || die;

class twig {

    /**
     * @var string
     */
    const HOME = 'Twig-1.12.1';

    /**
     * @var Twig_Environment
     */
    private static $_twig;

    /**
     * private c'tor
     */
    private function __construct() {
        // empty
    }

    /**
     * accessor
     * @return \Twig_Environment
     */
    public static function get_environment() {
        global $CFG;
        if (!empty(self::$_twig)) {
            return self::$_twig;
        }
        require_once $CFG->dirroot . '/' . self::HOME . '/lib/Twig/Autoloader.php';
        \Twig_Autoloader::register();
        $loader = new \Twig_Loader_Filesystem("{$CFG->dirroot}/twig_templates");
        $twig = new \Twig_Environment($loader, array('cache' => "{$CFG->dirroot}/twig_cache", 'auto_reload' => debugging()));

        // wrapper around get_string()
        $function = new \Twig_SimpleFunction('trans', function ($identifier, $component = '', $a = null) {
            return s(get_string($identifier, $component, $a));
        });
        $twig->addFunction($function);

        // wrapper around initializing an admin page
        $function = new \Twig_SimpleFunction('adminpage', function ($section) {
            global $CFG;
            require_once("{$CFG->libdir}/adminlib.php");
            admin_externalpage_setup($section);
        });
        $twig->addFunction($function);

        // wrapper around printing a Moodle header
        $function = new \Twig_SimpleFunction('header', function () {
            global $PAGE, $OUTPUT;
            $PAGE->set_context(\context_system::instance());
            return $OUTPUT->header();
        });
        $twig->addFunction($function);

        // wrapper around printing a Moodle footer
        $function = new \Twig_SimpleFunction('footer', function () {
            global $OUTPUT;
            return $OUTPUT->footer();
        });
        $twig->addFunction($function);

        // wrapper around including js
        $function = new \Twig_SimpleFunction('js', function ($path) {
            global $PAGE;
            $PAGE->requires->js($path);
        });
        $twig->addFunction($function);

        // wrapper around including css
        $function = new \Twig_SimpleFunction('css', function ($path) {
            global $PAGE;
            $PAGE->requires->css($path);
        });
        $twig->addFunction($function);

        // wrapper around displaying a moodle form
        $function = new \Twig_SimpleFunction('form', function (\moodleform $form) {
            $form->display();
        });
        $twig->addFunction($function);

        // wrapper around displaying the user's session key
        $function = new \Twig_SimpleFunction('sesskey', function () {
            global $USER;
            sesskey();
            return $USER->sesskey;
        });
        $twig->addFunction($function);

        // wrapper around getting the wwwroot
        $function = new \Twig_SimpleFunction('wwwroot', function () {
            global $CFG;
            return $CFG->wwwroot;
        });
        $twig->addFunction($function);

        // wrapper around determining whether the user is logged in
        $function = new \Twig_SimpleFunction('authenticated', function () {
            return isloggedin();
        });
        $twig->addFunction($function);

        self::$_twig = $twig;
        return $twig;
    }

}
