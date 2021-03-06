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
 * @author Paul Walker <paul.walker@catalyst-eu.net>
 * @package totara
 * @subpackage theme
 *
 * @copyright Totara Learning Solutions Ltd
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Settings for the kiwifruit theme
 */

defined('MOODLE_INTERNAL') || die;

if ($ADMIN->fulltree) {

// Logo file setting
$name = 'theme_kiwifruit/logo';
$title = get_string('logo','theme_kiwifruit');
$description = get_string('logodesc', 'theme_kiwifruit');
$default = "";
$setting = new admin_setting_configfilepicker($name, $title, $description, $default, array('web_image'));
$settings->add($setting);

// Favicon file setting
$name = 'theme_kiwifruit/favicon';
$title = get_string('favicon','theme_kiwifruit');
$description = get_string('favicondesc', 'theme_kiwifruit');
$default = "";
$setting = new admin_setting_configfilepicker($name, $title, $description, $default, array('*.ico'));
$settings->add($setting);

// Custom CSS file
$name = 'theme_kiwifruit/customcss';
$title = get_string('customcss','theme_kiwifruit');
$description = get_string('customcssdesc', 'theme_kiwifruit');
$default = '';
$setting = new admin_setting_configtextarea($name, $title, $description, $default);
$settings->add($setting);
}

