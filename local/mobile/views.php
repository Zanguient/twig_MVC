<?php

namespace mobile;

defined('MOODLE_INTERNAL') || die;

/**
 * login
 */
function login() {
    global $CFG, $SESSION, $USER;

    // must not be logged in
    if (isloggedin()) {
        redirect("{$CFG->wwwroot}/local/mobile/?view=profile");
    }

    // if request method is get, set $SESSION->wantsurl
    if ($_SERVER['REQUEST_METHOD'] == 'GET') {
        $wantsurl = optional_param('next', '', PARAM_URL);
        if (!empty($wantsurl)) {
            $SESSION->wantsurl = $CFG->wwwroot . urldecode($wantsurl);
        }
    }

    // if request method is post, redirect to $SESSION->wantsurl, otherwise, to enrolled_courses
    $errors = array();
    if ($_SERVER['REQUEST_METHOD'] == 'POST') {
        require_sesskey();
        $username = required_param('username', PARAM_USERNAME);
        $password = required_param('password', PARAM_TEXT);
        $user = authenticate_user_login($username, $password);
        update_login_count();
        if ($user) {
            add_to_log(SITEID, 'user', 'login', "view.php?id={$USER->id}&course=" . SITEID, $user->id, 0, $user->id);
            complete_user_login($user);
            reset_login_count();
            set_moodle_cookie($USER->username);
            redirect(empty($SESSION->wantsurl) ? "{$CFG->wwwroot}/local/mobile/?view=enrolled_courses" : $SESSION->wantsurl);
        }
        $errors[] = array('identifier' => 'invalidlogin', 'component' => '');
    }

    // populate context and render template
    $context = array(
        'url' => "{$CFG->wwwroot}/local/mobile/?view=login",
        'errors' => $errors,
    );
    _inject_common_context_variables($context);
    echo \external\twig::get_environment()->render('local/mobile/login.twig', $context);
}

/**
 * logout
 */
function logout() {
    global $CFG;

    // must be logged in
    if (!isloggedin()) {
        redirect("{$CFG->wwwroot}/local/mobile/?view=login&next=" . urlencode('/local/mobile/?view=profile'));
    }

    // log out of Moodle
    $authsequence = get_enabled_auth_plugins();
    foreach ($authsequence as $authname) {
        $authplugin = get_auth_plugin($authname);
        $authplugin->logoutpage_hook();
    }
    require_logout();
    redirect("{$CFG->wwwroot}/local/mobile/?view=login");
}

/**
 * profile
 */
function profile() {
    global $CFG, $USER;

    // must be logged in
    if (!isloggedin()) {
        redirect("{$CFG->wwwroot}/local/mobile/?view=login&next=" . urlencode('/local/mobile/?view=profile'));
    }

    // populate context and render template
    $context = array(
        'user' => $USER,
    );
    _inject_common_context_variables($context);
    echo \external\twig::get_environment()->render('local/mobile/profile.twig', $context);
}

/**
 * enrolled courses
 */
function enrolled_courses() {
    global $CFG, $USER;

    // must be logged in
    if (!isloggedin()) {
        redirect("{$CFG->wwwroot}/local/mobile/?view=login&next=" . urlencode('/local/mobile/?view=enrolled_courses'));
    }

    // get model
    require_once(dirname(__FILE__) . '/models.php');
    $enrolled_courses_model = new enrolled_courses_model($USER->id);

    // populate context and render template
    $context = array(
        'user' => $USER,
        'courses' => $enrolled_courses_model->getCourses(),
    );
    _inject_common_context_variables($context);
    echo \external\twig::get_environment()->render('local/mobile/courses.twig', $context);
}

/**
 * course
 */
function course() {
    global $CFG, $USER;

    // get courseid
    $courseid = required_param('id', PARAM_INT);

    // must be logged in
    if (!isloggedin()) {
        redirect("{$CFG->wwwroot}/local/mobile/?view=login&next=" . urlencode('/local/mobile/?view=course&id=' . $courseid));
    }

    // log viewing the course
    add_to_log($courseid, 'course', 'view', "view.php?id={$courseid}", $courseid);

    // get model
    require_once(dirname(__FILE__) . '/models.php');
    $course_model = new course_model($courseid, $USER->id);

    // populate context and render template
    $context = array(
        'user' => $USER,
        'course' => $course_model->getCourse(),
        'activities' => $course_model->getActivities(),
    );
    _inject_common_context_variables($context);
    echo \external\twig::get_environment()->render('local/mobile/course.twig', $context);
}

/**
 * helper to inject nav links into context
 * @param array $context
 */
function _inject_common_context_variables(&$context) {
    global $CFG;
    $context['base_url'] = "{$CFG->wwwroot}/local/mobile";
    $context['logout_url'] = "{$CFG->wwwroot}/local/mobile/?view=logout";
    $context['courses_url'] = "{$CFG->wwwroot}/local/mobile/?view=enrolled_courses";
    $context['profile_url'] = "{$CFG->wwwroot}/local/mobile/?view=profile";
}
