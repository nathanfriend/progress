<?php
class frontpagestudent {
	var $cfg;
	var $user;
	var $conf;
	var $debug;
	var $coursename;
	var $generated = false;
	var $glyph = 'Y';
	var $none = 'red';
	var $some = 'black';
	var $all = 'green';
	var $fade = 'lightgreen';
	var $total = 0;
	var $numcourses = 0;

function frontpagestudent($cfg, $u, $c) {
	$this->cfg = $cfg;
	$this->conf = $c;
	$this->user = $u;
	$this->debug = false;
	$this->coursename = 'shortname';
	if (isset($this->conf->debug) && $this->conf->debug == 'yes') $this->debug = true;
	if (isset($this->conf->format) && strlen($this->conf->format)) $this->coursename = $this->conf->format;
	if (isset($this->conf->none) && strlen($this->conf->none)) $this->none = $this->conf->none;
	if (isset($this->conf->some) && strlen($this->conf->some)) $this->some = $this->conf->some;
	if (isset($this->conf->all) && strlen($this->conf->all)) $this->all = $this->conf->all;
	if (isset($this->conf->fade) && strlen($this->conf->fade)) $this->fade = $this->conf->fade;
}

function get_points($idnumber) {
	$temp = explode('/', $idnumber);
	if (count($temp) == 3 && is_numeric($temp[0]) && is_numeric($temp[1]) && is_numeric($temp[2])) {
		$points['pass'] = $temp[0];
		$points['merit'] = $temp[1];
		$points['distinction'] = $temp[2];
		unset($temp);
	return $points;
	} else {
		return null;
	}
}

function get_body() {
        global $DB;
	$maxlength = 30;
	$data = '';
	$letters = array('P', 'M', 'D');
        $student_role=$DB->get_field('role', 'id', array('shortname'=>'student'));
	$content = '';
	$courses = enrol_get_users_courses($this->user->id, 'sortorder');
	foreach($courses as $course) {
		//echo '<h4>'.$course->fullname.'</h4>';
		$points = $this->get_points($course->idnumber);
		$context = get_context_instance(CONTEXT_COURSE, $course->id);
		$students = get_role_users($student_role, $context, true);
		$j = false;
		if ($students) {
			foreach($students as $student) {
       	    	if ($student->id == $this->user->id) {
                	$j = true;
                }
            }
		}
		if ($j == true) { // this student is on this course!
			$this->numcourses++;
			$grade = $this->get_grades($course->id);
			$achieved = $this->count_achieved($course->id, $this->user->id);
			//$achieved = $this->get_achieved($course->id);
			if ($grade['P'] > 0) {
				$url  = $this->cfg->httpswwwroot.'/blocks/progress/summary_student_course.php?course='.$course->id;
				if (array_key_exists('prefix', $_GET) ) $url .= '&prefix='.$_GET['prefix'];
				$url .= '&user='.$this->user->id.'&student='.$this->user->id.'&home=true';
				if ( isset($this->conf->debug) && $this->conf->debug=='yes') $url .= '&debug=true';
				$name = 'features';
				$features = 'width=800, height=600, scrollbars=yes, resizable=yes';
				$data .= '<p style="margin: 0; padding: 0; line-height: 80%; margin-bottom: 5px; font-size: 90%">'.shorten_text($course->fullname, $maxlength);
				//$data .= '<p style="margin: 0; padding: 0; line-height: 80%; margin-bottom: 5px; font-size: 90%"><a href="javascript:void(0)" onclick="window.open(\''.$url.'\', \''.$name.'\', \''.$features.'\')">'.shorten_text($course->fullname, $maxlength).'</a>';
				//$data .= '<a href="grade/report/user/index.php?id='.$course->id.'">'.shorten_text($course->fullname, $maxlength).'</a>';
				$data .= '<br />'.$this->build_bar($grade, $achieved);
				if ( isset($this->conf->debug) && $this->conf->debug == 'yes') {
       	    			$data .= '<br />&nbsp;&nbsp;<small>'.$this->show_summary($grades, $achieved, $points).'</small>';
       	    		}
				$data .= '</p>';
			}
		}
	}
	if ($data) {
		$url  = $this->cfg->httpswwwroot.'/blocks/progress/summary_student.php?student='.$this->user->id.'&user='.$this->user->id;
		if (array_key_exists('prefix', $_GET) ) $url .= '&prefix='.$_GET['prefix'];
		$name = 'features';
		$features = 'width=800, height=600, scrollbars=yes, resizable=yes';
		$message = get_string('detail', 'block_progress');
		$message = '<a href="javascript:void(0)" onclick="window.open(\''.$url.'\', \''.$name.'\', \''.$features.'\')">'.$message.'</a>';
		$link = '<p class="detail" style="margin-bottom: 0; text-align: center;">'.$message.'</p>';
		$data = $data.$link;
	}
	return $data;
}

function get_total_points() {
	return $this->total;
}

function get_grades($id) {
	$letters = array('P', 'M', 'D'); # needs to be based on bands
	foreach($letters as $letter) {
			$temp = array();
			$sql = 'select a.shortname
				from '.$this->cfg->prefix.'grade_outcomes a, '.$this->cfg->prefix.'grade_outcomes_courses b
				where a.id = b.outcomeid
				and b.courseid='.$id.
				' and a.shortname like "'.$letter.'%";';
        include '/moodledata/progress/dbcred.php'; 
        mysql_connect($host, $user, $pass);
        mysql_select_db($db);                
			$result = mysql_query($sql);
			$count = mysql_num_rows($result);
			$grade[$letter] = $count;
	}
	return $grade;
}

function count_achieved($courseid, $userid) {
	$all = array();
	$grades = array('P'=>0, 'M'=>0, 'D'=>0);
	$a = $this->get_achieved_assignment($courseid, $userid);
	foreach ($a as $key=>$val) {
		$l = substr($key, 0, 1);
		$all[$l][$key] = true;
	}
	$a = $this->get_achieved_quiz($courseid, $userid);
	foreach ($a as $key=>$val) {
		$l = substr($key, 0, 1);
		$all[$l][$key] = true;
	}
	foreach ($all as $key=>$val) {
		if (count($val)) $grades[$key] = count($val);
	}
	return $grades;
}

function get_achieved_assignment($courseid, $userid) {
	$criteria = array();
	$sql = 'select a.itemname from '.$this->cfg->prefix.'grade_items a, '.$this->cfg->prefix.'grade_grades b
			where a.id = b.itemid
			and a.grademax = b.finalgrade
			and a.itemmodule = "assignment"
			and b.userid='.$userid.'
			and a.courseid='.$courseid.';';
	$result = mysql_query($sql);
	while ($row = mysql_fetch_assoc($result)) {
		$c = $row['itemname'];
		$criteria[$c] = true;
	}
	return $criteria;
}

function get_achieved_quiz($courseid, $userid) {
        global $DB;
	$criteria = array();
	$q = $DB->get_records_select('quiz', 'course='.$courseid, array('id', 'sumgrades', 'grade'));
	if ($q != null) {
		foreach ($q as $quiz) {
		$threshold = $DB->get_record_select('quiz_feedback', 'quizid='.$quiz->id, array('feedbacktext="PASS"', 'mingrade'));
		$curr_score = $DB->get_record_select('quiz_grades', 'quiz='.$quiz->id, array('userid='.$userid.'', 'grade'));
		if ($curr_score->grade >= $threshold->mingrade && $threshold->mingrade != null) {
			// User has PASSED this quiz!
			$crit = $DB->get_records_select('grade_items', 'courseid='.$courseidm, array('itemmodule="quiz"','iteminstance='.$quiz->id, '', 'itemname'));
			foreach ($crit as $c=>$d) if (substr($c, 0, 1) == 'P' || substr($c, 0, 1) == 'M' || substr($c, 0, 1) == 'D') {
				$criteria[$c] = true;
			}
		}
	}
	}
	return $criteria;
}

function build_bar($g, $a) {
	//$interval = 2.5;
	$interval = 1.4;
	$maxbars = 100/$interval;
	$percent = array('P'=>0, 'M'=>0, 'D'=>0);
	$num_bars = array('P'=>1, 'M'=>1, 'D'=>1);
	$data = '';
	
	$letters = array('P', 'M', 'D');
	foreach ($letters as $letter) {
		if ($g[$letter] > 0) $percent[$letter] = ($a[$letter]/$g[$letter]) * 100;
		if ($a[$letter] > 0) $bars[$letter] = round($percent[$letter] / $interval);
		if ( !array_key_exists($letter, $bars) ) $bars[$letter]=1;
		$color = 'gray';
		if ($percent[$letter] == 0) $color = 'red';
		if ($percent[$letter] == 100) $color = 'green';
		if ( $g[$letter] ) {
			if ($letter != 'P') $data .= '<br style="margin: 0; padding: 0" />';
			$data .= '<span style="font-size: 50%; font-family: monaco, monospace">'.$letter.' </span>';
			$data .= '<span title="'.$a[$letter].'/'.$g[$letter].'" style="font-size: 50%; color: '.$color.'; background-color: '.$color.'">';
			for ($i=0; $i<$bars[$letter]; $i++) $data .= '|';
			$data .= '</span>';
		}
	}
	return $data;
}

function bar_count($p, $i) {
	if ($p['P'] < 100) {
		$num_bars = round($p['P'] / $i);
	}
	if ($p['P'] == 100 && $p['M'] == 0) {
		$num_bars = round($p['P'] / $i);
	}
	if ($p['P'] == 100 && $p['M'] > 0 && $p['M'] < 100) {
		$num_bars = round($p['M'] / $i);
	}
	if ($p['P'] == 100 && $p['M'] == 100 && $p['D'] == 0) {
		$num_bars = round($p['M'] / $i);
	}
	if ($p['P'] == 100 && $p['M'] == 100 && $p['D'] > 0) {
		$num_bars = round($p['D'] / $i);
	}
	return $num_bars;
}

function fill_count($p, $n, $m) {
	if ($p['P'] < 100) {
		$fill = 0;
	}
	if ($p['P'] == 100 && $p['M'] == 0) {
		$fill = 0;
	}
	if ($p['P'] == 100 && $p['M'] > 0 && $p['M'] < 100) {
		$fill = $m - $n;
	}
	if ($p['P'] == 100 && $p['M'] == 100 && $p['D'] == 0) {
		$fill = $m - $n;
	}
	if ($p['P'] == 100 && $p['M'] == 100 && $p['D'] > 0) {
		$fill = $m - $n;
	}
	return $fill;
}

function show_summary($g, $a, $p) {
	foreach($g as $letter=>$val) $percent[$letter] = ($a[$letter]/$g[$letter]) * 100;
	$grade = null;
	if ($a['P'] == $g['P']) {
		$grade = get_string('pass', 'block_progress');
		$points = $p['pass'];
		if ($a['M'] == $g['M']) {
			$grade = get_string('merit', 'block_progress');
			$points = $p['merit'];
			if ($a['D'] == $g['D']) {
				$grade = get_string('distinction', 'block_progress');
				$points = $p['distinction'];
			}
		}
	}
	$this->total += $points;
	//echo '<p>points so far: '.$this->total.'</p>';
	$towards = '';
	if ($percent['P'] == 100 && $percent['M'] > 0 && $percent['M'] < 100) {
		$towards = ' <small><small>('.get_string('merit', 'block_progress').' '.$a['M'].'/'.$g['M'].')</small></small>';
	}
	if ($percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] > 0 && $percent['D'] < 100) {
		$towards = ' <small><small>('.get_string('distinction', 'block_progress').' '.$a['D'].'/'.$g['D'].')</small></small>';
	}
	if ($percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] == 0) $towards = '';
	if ($percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] == 100) $towards = '';
	
	if (!$grade) $grade = $a['P'].'/'.$g['P'];
	$grade .= $towards;
	if ($points && $this->conf->debug == 'yes') $grade .= '<small><small> ('.$points.' '.get_string('points', 'block_progress').')</small></small>';
	return $grade;
}

function get_title() {
	if (isset($this->conf->title) && strlen($this->conf->title)) {
		$title = $this->conf->title;
	} else {
		$title = get_string('progress', 'block_progress');
	}
	return $title;
}

function get_footer() {
	if ($this->get_total_points() > 0 && $this->conf->footer == 'yes') {
		$total_points = $this->get_total_points();
		//$url = $this->cfg->httpswwwroot.'/blocks/progress/summary_student.php?prefix='.$prefix.'&student='.$this->user->id.'&user='.$this->user->id;
		//$name = 'features';
		//$features = 'width=800, height=600, scrollbars=yes, resizable=yes';
		$message = get_string('pointsachieved', 'block_progress');
		$message = str_replace('{points}', $total_points, $message);
		//$message = '<a href="javascript:void(0)" onclick="window.open(\''.$url.'\', \''.$name.'\', \''.$features.'\')">'.$message.'</a>';
		return $message;
	} else {
		return null;
	}
}

}

?>
