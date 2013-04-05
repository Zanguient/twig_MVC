<?php  // $Id$
    require_once('../config.php');
    require_once($CFG->libdir . '/adminlib.php');
    require_once('registerlib.php');
    require_once('register_form.php');

    admin_externalpage_setup('totararegistration');

    require_login();

    $renderer = $PAGE->get_renderer('core', 'register');

    require_capability('moodle/site:config', get_context_instance(CONTEXT_SYSTEM));

    if (!$site = get_site()) {
        redirect("index.php");
    }

    if (!$admin = get_admin()) {
        error("No admins");
    }

    if (!$admin->country and $CFG->country) {
        $admin->country = $CFG->country;
    }

/// Print headings
    $PAGE->navbar->add(get_string("administration"), new moodle_url('/admin/index.php'));

    echo $OUTPUT->header();

    echo $OUTPUT->heading(get_string("totararegistration", 'admin'), 3, 'main');

    echo $OUTPUT->box(get_string("totararegistrationinfo", 'admin'));

/// Print the form
    $mform = new register_form();
    $staticdata = get_registration_data();
    $data = $staticdata;
    $statusmsg = '';
    if ($formdata = $mform->get_data() and confirm_sesskey()) {
        if(isset($formdata->registrationenabled) && (!isset($CFG->registrationenabled) || ($formdata->registrationenabled != $CFG->registrationenabled))) {
            if (set_config('registrationenabled',$formdata->registrationenabled)) {
                $statusmsg = get_string('changessaved');
            }
        }
        if (!empty($CFG->registrationenabled)) {
            send_registration_data($staticdata);
        }
    } else {
        $registrationstatus = isset($CFG->registrationenabled) ? $CFG->registrationenabled : 0;
        $data['registrationenabled'] = $registrationstatus;
    }
    $mform->set_data($data);
    if (!empty($statusmsg)) {
        $url = $CFG->wwwroot.'/admin/register.php';
        totara_set_notification($statusmsg, $url, array('class'=>'notifysuccess'));
    }
    $mform->display();

    echo $OUTPUT->footer();

?>
