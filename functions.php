<?php
function generate_group_links($courseid, $page) {
        global $DB;
	$groups = $DB->get_records_select('groups', 'courseid='.$courseid, array('name', 'id, name'));
	if (count($groups)) {
		$data = '';
		$url = $page.'?course='.$courseid.'&prefix='.$_GET['prefix'].'&user='.$_GET['user'];
		$gr = array();
		if (isset($_GET['group'])) {
			array_push($gr, '<a href="'.$url.'">'.get_string('all', 'block_progress').'</a>');
		} else {
			array_push($gr, get_string('all', 'block_progress'));
		}
		if ( $groups != null ) {
			foreach($groups as $group) {
			if ( array_key_exists('group', $_GET) && $group->id == $_GET['group']) {
				array_push($gr, $group->name);
			} else {
				array_push($gr, '<a href="'.$url.'&group='.$group->id.'">'.$group->name.'</a>');
			}
		}
		}
	}
	return implode(' | ', $gr);
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
        include '/protected/dbcred.php'; 
        mysql_connect($host, $user, $pass);
        mysql_select_db($db);
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
function outcome_achieved($prefix, $course, $student, $outcome) {
	$quiz = quiz_outcome_achieved($prefix, $course, $student, $outcome);
	if ($quiz) return true;
	$sql = 'SELECT b.itemname
			FROM '.$prefix.'grade_grades a, '.$prefix.'grade_items b, '.$prefix.'grade_outcomes c
			WHERE a.itemid = b.id
			AND b.outcomeid = c.id
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
        global $DB;
	$quizzes = $DB->get_records_select('quiz', 'course='.$course, array('id'));
	foreach($quizzes as $q) {
		$threshold = $DB->get_field_select('quiz_feedback', 'mingrade', array('feedbacktext="PASS"'));
		$grade = $DB->get_field_select('quiz_grades', 'grade', array('quiz='.$q->id.' userid='.$student));
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
function uses_outcomes($prefix, $courseid) {
	$grades = get_possible_criteria($prefix, $courseid);
	if ($grades['P'] && $grades['M'] && $grades['D']) return true;
	return false;
}
?>
<?php
function get_students($courseid, $prefix, $group=null) {
        global $DB;
	if ($group != null) {
		$members = $DB->get_records_select('groups_members', 'groupid='.$group);
		foreach($members as $m) {
			$i = $m->userid;
			$member[$i] = true;
		}
	}
        $student_role=$DB->get_field('role','id',array('shortname'=>'student'));
	$context = get_context_instance(CONTEXT_COURSE,$courseid);
	$students = get_role_users($student_role, $context, true);
	if ($group != null) {
		foreach ($students as $student) {
			if ( isset($member[$student->id]) ) $checked[$student->id] = $student;
		}
		return $checked;
	}
	return $students;
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
function display_key($prefix, $courseid, $criteria) {
	$data = '<div id="key" class="printonly">';
	$data .= '<h2>Criteria</h2>';
	$data .= '<dl>';
	foreach ($criteria as $letter=>$crit) {
		foreach ($crit as $c) {
			$data .= '<dt>'.$letter.$c.'</td>'."\n";
			$data .= '<dd>'.get_description($prefix, $courseid, $letter.$c).'</dd>'."\n";
		}
	}
	$data .= '</dl></div>';
	return $data;
}
?>
<?php
/* checks to see if any criteria exist that begin with the supplied letter */
function criteria_exist($possible, $letter) {
	//print_r($possible);
	$crit = 0;
	foreach ($possible as $key=>$val) {
		if ( substr($key, 0, 1) == $letter ) $crit++;
	}
	if ($crit==0) return false;
	return true;
}
?>