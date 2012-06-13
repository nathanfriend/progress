<?php
$base_url = str_replace('blocks/progress/summary.php', '', $_SERVER['SCRIPT_FILENAME']);
require_once($base_url.'config.php');
//require_once($CFG->dirroot .'/lib/moodlelib.php');
?>
<?php
function generate_group_links($courseid) {
        global $DB;
	$groups = $DB->get_records_select('groups', 'courseid='.$courseid, array('name', 'id, name'));
	if (count($groups)) {
		$data = '';
		$url = 'summary.php?course='.$courseid.'&prefix='.$_GET['prefix'].'&user='.$_GET['user'];
		$gr = array();
		if ( array_key_exists('group', $_GET) ) {
			array_push($gr, '<a href="'.$url.'">'.get_string('all', 'block_progress').'</a>');
		} else {
			array_push($gr, get_string('all', 'block_progress'));
		}
		foreach($groups as $group) {
			if ( array_key_exists('group', $_GET) && $group->id == $_GET['group'] ) {
				array_push($gr, $group->name);
			} else {
				array_push($gr, '<a href="'.$url.'&group='.$group->id.'">'.$group->name.'</a>');
			}
		}
	}
	return implode(' | ', $gr);
}
?>
<?php
function get_grade($prefix, $s, $c) {
	$grade = get_grades($prefix, $c);
	$achieved = get_achieved($prefix, $s, $c);
	$result = '';
	if ($achieved['P'] >= 1 ) {
		$result = $achieved['P'].'/'.$grade['P'];
		//$result = '-';
		if ($achieved['P'] == $grade['P']) {
			$result = 'P';
			if ($achieved['M'] == $grade['M']) {
				$result = 'M';
				if ($achieved['D'] == $grade['D']) {
					$result = 'D';
					if ( $achieved['P'] == $grade['P'] && $grade['M'] == 0 && $grade['D'] == 0 ) {
						$result = 'P';
					}
				}
			}
		}
	}
	return $result;
}
?>
<?php
function get_grades($p, $c) {
	$letters = array('P', 'M', 'D'); # needs to be based on bands
	foreach($letters as $letter) {
		$temp = array();
		$sql = 'select a.shortname
				from '.$p.'grade_outcomes a, '.$p.'grade_outcomes_courses b
				where a.id = b.outcomeid
				and b.courseid='.$c.
				' and a.shortname like "'.$letter.'%";';
        include '/protected/dbcred.php'; 
        mysql_connect($host, $user, $pass);
        mysql_select_db($db);
		$result = mysql_query($sql);
		$count = mysql_num_rows($result);
		$grade[$letter] = $count;
	}
	return $grade;
}
?>
<?php
function get_achieved($p, $s, $c) {
        global $DB;
	$sql = 'SELECT c. shortname, b.itemname
			FROM '.$p.'grade_grades a, '.$p.'grade_items b, '.$p.'grade_outcomes c
			WHERE a.itemid = b.id
			AND b.outcomeid = c.id
			AND a.userid = '.$s.'
			AND a.finalgrade >1
			AND b.courseid = '.$c.';';
	//echo '<p>'.$sql.'</p>';
	$result = mysql_query($sql);
	while ($row = mysql_fetch_assoc($result)) {
		//$c = 'g'.$row['itemname'];
		$crit = $row['shortname'];
		$outcome[$crit] = 1;
	}
	// check quizzes...
	$q = $DB->get_records_select('quiz', 'course='.$c, array('id, sumgrades, grade'));
	if ( count($q) ) {
	foreach ($q as $quiz) { //loops through all quizzes in course
		$threshold = $DB->get_record_select('quiz_feedback', 'quizid='.$quiz->id, array('feedbacktext="PASS"', 'mingrade'));
		$curr_score = $DB->get_record_select('quiz_grades', 'quiz='.$quiz->id, array('userid='.$s.'', 'grade'));
		if ( $curr_score != null ) {
			if ($curr_score->grade >= $threshold->mingrade && $threshold->mingrade != null) {
			// User has PASSED this quiz!
			$sql = 'SELECT b.shortname 
					FROM '.$p.'grade_items a, '.$p.'grade_outcomes b 
					WHERE a.outcomeid = b.id 
					AND a.courseid='.$c.' 
					AND a.itemmodule="quiz" 
					AND a.iteminstance='.$quiz->id.';';
			$result = mysql_query($sql);
			while ( $row = mysql_fetch_assoc($result) ) {
				//echo '<pre>';
				//print_r($row);
				//echo '</pre>';
				$crit2 = $row['shortname'];
				if (substr($crit2, 0, 1) == 'P' || substr($crit2, 0, 1) == 'M' || substr($crit2, 0, 1) == 'D') {
					$outcome[$crit2] = 1;
				}
			}
		}
		}
	}
	} // end if
	//echo '<p>student: '.$s.'</p><pre>';
	//print_r($outcome);
	//echo '</pre>';
	
	
	$achieved = array('P'=>0, 'M'=>0, 'D'=>0);
	if (isset($outcome)) {
		foreach ($outcome as $c=>$d) {
			if (substr($c, 0, 1) == 'P') $achieved['P']++;
			if (substr($c, 0, 1) == 'M') $achieved['M']++;
			if (substr($c, 0, 1) == 'D') $achieved['D']++;
			//if (strpos($c, 'P')) $achieved['P']++;
			//if (strpos($c, 'M')) $achieved['M']++;
			//if (strpos($c, 'D')) $achieved['D']++;
		}
	}
	//print_r($achieved);
	return $achieved;
}
?>
<?php
function get_points($idnumber) {
	$temp = explode('/', $idnumber);
	if (count($temp) == 3 && is_numeric($temp[0]) && is_numeric($temp[1]) && is_numeric($temp[2])) {
		$points['P'] = $temp[0];
		$points['M'] = $temp[1];
		$points['D'] = $temp[2];
	}
	unset($temp);
	return $points;
}
?>
<?php
function uses_outcomes($prefix, $courseid) {
	$grades = get_grades($prefix, $courseid);
	//if ($grades['P'] && $grades['M'] && $grades['D']) return true;
	if ($grades['P']) return true;
	return false;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">

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
<img src="cc-logo.jpg" align="right"/>
<div class="page">
<?php
$courseid = $_GET['course'];
$userid = $_GET['user'];
$prefix = $_GET['prefix'];
if (isset($_GET['group'])) $group = $_GET['group'];
$now = time();
$user = $DB->get_record_select('user', 'id='.$userid);
$category_id = $DB->get_field_select('course', 'category', 'id='.$courseid);
$category_name = $DB->get_field_select('course_categories', 'name', 'id='.$category_id);
echo '<h1>'.$category_name.'</h1>';
if ( isset($group) ) {
        $g = $DB->get_field('groups', 'name', array('id'=>$group));
	echo '<h2>'.get_string('summaryfor', 'block_progress').' '.$g.'</h2>';
} else {
	echo '<h2>'.get_string('summary', 'block_progress').'</h2>';
}
?>
<p class="noprint"><?php echo generate_group_links($courseid) ?></p>
<p class="printonly"><?php echo $user->firstname.' '.$user->lastname ?></p>
<p class="printonly"><?php echo date('jS F Y', $now) ?></p>
<?php
$student_role=$DB->get_field('role','id',array('shortname'=>'student'));
$context = get_context_instance(CONTEXT_COURSE,$courseid);
if (has_capability('mod/assignment:grade', $context)) {
	$all_courses = array();
	$student_data = array();
	$students = get_role_users($student_role, $context, true);
	if ($students) {
		if (isset($group)) {
			$members = $DB->get_records_select('groups_members', 'groupid='.$group);
			foreach($members as $m) {
				$i = $m->userid;
				$member[$i] = true;
			}
		}
		foreach($students as $student) {
			if ( isset($member[$student->id]) && $member[$student->id] != null && $member[$student->id] || !isset($group)) {
				$courses = enrol_get_users_courses($student->id, 'sortorder');
				foreach ($courses as $course) {
					if (uses_outcomes($CFG->prefix, $course->id)) { // only a valid course if uses PMD outcomes
						$all_courses[$course->sortorder]['course'] = $course;
						$all_courses[$course->sortorder]['student'][$student->id] = 'Y';
					}
				}
			}
		}
		ksort($all_courses);
		# DISPLAY TABLE OF GRADES
		$totalgrades = array();
		$achievedgrades = array();
		$totalgr_stu = array();
		$achievedgr_stu = array();
		$stu_on_course = array();
		$courses_per_stu = array();
		echo '<div id="grades"><table><tr><th colspan="3">&nbsp;</th>';
		foreach ($all_courses as $key=>$val) {
			$course_label = str_replace(strtoupper($prefix), '', strtoupper($val['course']->shortname));
			echo '<th class="course" title="'.$val['course']->fullname.'"><a class="print" href="summary_course.php?course='.$val['course']->id.'&user='.$user->id.'&prefix='.$_GET['prefix'];
			if ( array_key_exists('group', $_GET) ) echo '&group='.$_GET['group'];
			echo '">'.$course_label.'</a></th>';
		}
		echo '</tr>';
		$not_enrolled = 0;
		foreach ($students as $student) {
			//if () {}
			if ( isset($member[$student->id]) || !isset($group) ) {
				$stuid = $student->id;
				$courses_per_stu[$stuid] = 0;
				$url = 'summary_student.php?prefix='.$_GET['prefix'].'&course='.$_GET['course'].'&student='.$student->id.'&user='.$user->id;
				if ( isset($_GET['debug']) ) $url .= '&debug=true';
				echo '<tr>';
				echo '<td class="label">'.strtoupper($student->lastname).'</a></td><td class="label">'.ucfirst(strtolower($student->firstname)).'</td><td><a class="noprint" href="'.$url.'">'.get_string('show', 'block_progress').'</a></td>';
				foreach($all_courses as $key=>$val) {
					if (isset($val['student'][$student->id])) { # has the student got a record for this course?
						$courseid = $val['course']->id;
						if ( !isset($stu_on_course[$courseid]) ) $stu_on_course[$courseid] = 0;
						//echo '<p>'.$stuid.'</p>';
						$stu_on_course[$courseid]++;
						$courses_per_stu[$stuid]++;
						$c = $val['course']->id;
						$grade = get_grade($CFG->prefix, $student->id, $val['course']->id);
						$g = get_grades($CFG->prefix, $val['course']->id);
						$a = get_achieved($CFG->prefix, $student->id, $val['course']->id);
						if (!isset($totalgrades[$c])) $totalgrades[$c] = 0;
						$totalgrades[$c] += $g['P'];
						if (!isset($totalgr_stu[$stuid])) $totalgr_stu[$stuid] = 0;
						$totalgr_stu[$stuid] += $g['P'];
						if (!isset($achievedgrades[$c])) $achievedgrades[$c] = 0;
						$achievedgrades[$c] += $a['P'];
						if (!isset($achievedgr_stu[$stuid])) $achievedgr_stu[$stuid] = 0;
						$achievedgr_stu[$stuid] += $a['P'];
						if ($grade == 'P' || $grade=='M' || $grade=='D') { # completion
							echo '<td class="pass" title="'.$val['course']->fullname.'">'.$grade.'</td>';
						} else if(strpos($grade, '/')) { # partial completion
							echo '<td class="partial" title="'.$val['course']->fullname.'">'.$grade.'</td>';
						} else {
							echo '<td class="nograde" title="'.$val['course']->fullname.'">&nbsp;</td>';
						}
					} else {
						echo '<td class="notenrolled" title="'.$val['course']->fullname.'">&nbsp;</td>';
						$not_enrolled++;
					}
				}
				$p = floor($achievedgr_stu[$stuid] / $totalgr_stu[$stuid] * 100);
				if ($p > 0) echo '<td class="summary">'.$p.'%</td>';
				else echo '<td class="summary">&nbsp;</td>';
				echo '<td class="summary">'.$courses_per_stu[$stuid].'</td>';
				echo '</tr>';
			}
		}
		echo '<tr><td colspan="3">&nbsp;</td>';
		foreach ($all_courses as $key=>$val) {
			$i = $val['course']->id;
			$p = floor($achievedgrades[$i] / $totalgrades[$i] * 100);
			if ($p > 0) {
				echo '<td class="summary" title="'.$val['course']->fullname.'">'.$p.'%</td>';
			} else {
				echo '<td class="summary">&nbsp;</td>';
			}
		}
		echo '</tr>';
		
		echo '<tr><td colspan="3">&nbsp;</td>';
		foreach ($all_courses as $key=>$val) {
			$i = $val['course']->id;
			echo '<td class="summary">'.$stu_on_course[$i].'</td>';
		}
		echo '</tr>';
		
		echo '</table></div></div>';

		# DISPLAY KEY
		$teacher_role=$DB->get_field('role','id',array('shortname'=>'editingteacher'));
		echo '<div class="page">';
		echo "\n".'<div id="key"><h2 class="newpage">'.get_string('key', 'block_progress').'</h2>';
		echo '<table><tr><td colspan="2"><h3>'.get_string('courses', 'block_progress').'</h3></td></tr>';
		foreach ($all_courses as $key=>$val) {
			$courseid = $val['course']->id;
			//$context = $val['course']->context;
			$context = get_context_instance(CONTEXT_COURSE,$courseid);
                        //$teachers = get_role_users($teacher_role, $context, true);
			$teach = array();
			foreach ($teachers as $t) {
				$name = strtoupper($t->lastname).' '.ucfirst(strtolower($t->firstname));
				array_push($teach, $name);
			}
			$teach_list = implode(', ', $teach);
			$course_label = str_replace(strtoupper($prefix), '', strtoupper($val['course']->shortname));
			echo '<tr><th class="course">'.$course_label.'</th><td class="label">'.$val['course']->fullname.'</td></tr>';
			echo '<tr><td class="small">&nbsp;</th><td class="small">'.$teach_list.'</th></tr>';
		}
		?>
		<tr class="noprint"><td colspan="2"><h3><?php print_string('colours', 'block_progress'); ?></h3></td></tr>
		<tr class="noprint"><th class="nograde"></th><td class="label"><?php print_string('notachieved', 'block_progress'); ?></td></tr>
                <tr class="noprint"><th class="partial"></th><td class="label"><?php echo 'partially achieved'; ?></td></tr>
		<tr class="noprint"><th class="pass"></th><td class="label"><?php print_string('achieved', 'block_progress'); ?></td></tr>
		</table></div>
		<a class="noprint footer" href="#" onClick="window.print();return false"><?php print_string('print', 'block_progress'); ?></a>
		<?php
	} else {
		echo '<li>There are no students enrolled on your course.</li>';
	}
} else {
	error(get_string('notpermitted', 'block_progress'));
}
?>
</div>
</body>
</html>
