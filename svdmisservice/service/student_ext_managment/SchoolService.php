<?php
require_once '../../model/user_management/OperationalUserManagement.php';
require_once '../../model/student_ext_managment/SchoolManagement.php';
require '../.././config/libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$currunt_user_id = NULL;

/**
 * Adding Middle Layer to authenticate every request
 * Checking if the request has valid api key in the 'Authorization' header
 */
function authenticate(\Slim\Route $route) {
    // Getting request headers
    $headers = apache_request_headers();
    $response = array();
    $app = \Slim\Slim::getInstance();

    // Verifying Authorization Header
    if (isset($headers['Authorization'])) {
        $operationalUserManagement = new OperationalUserManagement();

        // get the api key
        $api_key = $headers['Authorization'];
        // validating api key
        if (!$operationalUserManagement->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $currunt_user_id;
            // get user primary key id
            $currunt_user_id = $operationalUserManagement->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/*
 * ------------------------ SCHOOL METHODS ------------------------
 */
 
/**
 * School Registration
 * url - /school_register
 * method - POST
 * params - sch_name, sch_situated_in
 */
$app->post('/school_register',  'authenticate', function() use ($app) {
	
            // check for required params
            verifyRequiredParams(array('sch_name', 'sch_situated_in' ));
			
			global $currunt_user_id;

            $response = array();

            // reading post params
            $sch_name = $app->request->post('sch_name');
			$sch_situated_in = $app->request->post('sch_situated_in');
         
           
            $schoolManagement = new SchoolManagement();
			$res = $schoolManagement->createSchool($sch_name, $sch_situated_in, $currunt_user_id);
			
            if ($res == CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "School is successfully registered";
            } else if ($res == CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while registereing school";
            } else if ($res == ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this school already exist";
            }
            // echo json response
            echoRespnse(201, $response);
        });


/**
 * School Update
 * url - /school_update/:schoolName
 * method - PUT
 * params - sch_name, sch_situated_in
 */
$app->put('/school_update/:schoolName',  'authenticate', function($sch_name) use ($app) {
	
            // check for required params
            verifyRequiredParams(array( 'sch_situated_in'));
			
			global $currunt_user_id;

            $response = array();

            // reading put params
            $sch_situated_in = $app->request->put('sch_situated_in');

            $schoolManagement = new SchoolManagement();
			$res = $schoolManagement->updateSchool($sch_name, $sch_situated_in,$currunt_user_id);
			
            if ($res == UPDATE_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "You are successfully updated school";
            } else if ($res == UPDATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while updating school";
            } else if ($res == NOT_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this school is not exist";
            }
            // echo json response
            echoRespnse(201, $response);
        });




/**
 * School Delete
 * url - /school_delete
 * method - DELETE
 * params - sch_name
 * params - sch_situated_in
 */
$app->delete('/school_delete', 'authenticate', function() use ($app) {
	
            // check for required params
            verifyRequiredParams(array('sch_name', 'sch_situated_in'));
			
			global $currunt_user_id;

            $response = array();

			// reading post params
            $sch_name = $app->request->delete('sch_name');
			$sch_situated_in = $app->request->delete('sch_situated_in');
			
            $schoolManagement = new SchoolManagement();
			$res = $schoolManagement->deleteSchool($sch_name, $sch_situated_in, $currunt_user_id);
			
            if ($res == DELETE_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "School is successfully deleted";
            } else if ($res == DELETE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! An error occurred while deleting school";
            } else if ($res == NOT_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Sorry, this school is not exist";
            }
            // echo json response
            echoRespnse(201, $response);
        });


		
/**
 * get one school
 * method GET
 * url /school/:schoolName       
 */
$app->get('/school/:schoolName', 'authenticate', function($sch_name) {
            global $currunt_user_id;
            $response = array();
            
			$schoolManagement = new SchoolManagement();
			$res = $schoolManagement->getSchoolBySchoolName($sch_name);

            $response["error"] = false;
            $response["school"] = $res;

            

            echoRespnse(200, $response);
        });

/**
 * Listing all schools
 * method GET
 * url /school        
 */
$app->get('/school', 'authenticate', function() {
            global $user_id;
			
            $response = array();
			
            $schoolManagement = new SchoolManagement();
			$res = $schoolManagement->getAllSchools();

            $response["error"] = false;
            $response["schools"] = array();

            // looping through result and preparing talants array
            while ($school = $res->fetch_assoc()) {
                $tmp = array();
				
                $tmp["sch_name"] = $school["sch_name"];
				$tmp["sch_situated_in"] = $school["sch_situated_in"];
                $tmp["status"] = $school["status"];
                $tmp["recode_added_at"] = $school["recode_added_at"];
				$tmp["recode_added_by"] = $school["recode_added_by"];
				
                array_push($response["schools"], $tmp);
            }

            echoRespnse(200, $response);
        });		
				

/*
 * ------------------------ SUPPORTIVE METHODS ------------------------
 */				
				
/**
 * Verifying required params posted or not
 */
function verifyRequiredParams($required_fields) {
    $error = false;
    $error_fields = "";
    $request_params = array();
    $request_params = $_REQUEST;
    // Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'PUT') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
	// Handling PUT request params
    if ($_SERVER['REQUEST_METHOD'] == 'DELETE') {
        $app = \Slim\Slim::getInstance();
        parse_str($app->request()->getBody(), $request_params);
    }
    foreach ($required_fields as $field) {
        if (!isset($request_params[$field]) || strlen(trim($request_params[$field])) <= 0) {
            $error = true;
            $error_fields .= $field . ', ';
        }
    }

    if ($error) {
        // Required field(s) are missing or empty
        // echo error json and stop the app
        $response = array();
        $app = \Slim\Slim::getInstance();
        $response["error"] = true;
        $response["message"] = 'Required field(s) ' . substr($error_fields, 0, -2) . ' is missing or empty';
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * Echoing json response to client
 * @param String $status_code Http response code
 * @param Int $response Json response
 */
function echoRespnse($status_code, $response) {
    $app = \Slim\Slim::getInstance();
    // Http response code
    $app->status($status_code);

    // setting response content type to json
    $app->contentType('application/json');

    echo json_encode($response);
}

$app->run();
?>