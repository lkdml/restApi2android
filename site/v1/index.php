<?php

require_once '../include/DbHandler.php';
require_once '../include/PassHash.php';
require '.././libs/Slim/Slim.php';


\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

// User id from db - Global Variable
$user_id = NULL;

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

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $db = new DbHandler();

        // get the api key
        $api_key = $_SERVER['HTTP_AUTHORIZATION'];
        // validating api key
        if (!$db->isValidApiKey($api_key)) {
            // api key is not present in users table
            $response["error"] = true;
            $response["message"] = "Access Denied. Invalid Api key";
            echoRespnse(401, $response);
            $app->stop();
        } else {
            global $user_id;
            // get user primary key id
            $user_id = $db->getUserId($api_key);
        }
    } else {
        // api key is missing in header
        $response["error"] = true;
        $response["message"] = "Api key is misssing";
        echoRespnse(400, $response);
        $app->stop();
    }
}

/**
 * ----------- METHODS WITHOUT AUTHENTICATION ---------------------------------
 */
/**
 * User Registration
 * url - /register
 * method - POST
 * params - name, email, password
 */
$app->post('/register', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('nombre', 'apellido', 'usuario', 'email', 'password'));

            $response = array();

            // reading post params
            $nombre = $app->request->post('nombre');
            $apellido = $app->request->post('apellido');
            $usuario = $app->request->post('usuario');
            $email = $app->request->post('email');
            $password = $app->request->post('password');

            // validating email address
            validateEmail($email);

            $db = new DbHandler();
            $res = $db->createUser($nombre, $apellido, $usuario, $email, $password);

            if ($res == USER_CREATED_SUCCESSFULLY) {
                $response["error"] = false;
                $response["message"] = "Su registro fue realizado con exito.!";
            } else if ($res == USER_CREATE_FAILED) {
                $response["error"] = true;
                $response["message"] = "Oops! Ha ocurrido un error durante su registro.";
            } else if ($res == USER_ALREADY_EXISTED) {
                $response["error"] = true;
                $response["message"] = "Su correo o usuario ya existen en el sistema.";
            }
            // echo json response
            echoRespnse(201, $response);
        });

/**
 * User Login
 * url - /login
 * method - POST
 * params - email, password
 */
$app->post('/login', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('usuario','email', 'password'));

            // reading post params
            $usuario = $app->request()->post('usuario');
            $email = $app->request()->post('email');
            $password = $app->request()->post('password');
            $response = array();

            $db = new DbHandler();
            // check for correct email and password
            if ($db->checkLogin($usuario, $email, $password)) {
                // get the user by email
                if (is_null($usuario)){
                    $user = $db->getUserByEmail($email);
                } else if (is_null($email)){
                    $user = $db->getUserByEmail($usuario);
                }

                if ($user != NULL) {
                    $response["error"] = false;
                    $response['name'] = $user['name'];
                    $response['email'] = $user['email'];
                    $response['apiKey'] = $user['api_key'];
                    $response['createdAt'] = $user['created_at'];
                } else {
                    // unknown error occurred
                    $response['error'] = true;
                    $response['message'] = "Ha ocurrido un error, intente denuevo.";
                }
            } else {
                // user credentials are wrong
                $response['error'] = true;
                $response['message'] = 'ha ingresado datos incorrectos.';
            }

            echoRespnse(200, $response);
        });

/*
 * ------------------------ METHODS WITH AUTHENTICATION ------------------------
 */

/**
 * Listing all categorias of particual user
 * method GET
 * url /categorias          
 */
$app->get('/categorias', 'authenticate', function() {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetching all user tasks
            $result = $db->getAllUserTasks($user_id); //TODO Creo q esto no funca.

            $response["error"] = false;
            $response["categorias"] = array();

            // looping through result and preparing tasks array
            while ($task = $result->fetch_assoc()) {
                $tmp = array();
                $tmp["id"] = $task["id_categoria"];
                $tmp["titulo"] = $task["titulo"];
                $tmp["desc"] = $task["descripcion"];
                $tmp["url_foto"] = $task["url_foto"];
                $tmp["createdAt"] = $task["created_at"];
                array_push($response["categorias"], $tmp);
            }

            echoRespnse(200, $response);
        });

/**
 * Listing single categorias of particual user
 * method GET
 * url /categorias/:id
 * Will return 404 if the task doesn't belongs to user
 */
$app->get('/categorias/:id', 'authenticate', function($categoria_id) {
            global $user_id;
            $response = array();
            $db = new DbHandler();

            // fetch task
            $result = $db->getCategorias($categoria_id, $user_id);

            if ($result != NULL) {
                $response["error"] = false;
                $response["id"] = $result["id"];
                $response["titulo"] = $result["titulo"];
                $response["desc"] = $result["desc"];
                $response["url_foto"] = $result["url_foto"];
                $response["createdAt"] = $result["created_at"];
                echoRespnse(200, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "The requested resource doesn't exists";
                echoRespnse(404, $response);
            }
        });

/**
 * Creating new categorias in db
 * method POST
 * params - name
 * url - /categorias/
 */
$app->post('/categorias', 'authenticate', function() use ($app) {
            // check for required params
            verifyRequiredParams(array('categoria'));

            $response = array();
            $categoria = $app->request->post('categoria');

            global $user_id;
            $db = new DbHandler();

            // creating new task
            $categoria_id = $db->createTask($user_id, $categoria);

            if ($task_id != NULL) {
                $response["error"] = false;
                $response["message"] = "Categoria creada satisfactoriamente";
                $response["categoria_id"] = $categoria_id;
                echoRespnse(201, $response);
            } else {
                $response["error"] = true;
                $response["message"] = "Algo a pasado, intente nuevamente...";
                echoRespnse(200, $response);
            }            
        });

/**
 * Updating existing task
 * method PUT
 * params task, status
 * url - /tasks/:id
 */
$app->put('/tasks/:id', 'authenticate', function($task_id) use($app) {
            // check for required params
            verifyRequiredParams(array('task', 'status'));

            global $user_id;            
            $task = $app->request->put('task');
            $status = $app->request->put('status');

            $db = new DbHandler();
            $response = array();

            // updating task
            $result = $db->updateTask($user_id, $task_id, $task, $status);
            if ($result) {
                // task updated successfully
                $response["error"] = false;
                $response["message"] = "Task updated successfully";
            } else {
                // task failed to update
                $response["error"] = true;
                $response["message"] = "Task failed to update. Please try again!";
            }
            echoRespnse(200, $response);
        });

/**
 * Deleting task. Users can delete only their tasks
 * method DELETE
 * url /tasks
 */
$app->delete('/tasks/:id', 'authenticate', function($task_id) use($app) {
            global $user_id;

            $db = new DbHandler();
            $response = array();
            $result = $db->deleteTask($user_id, $task_id);
            if ($result) {
                // task deleted successfully
                $response["error"] = false;
                $response["message"] = "Task deleted succesfully";
            } else {
                // task failed to delete
                $response["error"] = true;
                $response["message"] = "Task failed to delete. Please try again!";
            }
            echoRespnse(200, $response);
        });

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
 * Validating email address
 */
function validateEmail($email) {
    $app = \Slim\Slim::getInstance();
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response["error"] = true;
        $response["message"] = 'Email address is not valid';
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