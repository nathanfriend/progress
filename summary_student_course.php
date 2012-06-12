<?php
global $DB;
$base_url = str_replace('blocks/progress/summary_student_course.php', '', $_SERVER['SCRIPT_FILENAME']);
require_once($base_url.'config.php');
$course = $DB->get_record_select('course', 'id='.$_GET['course']);
//print_r($course);
$user = $DB->get_record_select('user', 'id='.$_GET['user']);
$student = $DB->get_record_select('user', 'id='.$_GET['student']);
if ( array_key_exists('group', $_GET) ) $group = $DB->get_record_select('groups', 'id='.$_GET['group']);
$now = time();
?>
<?php
function get_possible_criteria($prefix, $course) {
	$letters = array('P', 'M', 'D'); # needs to be based on bands
	foreach($letters as $letter) {
		$sql = 'select a.shortname, a.description
				from '.$prefix.'grade_outcomes a, '.$prefix.'grade_outcomes_courses b
				where a.id = b.outcomeid
				and b.courseid='.$course.
				' and a.shortname like "'.$letter.'%";';
		$result = mysql_query($sql);
		$temp = array();
		while ($row = mysql_fetch_assoc($result)) {
			$a = array();
			$a['shortname'] = str_replace($letter, '', $row['shortname']);
			$a['description'] = $row['description'];
			array_push($temp, $a);
		}
		$possible[$letter] = $temp;
	}
	return $possible;
}
?>
<?php
function get_achieved($prefix, $course, $student) {
	$data = array();
	$sql = 'SELECT a.itemname as criteria, a.iteminstance as assignmentid, b.finalgrade as grade
			FROM '.$prefix.'grade_items a, '.$prefix.'grade_grades b
			WHERE a.id=b.itemid
			AND a.courseid='.$course.'
			AND b.userid ='.$student.';';
	//echo $sql;
	$result = mysql_query($sql);
	while($row = mysql_fetch_object($result)) {
		$firstchar = strtoupper(substr($row->criteria, 0, 1));
		if ($firstchar=='P' || $firstchar=='M' || $firstchar=='D') {
			//echo '<pre>assignments:'."\n";
			//print_r($row);
			//echo '</pre>';
			$value = 'no';
			if ($row->grade > 0) $data[$row->criteria][$row->assignmentid] = true;
		}
	}
	//echo '<pre>assignments:'."\n";
	//print_r($data);
	//echo '</pre>';
	return $data;
}
?>
<?php
function has_outcomes($prefix, $course, $assignment) {
	
}
?>
<?php
function get_possible($prefix, $course, $student) {
	$temp = $DB->get_records_select('grade_items', 'courseid='.$course, '', array('id', 'courseid', 'itemname', 'iteminstance'));
	//echo '<pre>';
	//print_r($temp);
	//echo '</pre>';
	$possible = array();
	foreach ($temp as $item) {
		$firstchar = strtoupper(substr($item->itemname, 0, 1));
		if ($firstchar=='P' || $firstchar=='M' || $firstchar=='D') {
			$possible[$item->itemname][$item->iteminstance] = true;
		}
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
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<title><?php echo $course->fullname ?></title>
	<?php
	if (file_exists('screen.css')) echo '<link rel="stylesheet" href="screen.css" type="text/css" media="screen">';
	if (file_exists('print.css')) echo '<link rel="stylesheet" href="print.css" type="text/css" media="print">';
	?>
</head>
<body>
<?php
$criteria = get_possible_criteria($CFG->prefix, $_GET['course']);
$assignments = $DB->get_records_select('assignment', 'course='.$_GET['course']);
$possible = get_possible($CFG->prefix, $_GET['course'], $_GET['student']);
$achieved = get_achieved($CFG->prefix, $_GET['course'], $_GET['student']);
$count = 0;
foreach($assignments as $key=>$val) {
	$sql = 'SELECT visible from '.$CFG->prefix.'course_modules where course='.$course->id.' AND instance='.$key.';';
	//echo '<p>'.$sql.'</p>';
	$result = mysql_query($sql);
	$row = mysql_fetch_object($result);
	if ($row->visible == 0) {
		unset($assignments[$key]);
	} else {
		$assignments[$key]->num = ++$count;
	}
}
//echo '<pre>possible:'."\n";
//print_r($assignments);
//echo '</pre>';
//echo '<p>assignments: '.count($assignments).'</p>';
echo '<h1>'.$course->fullname.'</h1>';
echo '<h3>'.$student->firstname.' '.$student->lastname.'</h3>';
?>
<p class="printonly">printed by <?php echo $user->firstname.' '.$user->lastname ?></p>
<p class="printonly"><?php echo date('jS F Y', $now) ?></p>
<?php
if ($_GET['home'] != 'true') {
	echo '<p class="noprint"><a href="javascript:history.go(-1)">'.get_string('back', 'block_progress').'</a></p>';
}
?>
<table>
<tr></tr><th colspan="2">&nbsp;</th>
<?php
//for($i=1; $i<= count($assignments); $i++) echo '<th>'.$i.'</th>';
foreach ($assignments as $a) echo '<th title="'.$a->name.'">'.$a->num.'</th>'
?>
</tr>
<?php
foreach ($criteria as $letter=>$detail) {
	foreach ($detail as $num=>$desc) {
		$n = $desc['shortname'];
		echo '<tr><td>'.$letter.$n.'</td><td>'.shorten_text($desc['description'], 40).'</td>';
		foreach ($assignments as $key=>$assignment) {
			$desc = '';
			$class = 'notenrolled';
			$content = '&nbsp;';
			if ($possible[$letter.$n][$key]) {
				$class = 'partial nobox';
				$content = $letter.$n;
				$d = get_description($CFG->prefix, $_GET['course'], $letter.$n);
				if ($d) $desc = ' title="'.$d.'"';
			}
			if ($achieved[$letter.$n][$key]) $class = 'achieved';
			//if ($achieved[$letter.$n][$key] == 'yes') $class = 'yes';
			echo '<td'.$desc.' class="'.$class.'">'.$content.'</td>';
		}
		echo '</tr>';
	}
}
?>
</table>
<h3><?php print_string('key', 'block_progress') ?></h3>
<dl>
<?php
foreach ($assignments as $assignment) {
	echo '<dt>'.$assignment->num.'</dt><dd>'.$assignment->name.'</dd>';
}
?>
</dl>
<p class="noprint footer"><a href="#" onClick="window.print();return false"><?php print_string('print', 'block_progress'); ?></a></p>
</body>
</html>