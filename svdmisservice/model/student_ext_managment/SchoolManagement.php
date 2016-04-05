<?php
require_once '../../model/commen/PassHash.php';
/**
 * Class to handle all the talant details
 * This class will have CRUD methods for talant
 *
 * @author Bagya
 *
 */

class SchoolManagement {

    private $conn;

    function __construct() {
        require_once '../../model/commen/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }
	
	
/*
 * ------------------------ SCHOOL TABLE METHODS ------------------------
 */

    /**
     * Creating new school
     *
     * @param String $sch_name School name
	 * @param String $sch_situated_in town of the school
	 * @param String $recode_added_by 
     *
     * @return database transaction status
     */
    public function createSchool($sch_name, $sch_situated_in, $recode_added_by) {

        $response = array();
		
        // First check if Talant already existed in db
        if (!$this->isSchoolExists($sch_name)) {
  
            // insert query
			 $stmt = $this->conn->prepare("INSERT INTO school(sch_name, sch_situated_in, recode_added_by) values(?, ?, ?)");
			 $stmt->bind_param("ssi", $sch_name, $sch_situated_in, $recode_added_by );
			 $result = $stmt->execute();

			 $stmt->close();

        } else {
            // School is already existed in the db
            return ALREADY_EXISTED;
        }
		
         

        // Check for successful insertion
        if ($result) {
			// talant successfully inserted
            return CREATED_SUCCESSFULLY;
        } else {
            // Failed to create talant
            return CREATE_FAILED;
        }
        
		return $response;

    }


/**
     * Update school
     *
     * @param String $sch_name School name for the system
     * @param String $sch_situated_in where school is situated
	 * @param String $recode_added_by 
     *
     * @return database transaction status
     */
    public function updateSchool($sch_name, $sch_situated_in, $recode_added_by) {

		
        $response = array();
        // First check if school already existed in db
        if ($this->isSchoolExists($sch_name)) {
            
			//
			$stmt = $this->conn->prepare("UPDATE school set status = 2,  recode_modified_at = now() , recode_modified_by = ? where sch_name = ? and status = 1");
			$stmt->bind_param("is", $recode_added_by, $sch_name);
			$result = $stmt->execute();
			
            // insert updated recode
			$stmt = $this->conn->prepare("INSERT INTO school(sch_name, sch_situated_in, recode_added_by) values(?, ?, ?)");
			$stmt->bind_param("ssi", $sch_name, $sch_situated_in, $recode_added_by );
			$result = $stmt->execute();

			$stmt->close();

        } else {
            // school is not already existed in the db
            return NOT_EXISTED;
        }
		
         

        // Check for successful update
        if ($result) {
			// school successfully update
            return UPDATE_SUCCESSFULLY;
        } else {
            // Failed to update school
            return UPDATE_FAILED;
        }
        
		return $response;

    }	
	
	
/**
     * Delete school
     *
     * @param String $sch_name School name for the system
	 * @param String $sch_situated_in where school is situated
	 * @param String $recode_added_by
     *
     * @return database transaction status
     */
    public function deleteSchool($sch_name, $sch_situated_in, $recode_added_by) {

		
        $response = array();
        // First check if school already existed in db
        if ($this->isSchoolExists($sch_name)) {
           			
			//
			$stmt = $this->conn->prepare("UPDATE school set status = 3, recode_modified_at = now() , recode_modified_by = ? where sch_name = ? and sch_situated_in = ? and (status=1 or  status=2)");
			$stmt->bind_param("iss",$recode_added_by, $sch_name, $sch_situated_in);
			$result = $stmt->execute();
			
            $stmt->close();

        } else {
            // School is not already existed in the db
            return NOT_EXISTED;
        }
		
         

        // Check for successful insertion
        if ($result) {
			// school successfully deleted
            return DELETE_SUCCESSFULLY;
        } else {
            // Failed to delete school
            return DELETE_FAILED;
        }
        
		return $response;

    }
	  
	/**
     * Fetching schools by sch_name
	 *
     * @param String $sch_name school name
	 *
	 *@return school object only needed data
     */
    public function getSchoolBySchoolName($sch_name) {
        $stmt = $this->conn->prepare("SELECT sch_name, sch_situated_in, status, recode_added_at, recode_added_by FROM school WHERE sch_name = ? and (status=1 or status=2)");
        $stmt->bind_param("s", $sch_name);
        if ($stmt->execute()) {
            $stmt->bind_result($sch_name, $sch_situated_in, $status, $recode_added_at, $recode_added_by);
            $stmt->fetch();
            $school = array();
            $school["sch_name"] = $sch_name;
			$school["sch_situated_in"] = $sch_situated_in;
            $school["status"] = $status;
            $school["recode_added_at"] = $recode_added_at;
			$school["recode_added_by"] = $recode_added_by;

            $stmt->close();
            return $school;
        } else {
            return NULL;
        }
    }
  
  
	/**
     * Fetching all schools
	 *
     * @return $school object set of all schools
     */
    public function getAllSchools() {
        $stmt = $this->conn->prepare("SELECT * FROM school WHERE status = 1 or  status = 2");
        $stmt->execute();
        $schools = $stmt->get_result();
        $stmt->close();
        return $schools;
    }
	
  
  
  
  
  
/*
 * ------------------------ SUPPORTIVE METHODS ------------------------
 */

	/**
     * Checking for duplicate schools by sch_name
     *
     * @param String $sch_name School name to check in db
     *
     * @return boolean
     */
    private function isSchoolExists($sch_name) {
		$stmt = $this->conn->prepare("SELECT sch_name from school WHERE (status = 1 or status = 2)  and sch_name = ?  ");
        $stmt->bind_param("s",$sch_name);
        $stmt->execute();
		$stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return ($num_rows > 0); //if it has more than zero number of rows; then  it sends true
    }

}

?>
