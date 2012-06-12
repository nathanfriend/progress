<?php
class coursestudent {
	var $cfg;
	var $conf;
	var $user;
	var $course;
	var $grade;

function coursestudent($cfg, $u, $c) {
	$this->cfg = $cfg;
	$this->conf = $c;
	$this->user = $u;
	$this->course = $_GET['id'];
}

function get_body() {
	?>
	<style type="text/css">
		.notachieved, .achieved {
			padding: 5px;
			border: 1px solid black;
		}
		.achieved {
			background-color: green;
		}
	</style>
	<?php
        global $DB;
	$course = $DB->get_record_select('course', 'id = '.$this->course, array('id', 'shortname', 'fullname'));
	$possible = $this->get_possible_criteria($this->cfg->prefix, $course->id);
	$poss_by_level = $this->get_possible_by_level($course->id);
	$achieved = $this->get_achieved($this->cfg->prefix, $this->user->id, $course->id);
	$this->grade = $this->get_grade($possible, $achieved);
	$max = max($poss_by_level);
	$okP = $this->criteria_exist($possible, 'P');
	$okM = $this->criteria_exist($possible, 'M');
	$okD = $this->criteria_exist($possible, 'D');
	$c = 0;
	if ($okP) $c++;
	if ($okM) $c++;
	if ($okD) $c++;
	$data = '<table style="text-align: center; margin: auto; border-collapse: collapse;">';
	if ($c > 1) {
		$data .= '<tr>';
		if ($okP) $data .= '<th title="Pass">P</th>';
		if ($okM) $data .= '<th>&nbsp;</th><th title="Merit">M</th>';
		if ($okD) $data .= '<th>&nbsp;</th><th title="Distinction">D</th>';
		$data .= '</tr>';
	}
	foreach($max as $key=>$val) {
		$data .= '<tr>';
		foreach(array('P', 'M', 'D') as $letter) {
			$ok = $this->criteria_exist($possible, $letter);
			if ($ok) {
				if ($letter != 'P') $data .= '<td>&nbsp;</td>';
				if (count($poss_by_level[$letter])) {
					$l = array_shift($poss_by_level[$letter]);
					$description = $DB->get_field_select('grade_outcomes', 'description', array('shortname="'.$l->shortname.'"','courseid='.$this->course));
					//echo '<p>'.$description.'</p>';
					if (isset($achieved[$l->shortname])) { // achieved!
						$data .= '<td title="'.$description.'" class="achieved"><strong>'.$l->shortname.'</strong></td>';
					} else {
						$data .= '<td title="'.$description.'" class="notachieved">'.$l->shortname.'</td>'; // not achieved!
					}
				} else { // not available!
					$data .= '<td>&nbsp;</td>';
				}
			}
		}
	}
	$data .= '</table>';
	// check to see of there are any pass criteria assigned to this course...
	$ok = $this->criteria_exist($possible, 'P');
	if ( $ok ) return $data;
	return null;
}

function get_title() {
	if (strlen($this->conf->title)) {
		$title = $this->conf->title;
	} else {
		$title = get_string('progress', 'block_progress');
	}
	return $title;
}

function get_footer() {
	return $this->grade;
}

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

function get_possible_by_level($course) {
        global $DB;
	$temp = $DB->get_records_select('grade_outcomes', 'courseid='.$course, array('shortname', 'description'));
	$possible = array();
	foreach ($temp as $item) {
		$firstchar = strtoupper(substr($item->shortname, 0, 1));
		if ($firstchar=='P' || $firstchar=='M' || $firstchar=='D') {
			$possible[$firstchar][$item->shortname] = $item;
		}
	}
	return $possible;
}

function get_possible_criteria($prefix, $course) {
	$letters = array('P', 'M', 'D'); # needs to be based on bands
	$possible = array();
	foreach($letters as $letter) {
		$sql = 'select a.shortname, a.description
				from '.$prefix.'grade_outcomes a, '.$prefix.'grade_outcomes_courses b
				where a.id = b.outcomeid
				and b.courseid='.$course.
				' and a.shortname like "'.$letter.'%";';
		$result = mysql_query($sql);
		while ($row = mysql_fetch_assoc($result)) {
			$s = $row['shortname'];
			$possible[$s] = $row['description'];
		}
	}
	return $possible;
}

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

function get_achieved($prefix, $userid, $courseid) {
	$all = array();
	$grades = array('P'=>0, 'M'=>0, 'D'=>0);
	$a = $this->get_achieved_assignment($prefix, $courseid, $userid);
	foreach ($a as $key=>$val) {
		$all[$key] = true;
	}
	$a = $this->get_achieved_quiz($courseid, $userid);
	foreach ($a as $key=>$val) {
		$all[$key] = true;
	}
	return $all;
}
/*  FIXED MJT  */
function get_achieved_assignment($prefix, $courseid, $userid) {
	$criteria = array();
	$sql = 'select c.shortname
			from '.$prefix.'grade_items a, '.$prefix.'grade_grades b, '.$prefix.'grade_outcomes c
			where a.id = b.itemid
			and a.outcomeid = c.id
			and a.grademax = b.finalgrade
			and a.itemmodule = "assignment"
			and b.userid='.$userid.'
			and a.courseid='.$courseid.';';
	$result = mysql_query($sql);
	while ($row = mysql_fetch_assoc($result)) {
		$c = $row['shortname'];
		$criteria[$c] = true;
	}
	return $criteria;
}
/*  FIXED MJT  */
function get_achieved_quiz($courseid, $userid) {
        global $DB;
	$prefix = $this->cfg->prefix;
	$criteria = array();
	$q = $DB->get_records_select('quiz', 'course='.$courseid, array('id, sumgrades, grade'));
	foreach ($q as $quiz) { //loops through all quizzes in course
		$threshold = $DB->get_record_select('quiz_feedback', 'quizid='.$quiz->id,array('feedbacktext="PASS"', 'mingrade'));
		$curr_score = $DB->get_record_select('quiz_grades', 'quiz='.$quiz->id,array('userid='.$userid.'', 'grade'));
		if (isset($curr_score->grade) && $curr_score->grade >= $threshold->mingrade && $threshold->mingrade != null) {
			// User has PASSED this quiz!
			$sql = 'SELECT b.shortname 
					FROM '.$prefix.'grade_items a, '.$prefix.'grade_outcomes b 
					WHERE a.outcomeid = b.id 
					AND a.courseid='.$courseid.' 
					AND a.itemmodule="quiz" 
					AND a.iteminstance='.$quiz->id.';';
			$result = mysql_query($sql);
			while ( $row = mysql_fetch_assoc($result) ) {
				$c = $row['shortname'];
				if (substr($c, 0, 1) == 'P' || substr($c, 0, 1) == 'M' || substr($c, 0, 1) == 'D') {
					$criteria[$c] = true;
				}
			}
		}
	}
	return $criteria;
}

function get_grade($possible, $achieved) {
	$okP = $this->criteria_exist($possible, 'P');
	$okM = $this->criteria_exist($possible, 'M');
	$okD = $this->criteria_exist($possible, 'D');
	//if ( !$ok ) return null;
	//if ($p==0) return null; //there are no pass criteria assigned to this course!
	$available = array();
	$acquired = array();
	foreach($possible as $key=>$val) {
		$letter = substr($key, 0, 1);
		if ( !array_key_exists($letter, $available) ) $available[$letter] = 0;
		$available[$letter]++;
		if ( !array_key_exists($letter, $acquired) ) $acquired[$letter] = 0;
		if ( array_key_exists($key, $achieved) ) $acquired[$letter]++;
	}
	$grade = null;
	if ($acquired['P']==$available['P'] && $okP) $grade = "PASS";
	if ( array_key_exists('M', $available) && $acquired['M']==$available['M'] && $okM) $grade = "MERIT";
	if ( array_key_exists('D', $available) && $acquired['D']==$available['D'] && $okD) $grade = "DISTINCTION";
	return $grade;
}

}
?>