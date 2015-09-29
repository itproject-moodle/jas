<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Page to edit the question bank
 *
 * @package    moodlecore
 * @subpackage questionbank
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require_once(dirname(__FILE__) . '/../config.php');
require_once($CFG->dirroot . '/question/editlib.php');

// Include Composer autoloader if not already done.
include '../local/myplugin/pdfparser/vendor/autoload.php';
require("../local/myplugin/pdfparser/dbOption/Db.class.php");

$db = new Db();
$transmutation 	 =    $db->query("SELECT * FROM mdl_transmutation");

list($thispageurl, $contexts, $cmid, $cm, $module, $pagevars) =
question_edit_setup('upload', '/question/jas.php');

$url = new moodle_url($thispageurl);
if (($lastchanged = optional_param('lastchanged', 0, PARAM_INT)) !== 0) {
	$url->param('lastchanged', $lastchanged);
}
$PAGE->set_url($url);

// TODO log this page view.

$context = $contexts->lowest();
$streditingquestions = get_string('upload', 'local_myplugin');
$PAGE->set_title($streditingquestions);
$PAGE->set_heading($COURSE->fullname);
echo $OUTPUT->header();

$user_id = $USER->id;			// get id of user
$course_id = $COURSE->id;		// get id of course

$_SESSION['user_id'] = $user_id;
$_SESSION['category'] = $category;
$_SESSION['course_id'] = $course_id;

	echo 'Hello you lil shit';

	if (empty($transmutation)) 
	{	
		echo "Table is empty!";
	}
	else
	{
		$table = new html_table();
		$table->head = array('Grade From','Grade To', 'Equivalent');
		foreach ($transmutation as $key => $value) {
			$gradefrom 	= $value['gradefrom'];
			$gradeto 	= $value['gradeto'];
			$equi 		= $value['equivalent'];
			$table->data[] = array($gradefrom, $gradeto, $equi);
		}
		echo html_writer::table($table);
	}
	
	echo $OUTPUT->footer();
