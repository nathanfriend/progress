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
 function init() {
        $this->title = get_string('progress', 'block_progress');
        }
function instance_allow_config() {
    return true;
}








function get_content() {
    	global $CFG, $USER, $SITE, $COURSE;
	if ($this->content !== NULL) {
		return $this->content;
	}
        
        
	$this->content = new stdClass;
	global $USER, $CFG, $COURSE;
            include_once('course_teacher.php');
           
            
			$content = new courseteacher($CFG, $USER, $this->config);
			$this->title = $content->get_title();
			$this->content->text = $content->get_body();
			$this->content->footer = $content->get_footer();
                        
        // get the proper context 
        $context2 = get_context_instance(CONTEXT_COURSE, $COURSE->id);
                        
        //only display the block if the users is a teacher - ie can update the course
        if (has_capability('moodle/course:update', $context2)) { $this->content->text = $content->display_tutor_link(); } else { $this->content->text = $content->display_student_link();}
            
             return $this->content;

}

}
?>



