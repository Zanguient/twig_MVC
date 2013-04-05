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
 * @package totara
 * @subpackage totara_core
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/totara/core/totara.php');

/**
 *  * Resize an image to fit within the given rectange, maintaing aspect ratio
 *
 * @param string Path to image
 * @param string Destination file - without file extention
 * @param int Width to resize to
 * @param int Height to resize to
 * @param string Force image to this format
 *
 * @global $CFG
 * @return string Path to new file else false
 */
function resize_image($originalfile, $destination, $newwidth, $newheight, $forcetype = false) {
    global $CFG;

    require_once($CFG->libdir.'/gdlib.php');

    if(!(is_file($originalfile))) {
        return false;
    }

    if (empty($CFG->gdversion)) {
        return false;
    }

    $imageinfo = GetImageSize($originalfile);
    if (empty($imageinfo)) {
        return false;
    }

    $image = new stdClass;

    $image->width  = $imageinfo[0];
    $image->height = $imageinfo[1];
    $image->type   = $imageinfo[2];

    $ratiosrc = $image->width / $image->height;

    if ($newwidth/$newheight > $ratiosrc) {
        $newwidth = $newheight * $ratiosrc;
    } else {
        $newheight = $newwidth / $ratiosrc;
    }

    switch ($image->type) {
    case IMAGETYPE_GIF:
        if (function_exists('ImageCreateFromGIF')) {
            $im = ImageCreateFromGIF($originalfile);
            $outputformat = 'png';
        } else {
            notice('GIF not supported on this server');
            return false;
        }
        break;
    case IMAGETYPE_JPEG:
        if (function_exists('ImageCreateFromJPEG')) {
            $im = ImageCreateFromJPEG($originalfile);
            $outputformat = 'jpeg';
        } else {
            notice('JPEG not supported on this server');
            return false;
        }
        break;
    case IMAGETYPE_PNG:
        if (function_exists('ImageCreateFromPNG')) {
            $im = ImageCreateFromPNG($originalfile);
            $outputformat = 'png';
        } else {
            notice('PNG not supported on this server');
            return false;
        }
        break;
    default:
        return false;
    }

    if ($forcetype) {
        $outputformat = $forcetype;
    }

    $destname = $destination.'.'.$outputformat;

    if (function_exists('ImageCreateTrueColor') and $CFG->gdversion >= 2) {
        $im1 = ImageCreateTrueColor($newwidth,$newheight);
    } else {
        $im1 = ImageCreate($newwidth, $newheight);
    }
    ImageCopyBicubic($im1, $im, 0, 0, 0, 0, $newwidth, $newheight, $image->width, $image->height);

    switch($outputformat) {
    case 'jpeg':
        imagejpeg($im1, $destname, 90);
        break;
    case 'png':
        imagepng($im1, $destname, 9);
        break;
    default:
        return false;
    }
    return $destname;
}


/**
 * hook to add extra sticky-able page types.
 */
function local_get_sticky_pagetypes() {
    return array(
        // not using a constant here because we're doing funky overrides to PAGE_COURSE_VIEW in the learning path format
        // and it clobbers the page mapping having them both defined at the same time
        'Totara' => array(
            'id' => 'Totara',
            'lib' => '/totara/core/lib.php',
            'name' => 'Totara'
        ),
    );
}


/**
 * Determine if the current request is an ajax request
 *
 * @param array $server A $_SERVER array
 * @return boolean
 */
function is_ajax_request($server) {
    return (isset($server['HTTP_X_REQUESTED_WITH']) && strtolower($server['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest');
}
?>
