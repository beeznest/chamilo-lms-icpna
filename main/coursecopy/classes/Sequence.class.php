<?php
/* For licensing terms, see /license.txt */
require_once 'Resource.class.php';
/**
 * Attendance backup script
 * @package chamilo.backup
 */

class CourseCopySequence extends Resource {
		
	var $prerequisites = array();

    var $sequence_type_entity_id;

    var $c_id;

    var $row_id;

    var $name;

    var $previous;

    var $next;

	/**
	 * Create a new Thematic
	 * 
	 * @param array parameters	
	 */
	public function __construct($id, $sequence_type_entity_id, $c_id, $row_id, $name, $previous_sequence, $next_sequence) {
		parent::Resource($id, RESOURCE_SEQUENCE);
        $this->sequence_type_entity_id = $sequence_type_entity_id;
        $this->c_id = $c_id;
        $this->row_id = $row_id;
        $this->name = $name;
        $this->previous_sequence = $previous_sequence;
        $this->next_sequence = $next_sequence;
	}

	public function show() {
		parent::show();
		echo $this->name;
	}

    public function get_previous_sequence() {
        return $this->previous_sequence;
    }

    public function get_next_sequence() {
        return $this->next_sequence;
    }

}