<?php
include('functions.php');
$base_url = str_replace('blocks/progress/summary_course.php', '', $_SERVER['SCRIPT_FILENAME']);
require_once($base_url.'config.php');
global $DB;
$course = $DB->get_record_select('course', 'id='.$_GET['course']);
$user = $DB->get_record_select('user', 'id='.$_GET['user']);
if ( isset($_GET['group']) ) $group = $DB->get_record_select('groups', 'id='.$_GET['group']);
$now = time();
?>
<?php
function display_grid($prefix, $courseid, $students, $criteria) {
	//print_r($criteria);
	$ok = array();
	if ( count($criteria['P']) ) $ok['P'] = true;
	if ( count($criteria['M']) ) $ok['M'] = true;
	if ( count($criteria['D']) ) $ok['D'] = true;
	//print_r($ok);
	$padding = array('P'=>0, 'M'=>0, 'D'=>0);
	$count['P'] = count($criteria['P']);
	$count['M'] = count($criteria['M']);
	$count['D'] = count($criteria['D']);
	if ($count['D'] < 6) {
		$padding['D'] = 6-$count['D'];
		$count['D'] = 6;
	}
	$data = '<table id="grades" border="1">'."\n";
	if ( array_key_exists('P', $ok) && array_key_exists('M', $ok) ) {
		$data .= '<tr><th colspan="3">&nbsp;</th>';
		if ($ok['P']) $data .= '<th class="divider">&nbsp;</th><th class="left small" colspan="'.$count['P'].'">'.get_string('pass', 'block_progress').'</th>';
		if ($ok['M']) $data .= '<th class="divider">&nbsp;</th><th class="left small" colspan="'.$count['M'].'">'.get_string('merit', 'block_progress').'</th>';
		if ($ok['D']) $data .= '<th class="divider">&nbsp;</th><th class="left small" colspan="'.$count['D'].'">'.get_string('distinction', 'block_progress').'</th>';
		$data .= '</tr>'."\n";
	}
	$data .= '<tr><th colspan="3">&nbsp;</th>';
	foreach ($criteria as $l=>$crit) {
		if ( array_key_exists($l, $ok) ) {
			$data .= '<th class="divider">&nbsp;</th>';
			foreach ($crit as $a=>$c) {
				$data .= '<th class="centre small">'.$c.'</th>';
			}
		}
	}
	$data .= '<th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th><th>&nbsp;</th></tr>';
	foreach ($students as $student) {
		$data .= '<tr><td>'.strtoupper($student->lastname).'</td><td>'.ucfirst(strtolower($student->firstname)).'</td></td><td>&nbsp;</td>';
		foreach ($criteria as $letter=>$crit) {
		if ( array_key_exists($letter, $ok) ) {
			$data .= '<td class="divider">&nbsp;</td>';
			foreach ($crit as $a=>$c) {
				$class = 'partial hide';
				$title = '';
				$desc = get_description($prefix, $courseid, $letter.$c);
				if ($desc) $title = ' title="'.$desc.'"';
				if (outcome_achieved($prefix, $courseid, $student->id, $letter.$c)) $class = 'achieved';
				$data .= '<td'.$title.' class="'.$class.'">'.$letter.$c.'</td>';
			}
		}
	}
		$data .= '</tr>';
	}
	$data .= '</table>'."\n";
	return $data;
}
?>
<?php
$criteria = get_possible_criteria($CFG->prefix, $course->id);
//print_r($criteria);
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
<h1><?php echo $course->fullname ?></h1>
<p class="printonly">printed by <?php echo $user->firstname.' '.$user->lastname ?></p>
<p class="printonly"><?php echo date('jS F Y', $now) ?></p>
<?php
if ( !array_key_exists('home', $_GET) ) {
	echo '<p class="noprint"><a href="javascript:history.go(-1)">'.get_string('back', 'block_progress').'</a></p>';
}
?>
<p class="noprint"><?php echo generate_group_links($course->id, 'summary_course.php') ?></p>
<?php
if ( array_key_exists('group', $_GET) ) $students = get_students($course->id, $_GET['prefix'], $_GET['group']);
else $students = get_students($course->id, $_GET['prefix']);
?>
<?php
echo display_grid($CFG->prefix, $course->id, $students, $criteria);
echo display_key($CFG->prefix, $course->id, $criteria);
?>
<p class="noprint footer"><a href="#" onClick="window.print();return false"><?php print_string('print', 'block_progress'); ?></a></p>
</body>
</html>