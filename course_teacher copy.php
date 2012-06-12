<?php
class courseteacher {
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
	var $achievement = 0;
	var $student_count = 0;
	var $achieved_count = 0;

function courseteacher($cfg, $u, $c) {
	$this->cfg = $cfg;
	$this->conf = $c;
	$this->user = $u;
	$this->debug = false;
	$this->coursename = 'shortname';
	if ($this->conf->debug == 'yes') $this->debug = true;
	if (strlen($this->conf->format)) $this->coursename = $this->conf->format;
	if (strlen($this->conf->none)) $this->none = $this->conf->none;
	if (strlen($this->conf->some)) $this->some = $this->conf->some;
	if (strlen($this->conf->all)) $this->all = $this->conf->all;
	if (strlen($this->conf->fade)) $this->fade = $this->conf->fade;
}

# ------------------------- RETRIEVES THE BLOCK TITLE --------------------------
function get_title() {
	if (strlen($this->conf->title)) {
		$title = $this->conf->title;
	} else {
		$title = get_string('progress', 'block_progress');
	}
	return $title;
}

# --------------------- RETRIEVES THE CONTENT OF THE BLOCK ---------------------
function get_body() {
	$setup = true;
	$courseid = $_GET['id'];
	$maxlength = 30;
	$data = '';
	$error_message = '';
	$letters = array('P', 'M', 'D');
	$course = get_record_select('course', 'id = '.$courseid);
	$grades = $this->get_grades($courseid);
	if (!$grades) $error_message .= '<p>'.get_string('outcomesmissing', 'block_progress').'</p>';
	$student_role=get_field('role','id','shortname','student');
	$content = '';

		$context = get_context_instance(CONTEXT_COURSE,$_GET['id']);
		
		$students = get_role_users($student_role, $context, true);
		$j = false;
		if (isset($this->conf->group)) $g = $this->conf->group;
		if ($this->conf->group == 'all') unset($group);
		if (isset($_GET['group'])) $g = $_GET['group'];
		if ($students) {
			if (isset($g)) {
				$members = get_records_select('groups_members', 'groupid='.$g);
				foreach($members as $m) {
					//echo '<p>'.$m->id.'</p>';
					$i = $m->userid;
					$member[$i] = true;
				}
			}
			if (!isset($_GET['group'])) $_GET['group'] = null;
			if ($_GET['group'] == 'all') $g = null;
			$data .= '<p style="margin:0; padding:0; text-align:center"><small>'.$this->generate_group_links($courseid, $g).'</small></p>';
			foreach($students as $student) {
				$stuid = $student->id;
				$achieved = $this->count_achieved($courseid, $student->id);
				//print_r($achieved);
				if (!$g || $member[$stuid]) {
					$this->student_count++;
					if ($achieved['P'] == $grades['P']) $this->achieved_count++;
					$title = $this->show_summary($grades, $achieved, $points);
					$url = $this->cfg->httpswwwroot.'/blocks/progress/summary_student_course.php?course='.$course->id.'&prefix='.$prefix.'&user='.$this->user->id.'&student='.$student->id.'&home=true';
       	    		$name = 'features';
					$features = 'width=800, height=600, scrollbars=yes, resizable=yes';
					$data .= '<p><a href="javascript:void(0)" onclick="window.open(\''.$url.'\', \''.$name.'\', \''.$features.'\')">'.strtoupper($student->lastname).' '.$student->firstname.'</a><br />';

       	    		$data .= '&nbsp;&nbsp;'.$this->build_bar($grades, $achieved);
       	    		if ($this->conf->debug == 'yes') {
       	    			$data .= '<br />&nbsp;&nbsp;<small>'.$this->show_summary($grades, $achieved, $points).'</small>';
       	    		}
       	    		$data .= '</p>';
       	    	}
            }
		} else {
			$error_message .= '<p>'.get_string('nostudents', 'block_progress').'</p>';;
		}
	if ($error_message) {
		return $error_message;
	} else {
		return $data;
	}
}

# ------------------------ RETRIEVES THE BLOCK FOOTER ------------------------
function get_footer() {
	if ($this->conf->footer != 'no') {
		$prefix = '';
		$string = get_string('achievement', 'block_progress');
		if (isset($this->conf->prefix)) $prefix = $this->conf->prefix;
		$pagename = 'summary_course.php';
		if ($this->conf->mode == 'tutor') $pagename = 'summary.php';
		$url = $this->cfg->httpswwwroot.'/blocks/progress/'.$pagename.'?course='.$_GET['id'].'&prefix='.$prefix.'&user='.$this->user->id.'&home=true';
		if ($this->conf->debug=='yes') $url .= '&debug=true';
		if ($this->conf->group > 0) $group = $this->conf->group;
		if (isset($_GET['group'])) $group = $_GET['group'];
		if (isset($_GET['group']) && $_GET['group'] == 'all') unset($group);
		if (isset($group)) $url .= '&group='.$group;
		$name = 'features';
		$features = 'width=800, height=600, scrollbars=yes, resizable=yes';
		$percent = 0;
		if ($this->student_count > 0) {
			$percent = round($this->achieved_count / $this->student_count * 100);
		}
		$message = '<a href="javascript:void(0)" onclick="window.open(\''.$url.'\', \''.$name.'\', \''.$features.'\')">'.$string.' '.$percent.'% <small>('.$this->achieved_count.'/'.$this->student_count.')</small></a>';
		return $message;
	} else {
		return null;
	}
}

function generate_group_links($courseid, $g) {
	$groups = get_records_select('groups', 'courseid='.$courseid, 'name', 'id, name');
	if (count($groups)) {
		$data = '';
		//$url = 'summary.php?course='.$courseid.'&prefix='.$_GET['prefix'].'&user='.$_GET['user'];
		$url = $this->cfg->wwwroot.'/course/view.php?id='.$courseid;
		//echo '<p>'.$url.'</p>';
		//echo '<pre>';
		//print_r($this->cfg);
		//echo '</pre>';
		$gr = array();
		if ($g != null) {
			array_push($gr, '<a href="'.$url.'&group=all">'.get_string('all', 'block_progress').'</a>');
		} else {
			array_push($gr, get_string('all', 'block_progress'));
		}
		foreach($groups as $group) {
			if ($group->id == $g) {
				array_push($gr, $group->name);
			} else {
				array_push($gr, '<a href="'.$url.'&group='.$group->id.'">'.$group->name.'</a>');
			}
		}
	}
	if (count($groups) > 1) {
		return implode(' | ', $gr);
	} else {
		return null;
	}
}

function get_points($idnumber) {
	$temp = explode('/', $idnumber);
	if (count($temp) == 3 && is_numeric($temp[0]) && is_numeric($temp[1]) && is_numeric($temp[2])) {
		$points['pass'] = $temp[0];
		$points['merit'] = $temp[1];
		$points['distinction'] = $temp[2];
	} else {
		$points = false;
	}
	unset($temp);
	return $points;
}

function get_total_points($g, $a, $p) {
	return $this->total;
}

function get_grades($id) { # retrieves the available p, m and d outcomes
	$letters = array('P', 'M', 'D'); # needs to be based on bands
	$grade = array();
	foreach($letters as $letter) {
			$temp = array();
			$sql = 'select a.shortname
				from '.$this->cfg->prefix.'grade_outcomes a, '.$this->cfg->prefix.'grade_outcomes_courses b
				where a.id = b.outcomeid
				and b.courseid='.$id.
				' and a.shortname like "'.$letter.'%";';
				//echo '<p>'.$sql.'</p>';
			$result = mysql_query($sql);
			$count = mysql_num_rows($result);
			//echo '<p>count: '.$count.'</p>';
			if ($count > 0) $grade[$letter] = $count;
	}
	//if (count($letters) == count($grade)) {
		return $grade;
	//} else {
	//	return false;
	//}
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
	$criteria = array();
	$q = get_records_select('quiz', 'course='.$courseid, '', 'id, sumgrades, grade');
	foreach ($q as $quiz) {
		$threshold = get_record_select('quiz_feedback', 'quizid='.$quiz->id.' and feedbacktext="PASS"', 'mingrade');
		$curr_score = get_record_select('quiz_grades', 'quiz='.$quiz->id.' and userid='.$userid.'', 'grade');
		if ($curr_score->grade >= $threshold->mingrade && $threshold->mingrade != null) {
			// User has PASSED this quiz!
			$crit = get_records_select('grade_items', 'courseid='.$courseid.' and itemmodule="quiz" and iteminstance='.$quiz->id, '', 'itemname');
			foreach ($crit as $c=>$d) if (substr($c, 0, 1) == 'P' || substr($c, 0, 1) == 'M' || substr($c, 0, 1) == 'D') {
				$criteria[$c] = true;
			}
		}
	}
	return $criteria;
}

function build_bar($g, $a) {
	$interval = 2.5;
	//$interval = 2;
	$maxbars = 100/$interval;
	$percent = array('P'=>0, 'M'=>0, 'D'=>0);
	if ($g['P'] > 0) $percent['P'] = ($a['P']/$g['P']) * 100;
	if ($g['M'] > 0) $percent['M'] = ($a['M']/$g['M']) * 100;
	if ($g['D'] > 0) $percent['D'] = ($a['D']/$g['D']) * 100;
	$num_bars = $this->bar_count($percent, $interval);
	$fill = $this->fill_count($percent, $num_bars, $maxbars);
	$bar = $bar2 = '';
	for ($i=0; $i<$num_bars; $i++) $bar .= '|';
	for ($i=0; $i<$fill; $i++) $bar2 .= '|';
	if ($bar == '') $bar = '|';
	$color = $this->some;
	if ($percent['P'] == 0) $color = $this->none;
	if ($percent['P'] == 100 && $percent['M'] == 0 && $percent['D'] == 0) $color = $this->all;
	if ($percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] == 0) $color = $this->all;
	if ($percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] == 100) $color = $this->all;
	$data = '<span style="color: '.$color.'">'.$bar.'</span>';
	if ($fill) $data .= '<span style="color: '.$this->fade.'">'.$bar2.'</span>';
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
	if ($g != null) {
		foreach($g as $letter=>$val) $percent[$letter] = ($a[$letter]/$g[$letter]) * 100;
		$grade = null;
		if ($a['P'] == $g['P']) {
			$grade = get_string('pass', 'block_progress');
			$points = $p['pass'];
			$this->achievement++;
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
			$towards = ' ('.get_string('merit', 'block_progress').' '.$a['M'].'/'.$g['M'].')';
		}
		if ($percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] > 0 && $percent['D'] < 100) {
			$towards = ' ('.get_string('distinction', 'block_progress').' '.$a['D'].'/'.$g['D'].')';
		}
		if ($percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] == 0) $towards = '';
		if ($percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] == 100) $towards = '';
	
		if (!$grade) $grade = $a['P'].'/'.$g['P'];
		$grade .= $towards;
		if ($points && $this->conf->debug == 'yes') $grade .= ' ('.$points.' '.get_string('points', 'block_progress').')';
		return $grade;
	} else {
		return null; // BUG FIX - prevents data being returned if the parameters are null
	}
}

}

?>