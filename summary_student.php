<?php
$base_url = str_replace('blocks/progress/summary_student.php', '', $_SERVER['SCRIPT_FILENAME']);
require_once($base_url.'config.php');
$student = get_record_select('user', 'id='.$_GET['student']);
$courses = get_my_courses($student->id, 'sortorder');
$user = get_record_select('user', 'id='.$_GET['user']);
$now = time();

// require valid moodle login.  Will redirect to login page if not logged in.
require_login();

//Gets role id in course context.
$context = get_context_instance(CONTEXT_COURSE,$SESSION->cal_course_referer);
if ($roles = get_user_roles($context, $USER->id)) {
foreach ($roles as $role) {
//echo 'ID: '.$role->roleid.'<br />';
//echo 'Name'.$role->name.'<br />';
}
}

//Checks session userid matches url id.
function CheckRole() {
                     global $USER;
                     if ($_GET['student']==$USER->id) { 
                                                      } else exit("Authentication missmatch, access denied.");
                     }

//If user is a student call CheckRole function.
if ($role->roleid==5) {
                      CheckRole();
                      } 

$r = get_record('role_assignments', 'userid', $USER->id);
//echo 'role 2:' . $r->roleid;
//if ($r->roleid==5) { CheckRole(); }
if ($role->roleid==5) {CheckRole(); }

?>
<?php
function uses_outcomes($prefix, $courseid) {
	$grades = get_possible($prefix, $courseid);
	if ($grades['P']) return true;
	return false;
}
?>
<?php
function get_possible($prefix, $course) {
	$letters = array('P', 'M', 'D'); # needs to be based on bands
	foreach($letters as $letter) {
		$temp = array();
		$sql = 'select a.shortname
				from '.$prefix.'grade_outcomes a, '.$prefix.'grade_outcomes_courses b
				where a.id = b.outcomeid
				and b.courseid='.$course.
				' and a.shortname like "'.$letter.'%";';
		$result = mysql_query($sql);
		$count = mysql_num_rows($result);
		$possible[$letter] = $count;
	}
	return $possible;
}
?>
<?php // MJT NEEDS FIXING!
function get_achieved($prefix, $student, $course) {
	$sql = 'SELECT b.itemname
			FROM '.$prefix.'grade_grades a, '.$prefix.'grade_items b, '.$prefix.'grade_outcomes c
			WHERE a.itemid = b.id
			AND b.itemname = c.fullname
			AND a.userid = '.$student.'
			AND a.finalgrade >1
			AND b.courseid = '.$course.';';
	$result = mysql_query($sql);
	while ($row = mysql_fetch_assoc($result)) {
		$c = 'g'.$row['itemname'];
		$outcome[$c] = 1;
	}
	$achieved = array('P'=>0, 'M'=>0, 'D'=>0);
	//print_r($outcome);
	if ( isset($outcome) ) {
		foreach ($outcome as $c=>$d) {
			if (strpos($c, 'P')) $achieved['P']++;
			if (strpos($c, 'M')) $achieved['M']++;
			if (strpos($c, 'D')) $achieved['D']++;
		}
		return $achieved;
	}
	return null;
}
?>
<?php
function outcome_achieved($prefix, $course, $student, $outcome) {
	$quiz = quiz_outcome_achieved($prefix, $course, $student, $outcome);
	if ($quiz) return true;
	$sql = 'SELECT b.itemname
			FROM '.$prefix.'grade_grades a, '.$prefix.'grade_items b, '.$prefix.'grade_outcomes c
			WHERE a.itemid = b.id
			AND b.itemname = c.fullname
			AND a.userid = '.$student.'
			AND a.finalgrade >1
			AND c.shortname = "'.$outcome.'"
			AND b.courseid = '.$course.';';
	$result = mysql_query($sql);
	if (mysql_num_rows($result)) return true;
	return false;
}
?>
<?php
// returns true if the user has achieved a specific outcome through a quiz //
function quiz_outcome_achieved($prefix, $course, $student, $outcome) {
	$quizzes = get_records_select('quiz', 'course='.$course, 'id');
	foreach($quizzes as $q) {
		$threshold = get_field_select('quiz_feedback', 'mingrade', 'feedbacktext="PASS"');
		$grade = get_field_select('quiz_grades', 'grade', 'quiz='.$q->id.' and userid='.$student);
		if ($grade >= $threshold) { // they have passed the quiz!
			// which criteria have they passed?
			$sql = 'select count(b.id) as id from mdl_grade_items a, mdl_grade_outcomes b 
					where a.outcomeid = b.id 
					and a.courseid = '.$course.' 
					and a.itemmodule = "quiz"
					and b.shortname = "'.$outcome.'";';
			$result = mysql_query($sql);
			$row = mysql_fetch_assoc($result);
			if ($row['id']) return true;
		}
	}
	return false;
}
?>
<?php
function get_possible_criteria($prefix, $course) {
	$letters = array('P', 'M', 'D'); # needs to be based on bands
	foreach($letters as $letter) {
		$sql = 'select a.shortname
				from '.$prefix.'grade_outcomes a, '.$prefix.'grade_outcomes_courses b
				where a.id = b.outcomeid
				and b.courseid='.$course.
				' and a.shortname like "'.$letter.'%";';
		$result = mysql_query($sql);
		$temp = array();
		while ($row = mysql_fetch_assoc($result)) {
			array_push($temp, str_replace($letter, '', $row['shortname']));
		}
		$possible[$letter] = $temp;
	}
	return $possible;
}
?>

<?php
function get_description($prefix, $course, $outcome) {
	$sql = 'SELECT description from '.$prefix.'grade_outcomes WHERE courseid='.$course.' and shortname="'.$outcome.'";';
	$result = mysql_query($sql);
	$row = mysql_fetch_assoc($result);
	$desc = str_replace('<p>', "\n", $row['description']);
	$desc = strip_tags($desc);
	return $desc;
}
?>
<?php
function get_points($course) {
	$idnumber = get_field_select('course', 'idnumber', 'id='.$course['course']->id);
	$values = explode('/', $idnumber);
	if (count($values) == 3 && is_numeric($values[0]) && is_numeric($values[1]) && is_numeric($values[2])) {
		$points = 0;
		if ($course['achieved']['summary']['P'] >= $course['possible']['summary']['P']) $points = $values[0];
		if ($course['achieved']['summary']['M'] >= $course['possible']['summary']['M']) $points = $values[1];
		if ($course['achieved']['summary']['D'] >= $course['possible']['summary']['D']) $points = $values[2];
		return $points;
	}
	return 0;
}
?>
<?php
$all_courses = array();
$max = array('P'=>0, 'M'=>0, 'D'=>0);
foreach ($courses as $course) {
	if (uses_outcomes($CFG->prefix, $course->id)) { // only a valid course if uses PMD outcomes
		$poss = array();
		$all_courses[$course->sortorder]['course'] = $course;
		$all_courses[$course->sortorder]['possible']['summary'] = get_possible($CFG->prefix, $course->id);
		$all_courses[$course->sortorder]['possible']['detail'] = get_possible_criteria($CFG->prefix, $course->id);
		$poss = get_possible($CFG->prefix, $course->id);
		if ($poss['P'] > $max['P']) $max['P'] = $poss['P'];
		if ($poss['M'] > $max['M']) $max['M'] = $poss['M'];
		if ($poss['D'] > $max['D']) $max['D'] = $poss['D'];
		$all_courses[$course->sortorder]['achieved']['summary'] = get_achieved($CFG->prefix, $student->id, $course->id);
		//$grades = get_grade_data($CFG->prefix, $student->id, $course->id);
	}
}
//echo '<pre>';
//print_r($all_courses);
//echo '</pre>';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title>Summary</title>
	<?php
	if (file_exists('screen.css')) echo '<link rel="stylesheet" href="screen.css" type="text/css" media="screen">';
	if (file_exists('print.css')) echo '<link rel="stylesheet" href="print.css" type="text/css" media="print">';
	?>
</head>
<body>
<h1><?php echo get_string('summaryforstudent', 'block_progress').' '.$student->firstname.' '.$student->lastname ?></h1>
<p class="printonly">printed by <?php echo $user->firstname.' '.$user->lastname ?></p>
<p class="printonly"><?php echo date('jS F Y', $now) ?></p>
<?php
if ( array_key_exists('home', $_GET) && $_GET['home'] != 'true' && $_GET['student'] != $_GET['user']) {
	echo '<a href="javascript:history.go(-1)">'.get_string('back', 'block_progress').'</a>';
}
?>
<table border="1">
<tr>
<th colspan="3">&nbsp</th>
<?php
// FOR DEBUG
if ( array_key_exists('debug', $_GET) ) echo '<th class="centre">P</th><th class="centre">M</th><th class="centre">D</th>';

echo '<th class="left" colspan="'.($max['P']+1).'">'.get_string('pass', 'block_progress').'</th>';
echo '<th class="left" colspan="'.($max['M']+1).'">'.get_string('merit', 'block_progress').'</th>';
echo '<th class="left" colspan="'.($max['D']+6).'">'.get_string('distinction', 'block_progress').'</th>';
?>
</tr>
<?php
$points_total = 0;
foreach ($all_courses as $course) {
	$points = get_points($course);
	$points_total += $points;
	if ($points==0) $points = '&nbsp;';
	//echo '<pre>';
	//print_r($course);
	//echo '</pre>';
	$unit_num = '';
	if ( array_key_exists('prefix', $_GET) ) $unit_num = '<strong>'.str_replace(strtoupper($_GET['prefix']), '', $course['course']->shortname).'</strong> ';
	echo '<tr>';
	echo '<td class="right">'.$unit_num.$course['course']->fullname.'</td>';
	$url  = 'summary_student_course.php?course='.$course['course']->id.'&user='.$_GET['user'].'&student='.$user->id;
	if ( array_key_exists('prefix', $_GET) ) $url .= '&prefix='.$_GET['prefix'];
	//echo '<td class="noprint"><a href="'.$url.'">'.get_string('show', 'block_progress').'</a></td>';
	echo '<td class="noprint">&nbsp;</td>';
	echo '<td class="right">'.$points.'</td>';
	// FOR DEBUG
	if ( array_key_exists('debug', $_GET) ) {
		echo '<td class="partial">'.$course['achieved']['summary']['P'].'/'.$course['possible']['summary']['P'].'</td>';
		echo '<td class="partial">'.$course['achieved']['summary']['M'].'/'.$course['possible']['summary']['M'].'</td>';
		echo '<td class="partial">'.$course['achieved']['summary']['D'].'/'.$course['possible']['summary']['D'].'</td>';
	}
	// PASS
	echo '<td class="divider">&nbsp;</td>';
	//for ($i=0; $i<$course['possible']['summary']['P']; $i++ ) echo '<td class="nograde">&nbsp;</td>';
	//print_r($courses['possible']['detail']['P']);
	foreach($course['possible']['detail']['P'] as $c) {
		$class = 'partial nobox';
		$title = '';
		$desc = get_description($CFG->prefix, $course['course']->id, 'P'.$c);
		if ($desc) $title = ' title="'.$desc.'"';
		if (outcome_achieved($CFG->prefix, $course['course']->id, $student->id, 'P'.$c)) $class = 'achieved';
		if (quiz_outcome_achieved($CFG->prefix, $course['course']->id, $student->id, 'P'.$c)) $class = 'achieved';
		echo "\n".'<td'.$title.' class="'.$class.'">'.$c.'</td>';
	}
	$padding_p = $max['P']-$course['possible']['summary']['P'];
	for ($i=0; $i<$padding_p; $i++) echo "\n".'<td class="notenrolled">&nbsp;</td>';
	// MERIT
	echo "\n".'<td class="divider">&nbsp;</td>';
	foreach($course['possible']['detail']['M'] as $c) {
		$class = 'partial nobox';
		$title = '';
		$desc = get_description($CFG->prefix, $course['course']->id, 'M'.$c);
		if ($desc) $title = ' title="'.$desc.'"';
		if (outcome_achieved($CFG->prefix, $course['course']->id, $student->id, 'M'.$c)) $class = 'achieved';
		if (quiz_outcome_achieved($CFG->prefix, $course['course']->id, $student->id, 'M'.$c)) $class = 'achieved';
		//echo "\n".'<td class="'.$class.'">'.$c.'</td>';
		echo "\n".'<td'.$title.' class="'.$class.'">'.$c.'</td>';
	}
	$padding_p = $max['M']-$course['possible']['summary']['M'];
	for ($i=0; $i<$padding_p; $i++) echo "\n".'<td class="notenrolled">&nbsp;</td>';
	// DISTINCTION
	echo "\n".'<td class="divider">&nbsp;</td>';
	foreach($course['possible']['detail']['D'] as $c) {
		$class = 'partial nobox';
		$title = '';
		$desc = get_description($CFG->prefix, $course['course']->id, 'D'.$c);
		if ($desc) $title = ' title="'.$desc.'"';
		if (outcome_achieved($CFG->prefix, $course['course']->id, $student->id, 'D'.$c)) $class = 'achieved';
		if (quiz_outcome_achieved($CFG->prefix, $course['course']->id, $student->id, 'D'.$c)) $class = 'achieved';
		//echo "\n".'<td class="'.$class.'">'.$c.'</td>';
		echo "\n".'<td'.$title.' class="'.$class.'">'.$c.'</td>';
	}
	$padding_p = $max['D']-$course['possible']['summary']['D'];
	for ($i=0; $i<$padding_p; $i++) echo "\n".'<td class="notenrolled">&nbsp;</td>';
	echo "\n".'</tr>';
}
if ($points_total > 0 ) echo '<tr><td class="right"><em>'.get_string('pointstotal', 'block_progress').'</em></td><td class="total right"><strong>'.$points_total.'</strong></td></tr>';
?>
</table>
<p class="noprint footer"><a href="#" onClick="window.print();return false"><?php print_string('print', 'block_progress');
?></a></p>
</body>
</html>
