<?php
/**
 * This class is designed to show a student what progress they have made with their
 * 'courses'. It is designed to support the use of criteria-based assessment,
 * specifically the Pass/Merit/Distinction criteria used by exam boards such as
 * Edexcel in the UK.
 * Works in Moodle 1.9+
 *
 * there are certain steps required to get students' progress to display correctly.
 * please see the pdf walkthrough included in this folder (walkthrough.pdf)
 * briefly:
 *     	enable outcomes (done by the admin)
 *		set up a new site-wide scale '-,Y' called EDEXCEL (or a name of your choice)
 *		IN EACH COURSE
 *		add an outcome for each criteria, the first letter of the short name is 
 *		important (all pass criteria must start with a capital P, merit M, 
 *		distinction D).
 *
 * original author Mark J Tyers mark@zannet.co.uk * @version 23/07/08
 
 * UPDATE 03/07/08
 * if a grade has been achieved and progress is being made towards a higher grade
 * the full bar is faded out and a new bar overlayed to show the progress
 * towards this new higher grade.
 * bars now show true fraction (removed the initial increment from bar).
 *
 * TO DO
 *      allow the use of other citeria scales (set on the configuration page)
 *      implement a footer (or a choice of different footers)
 */
 
class block_progress extends block_base {
public function init() {
        $this->title = get_string('progress', 'block_progress');
        }
function instance_allow_config() {
    return true;
}

public function get_content() {
	if ($this->content !== NULL) {
		return $this->content;
	}
	$this->content = new stdClass;
	global $USER, $CFG, $COURSE;
        //Test link code
            $this->content->text   = '<center><a href="/vle/blocks/group_targets/group_select.php"><img src="/vle/blocks/group_targets/group-target.png"</a></center>';
            
            include_once('course_teacher.php');
			$content = new courseteacher($CFG, $USER, $this->config);
			$this->title = $content->get_title();
			$this->content->text = $content->get_body();
			$this->content->footer = $content->get_footer();
            
            
             return $this->content;
        
        
        
        
	if (strpos($CFG->pagepath, 'course')) {
		$context = get_context_instance(CONTEXT_COURSE, $COURSE->id);
		if (has_capability('mod/assignment:grade', $context)) {
			// they are a teacher
			include_once('course_teacher.php');
			$content = new courseteacher($CFG, $USER, $this->config);
			$this->title = $content->get_title();
			$this->content->text = $content->get_body();
			$this->content->footer = $content->get_footer();
			//$this->content->footer = 'test';
		} else {
			// they are a student
			if ($this->config->mode == 'tutor') {
				include_once('frontpage_student.php');
				$content = new frontpagestudent($CFG, $USER, $this->config);
				$this->title = $content->get_title();
				$this->content->text = $content->get_body();
				$this->content->footer = $content->get_footer();
			} else {
				include_once('course_student.php');
				$content = new coursestudent($CFG, $USER, $this->config);
				$this->title = $content->get_title();
				$this->content->text = $content->get_body();
				$this->content->footer = $content->get_footer();
			}
		}
	} else {
		include_once('frontpage_student.php');
		$content = new frontpagestudent($CFG, $USER, $this->config);
		$this->title = $content->get_title();
		$this->content->text = $content->get_body();
		$this->content->footer = $content->get_footer();
	}

        
	return $this->content;
}

}
?>