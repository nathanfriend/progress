<?php
 
class block_progress_edit_form extends block_edit_form {
 
    protected function specific_definition($mform) {
    require_once($base_url.'/srv/www/htdocs/config.php');
    
    // Adds Block settings section
    $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

    // Adds block name field.
    $mform->addElement('text', 'config_title', get_string('blocktitletext', 'block_progress'));
    $mform->setDefault('config_title', 'default value');
    $mform->setType('config_title', PARAM_MULTILANG);        

    //Adds footer field
    $options = array('no'=>'No', 'yes'=>'Yes',);       
    $mform->addElement('select', 'config_footer', get_string('showpoints', 'block_progress'), $options, $attributes);
    $mform->setType('config_footer', PARAM_MULTILANG); 


    //Adds summary mode field
    $options = array('teacher'=>get_string('teacher', 'block_progress'), 'tutor'=>get_string('tutor', 'block_progress'),);       
    $mform->addElement('select', 'config_mode', get_string('summarymode', 'block_progress'), $options, $attributes);
    $mform->setType('config_mode', PARAM_MULTILANG);


    //Adds default group field.
    global $DB;
    global $course;
    $groups  = $DB->get_records_sql('SELECT `name`,`id` FROM {groups} WHERE courseid = '.$course->id.'');
    
    //Converts object array to string array
    function objectToArray($d) {
		if (is_object($d)) {
			// Gets the properties of the given object
			// with get_object_vars function
			$d = get_object_vars($d);
		}
 
		if (is_array($d)) {
			/*
			* Return array converted to object
			* Using __FUNCTION__ (Magic constant)
			* for recursive call
			*/
			return array_map(__FUNCTION__, $d);
		}
		else {
			// Return array
			return $d;
		}
	}  
    //Converts object array to string array
    $groups = objectToArray($groups);

    //Builds group select list from $groups array.
    foreach ($groups as $key => $row) {
                                      $groupselect[$row['id']] = $row['name'];
                                      }
    $groupselect['0']="All";
    ksort($groupselect);
    $mform->addElement('select', 'config_group', get_string('defaultgroup', 'block_progress'), $groupselect);
    }
   
}



