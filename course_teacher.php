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
	if (isset($this->conf->debug) && $this->conf->debug == 'yes') $this->debug = true;
	if (isset($this->conf->format) && strlen($this->conf->format)) $this->coursename = $this->conf->format;
	if (isset($this->conf->none) && strlen($this->conf->none)) $this->none = $this->conf->none;
	if (isset($this->conf->some) && strlen($this->conf->some)) $this->some = $this->conf->some;
	if (isset($this->conf->all) && strlen($this->conf->all)) $this->all = $this->conf->all;
	if (isset($this->conf->fade) && strlen($this->conf->fade)) $this->fade = $this->conf->fade;
}

# ------------------------- RETRIEVES THE BLOCK TITLE --------------------------
function get_title() {
	if (isset($this->conf->title) && strlen($this->conf->title)) {
		$title = $this->conf->title;
	} else {
		$title = get_string('progress', 'block_progress');
	}
	return $title;
}

# --------------------- RETRIEVES THE CONTENT OF THE BLOCK ---------------------
function get_body() {
        global $DB;
	global $COURSE;
	$data = '';
	$g = false;
	$setup = true;
	$courseid = $COURSE->id;
	$maxlength = 30;
	$data = '';
	$error_message = '';
	$letters = array('P', 'M', 'D');
	$course = $DB->get_record_select('course', 'id = '.$courseid);
	$grades = $this->get_grades($courseid);
	//print_r($grades);
	if (isset($this->conf->mode) && $this->conf->mode == 'tutor') {
		//$error_message .= '&nbsp;';
		$error_message .= $this->display_tutor_link();
	} else if (!$grades) {
		$error_message .= '<p>'.get_string('outcomesmissing', 'block_progress').'</p>';
	}
        $student_role=$DB->get_field('role','id',array('shortname'=>'student'));
	$content = '';

		$context = get_context_instance(CONTEXT_COURSE,$COURSE->id);
		
		$students = get_role_users($student_role, $context, true);
		$j = false;
		if (isset($this->conf->group)) $g = $this->conf->group;
		if ( isset($this->course->group) && $this->conf->group == 'all') unset($group);
		if (isset($_GET['group'])) $g = $_GET['group'];
		if ($students) {
			if (isset($g) && $g != null && $g != 'all') {
				$members = $DB->get_records_select('groups_members', 'groupid='.$g);
				//print_r($members);
				if ( $members != null ) {
					foreach($members as $m) {
					//echo '<p>'.$m->id.'</p>';
					$i = $m->userid;
					$member[$i] = true;
				}
				}
			}
			if (!isset($_GET['group'])) $_GET['group'] = null;
			if ($_GET['group'] == 'all') $g = null;
			$data .= '<p style="margin:0; padding:0; text-align:center"><small>'.$this->generate_group_links($courseid, $g).'</small></p>';
			foreach($students as $student) {
				$stuid = $student->id;
				$achieved = $this->count_achieved($courseid, $student->id);
				//print_r($achieved);
				if (!$g || array_key_exists($stuid, $member)) {
					$this->student_count++;
					if ( !isset($points) ) $points = 0;
					if ( array_key_exists('P', $grades) && $achieved['P'] == $grades['P']) $this->achieved_count++;
					$title = $this->show_summary($grades, $achieved, $points);
					$url = $this->cfg->httpswwwroot.'/blocks/progress/summary_student_course.php?course='.$course->id;
					if (isset($prefix) ) $url .= '&prefix='.$prefix;
					$url .= '&user='.$this->user->id.'&student='.$student->id.'&home=true';
       	    		$name = 'features';
					$features = 'width=800, height=600, scrollbars=yes, resizable=yes';
					$data .= '<p style="margin: 0; padding: 0; line-height: 80%; margin-bottom: 5px; font-size: 90%">'.strtoupper($student->lastname).' '.ucfirst(strtolower($student->firstname)).'<br />';
					//$data .= '<p style="margin: 0; padding: 0; line-height: 80%; margin-bottom: 5px; font-size: 90%"><a href="javascript:void(0)" onclick="window.open(\''.$url.'\', \''.$name.'\', \''.$features.'\')">'.strtoupper($student->lastname).' '.$student->firstname.'</a><br />';

       	    		$data .= $this->build_bar($grades, $achieved);
       	    		if (isset($this->conf->debug) && $this->conf->debug == 'yes') {
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

function display_tutor_link() {
	global $COURSE;
	if (isset($this->conf->prefix)) $prefix = $this->conf->prefix;
	$url = $this->cfg->httpswwwroot.'/blocks/progress/summary.php?course='.$COURSE->id.'&prefix='.$prefix.'&user='.$this->user->id.'&home=true';
	if ($this->conf->debug=='yes') $url .= '&debug=true';
	if (isset($this->conf->group) && $this->conf->group > 0) $group = $this->conf->group;
	if (isset($_GET['group'])) $group = $_GET['group'];
	if (isset($_GET['group']) && $_GET['group'] == 'all') unset($group);
	if (isset($group)) $url .= '&group='.$group;
	$name = 'features';
	$features = 'width=800, height=600, scrollbars=yes, resizable=yes';
	$message = '<a href="javascript:void(0)" onclick="window.open(\''.$url.'\', \''.$name.'\', \''.$features.'\')">'.get_string('showtracking', 'block_progress').'</a>';
        return $message;
}

function display_student_link() {
	global $COURSE;
	if (isset($this->conf->prefix)) $prefix = $this->conf->prefix;
//	$url = $this->cfg->httpswwwroot.'/blocks/progress/summary_student.php?course=&prefix='.$prefix.'&user='.$this->user->id.'&home=true';
	$url = $this->cfg->httpswwwroot.'/blocks/progress/summary_student.php?student='.$this->user->id.'&user='.$this->user->id;        
 
        
	if ($this->conf->debug=='yes') $url .= '&debug=true';
	if (isset($this->conf->group) && $this->conf->group > 0) $group = $this->conf->group;
	if (isset($_GET['group'])) $group = $_GET['group'];
	if (isset($_GET['group']) && $_GET['group'] == 'all') unset($group);
	if (isset($group)) $url .= '&group='.$group;
	$name = 'features';
	$features = 'width=800, height=600, scrollbars=yes, resizable=yes';
	$message = '<a href="javascript:void(0)" onclick="window.open(\''.$url.'\', \''.$name.'\', \''.$features.'\')">'.get_string('showtracking', 'block_progress').'</a>';
	return $message;
}


# ------------------------ RETRIEVES THE BLOCK FOOTER ------------------------
function get_footer() {
	global $COURSE;
	if ( isset($this->conf->footer) && isset($this->conf->mode) && $this->conf->footer != 'no' && $this->conf->mode != 'tutor') {
		$prefix = '';
		$string = get_string('achievement', 'block_progress');
		if (isset($this->conf->prefix)) $prefix = $this->conf->prefix;
		else $prefix = '';
		$pagename = 'summary_course.php';
		if ($this->conf->mode == 'tutor') $pagename = 'summary.php';
		$url = $this->cfg->httpswwwroot.'/blocks/progress/'.$pagename.'?course='.$COURSE->id.'&prefix='.$prefix.'&user='.$this->user->id.'&home=true';
		if ($this->conf->debug=='yes') $url .= '&debug=true';
		if ( isset($this->conf->group) && $this->conf->group > 0 ) $group = $this->conf->group;
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
        global $DB;
	$groups = $DB->get_records_select('groups', 'courseid='.$courseid, array('name', 'id', 'name'));
	if ( $groups != null ) {
		$data = '';
		$url = $this->cfg->wwwroot.'/course/view.php?id='.$courseid;
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
	if (count($groups) > 0 && isset($gr)) {
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
        include '/protected/dbcred.php'; 
        mysql_connect($host, $user, $pass);
        mysql_select_db($db);
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
	//print_r($a);
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
	//print_r($q);
	if ($q) {
		foreach ($q as $quiz) {
		$threshold = $DB->get_record_select('quiz_feedback', 'quizid='.$quiz->id, array('feedbacktext="PASS"', 'mingrade'));
		$curr_score = $DB->get_record_select('quiz_grades', 'quiz='.$quiz->id, array('userid='.$userid, 'grade'));
		//echo '<p>curr_score-&gt;grade '.$curr_score->grade.'</p>';
		//echo '<p>threshold-&gt;mingrade '.$threshold->mingrade.'</p>';
		if ( isset($curr_score->grade) ) {
			//echo '<p>currscore - grade exists!</p>';
			if ($curr_score->grade >= $threshold->mingrade && $threshold->mingrade != null) {
			// User has PASSED this quiz!
			//echo '<p>PASSED!</p>';
			$crit = $DB->get_records_select('grade_items', 'courseid='.$courseid, array('itemmodule="quiz"','iteminstance='.$quiz->id, '', 'itemname'));
			//print_r($crit);
			foreach ($crit as $c=>$d) if (substr($c, 0, 1) == 'P' || substr($c, 0, 1) == 'M' || substr($c, 0, 1) == 'D') {
				$criteria[$c] = true;
			}
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
	$data = ' ';
	$letters = array('P', 'M', 'D');
	foreach ($letters as $letter) {
		$bars[$letter] = 0;
		if ( array_key_exists($letter, $g) && $g[$letter] > 0) $percent[$letter] = ($a[$letter]/$g[$letter]) * 100;
		if ($a[$letter] > 0) $bars[$letter] = round($percent[$letter] / $interval);
		if ( $bars[$letter]==0 ) $bars[$letter]=1;
		$color = 'gray';
		if ($percent[$letter] == 0) $color = 'red';
		if ($percent[$letter] == 100) $color = 'green';
		if ( array_key_exists($letter, $g) ) {
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
	if ($g != null) {
		foreach($g as $letter=>$val) $percent[$letter] = ($a[$letter]/$g[$letter]) * 100;
		$grade = null;
		if ($a['P'] == $g['P']) {
			$grade = get_string('pass', 'block_progress');
			$points = $p['pass'];
			$this->achievement++;
			if (array_key_exists('M', $g) && $a['M'] == $g['M']) {
				$grade = get_string('merit', 'block_progress');
				$points = $p['merit'];
				if ($a['D'] == $g['D']) {
					$grade = get_string('distinction', 'block_progress');
					$points = $p['distinction'];
				}
			}
		}
		if ( isset($points) ) $this->total += $points;
		//echo '<p>points so far: '.$this->total.'</p>';
		$towards = '';
		if (array_key_exists('M', $percent) && array_key_exists('M', $percent) && $percent['P'] == 100 && $percent['M'] > 0 && $percent['M'] < 100) {
			$towards = ' ('.get_string('merit', 'block_progress').' '.$a['M'].'/'.$g['M'].')';
		}
		if (array_key_exists('M', $percent) && array_key_exists('M', $percent) && $percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] > 0 && $percent['D'] < 100) {
			$towards = ' ('.get_string('distinction', 'block_progress').' '.$a['D'].'/'.$g['D'].')';
		}
		if (array_key_exists('M', $percent) && array_key_exists('D', $percent) && $percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] == 0) {
			$towards = '';
		}
		if (array_key_exists('M', $percent) && array_key_exists('D', $percent) && $percent['P'] == 100 && $percent['M'] == 100 && $percent['D'] == 100) {
			$towards = '';
		}
	
		if (!$grade) $grade = $a['P'].'/'.$g['P'];
		$grade .= $towards;
		if ( isset($points) && $points && $this->conf->debug == 'yes') $grade .= ' ('.$points.' '.get_string('points', 'block_progress').')';
		return $grade;
	} else {
		return null; // BUG FIX - prevents data being returned if the parameters are null
	}
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

}

?>
