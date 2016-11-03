<?php

/**
 * Class to handle all db operations
 * This class will have CRUD methods for database tables
 *
 * @author Ravi Tamada
 * @link URL Tutorial link
 */
class DbHandler {

    private $conn;

    function __construct() {
        require_once dirname(__FILE__) . '/DbConnect.php';
        // opening db connection
        $db = new DbConnect();
        $this->conn = $db->connect();
    }

    /* ------------- `users` table method ------------------ */

    /**
     * Creating new user
     * @param String $name User full name
     * @param String $email User login email id
     * @param String $password User login password
     */
    public function createUser($nombre, $apellido , $usuario, $email, $password) {
        require_once 'PassHash.php';
        $response = array();

        // First check if user already existed in db
        if (!$this->isUserExists($email)) {
            // Generating password hash
            $password_hash = PassHash::hash($password);
            // Generating API key
            $api_key = $this->generateApiKey();

            // insert query
            $stmt = $this->conn->prepare("INSERT INTO usuarios(nombre, apellido, usuario, email, password_hash, api_key, status) values(?, ?, ?, ?, ?, ?, 1)");
            $stmt->bind_param("ssssss", $nombre, $apellido, $usuario, $email, $password_hash, $api_key);

            $result = $stmt->execute();

            $stmt->close();

            // Check for successful insertion
            if ($result) {
                // User successfully inserted
                return USER_CREATED_SUCCESSFULLY;
            } else {
                // Failed to create user
                return USER_CREATE_FAILED;
            }
        } else {
            // User with same email already existed in the db
            return USER_ALREADY_EXISTED;
        }

        return $response;
    }

    /**
     * Checking user login
     * @param String $email User login email id
     * @param String $password User login password
     * @return boolean User login status success/fail
     */
    public function checkLogin( $email, $password) {
        
        // fetching user by email
        $stmt = $this->conn->prepare("SELECT password_hash FROM usuarios WHERE email = ?");

        $stmt->bind_param("s", $email);

        $stmt->execute();

        $stmt->bind_result($password_hash);

        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            // Found user with the email
            // Now verify the password

            $stmt->fetch();

            $stmt->close();

            if (PassHash::check_password($password_hash, $password)) {
                // User password is correct
                return TRUE;
            } else {
                // user password is incorrect
                return FALSE;
            }
        } else {
            $stmt->close();

            // user not existed with the email
            return FALSE;
        }
    }

    /**
     * Checking for duplicate user by email address
     * @param String $email email to check in db
     * @return boolean
     */
    private function isUserExists($email) {
        $stmt = $this->conn->prepare("SELECT id_usuario as id from usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Fetching user by usuario
     * @param String $usuario Usuario 
     */
    public function getUserByUserName($usuario) {
        $stmt = $this->conn->prepare("SELECT CONCAT(nombre, ' ', apellido) As name, email, api_key, status, created_at FROM usuarios WHERE usuario = ?");
        $stmt->bind_param("s", $usuario);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name, $email, $api_key, $status, $created_at);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }
    /**
     * Fetching user by email
     * @param String $email User email id
     */
    public function getUserByEmail($email) {
        $stmt = $this->conn->prepare("SELECT CONCAT(nombre, ' ', apellido) As name, email, api_key, status, created_at FROM usuarios WHERE email = ?");
        $stmt->bind_param("s", $email);
        if ($stmt->execute()) {
            // $user = $stmt->get_result()->fetch_assoc();
            $stmt->bind_result($name, $email, $api_key, $status, $created_at);
            $stmt->fetch();
            $user = array();
            $user["name"] = $name;
            $user["email"] = $email;
            $user["api_key"] = $api_key;
            $user["status"] = $status;
            $user["created_at"] = $created_at;
            $stmt->close();
            return $user;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user api key
     * @param String $user_id user id primary key in user table
     */
    public function getApiKeyById($user_id) {
        $stmt = $this->conn->prepare("SELECT api_key FROM usuarios WHERE id_usuario = ?");
        $stmt->bind_param("i", $user_id['id']);
        if ($stmt->execute()) {
            // $api_key = $stmt->get_result()->fetch_assoc();
            // TODO
            $stmt->bind_result($api_key);
            $stmt->close();
            return $api_key;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching user id by api key
     * @param String $api_key user api key
     */
    public function getUserId($api_key) {
        $stmt = $this->conn->prepare("SELECT id_usuario as id from usuarios WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $user_id = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        return $user_id;
    }

    /**
     * Validating user api key
     * If the api key is there in db, it is a valid key
     * @param String $api_key user api key
     * @return boolean
     */
    public function isValidApiKey($api_key) {
        $stmt = $this->conn->prepare("SELECT id_usuario as id from usuarios WHERE api_key = ?");
        $stmt->bind_param("s", $api_key);
        $stmt->execute();
        $stmt->store_result();
        $num_rows = $stmt->num_rows;
        $stmt->close();
        return $num_rows > 0;
    }

    /**
     * Generating random Unique MD5 String for user Api key
     */
    private function generateApiKey() {
        return md5(uniqid(rand(), true));
    }

    /* ------------- `tasks` table method ------------------ */

    /**
     * Creating new task
     * @param String $user_id user id to whom task belongs to
     * @param String $task task text
     */
    public function createCategoria($user_id, $titulo, $descripcion,$array_file=null) {
        $rutaImg = $this->guardarImagen($array_file);
        $stmt = $this->conn->prepare("INSERT INTO categorias(id_usuario,titulo,descripcion,url_foto) VALUES(?,?,?,?)"); //FIX puede fallar
        $stmt->bind_param("isss", $user_id['id'],$titulo, $descripcion,$rutaImg);
        $result = $stmt->execute();
        $stmt->close();

        if ($result) {
            // task row created
            // now assign the task to user
            $nuevaCategoria_id = $this->conn->insert_id;
            //$res = $this->createUserTask($user_id, $nuevaCategoria_id);
            if (!is_null($nuevaCategoria_id)) {
                // task created successfully
                return $nuevaCategoria_id;
            } else {
                // task failed to create
                return NULL;
            }
        } else {
            // task failed to create
            return NULL;
        }
    }

    /**
     * Fetching single task
     * @param String $task_id id of the task
     */
    public function getCategoria($categoria_id, $user_id) {
        $stmt = $this->conn->prepare("SELECT c.id_categoria, c.titulo, c.descripcion, c.url_foto, c.created_at from categorias c WHERE c.id_categoria = ? AND c.id_usuario = ?");
        $stmt->bind_param("ii", $categoria_id, $user_id['id']);
        if ($stmt->execute()) {
            $res = array();
            $stmt->bind_result($id, $titulo, $desc, $url_foto, $created_at);
            // TODO
            // $task = $stmt->get_result()->fetch_assoc();
            $stmt->fetch();
            $res["id"] = $id;
            $res["titulo"] = $titulo;
            $res["desc"] = $desc;
            $res["url_foto"] = $url_foto;
            $res["created_at"] = $created_at;
            $stmt->close();
            return $res;
        } else {
            return NULL;
        }
    }

    /**
     * Fetching all user tasks
     * @param String $user_id id of the user
     */
    public function getAllUserCategories($user_id) {
        $stmt = $this->conn->prepare("SELECT c.* FROM categorias c WHERE c.id_usuario = ?");
        $stmt->bind_param("i", $user_id['id']);
        $stmt->execute();
        $tasks = $stmt->get_result();
        $stmt->close();
        return $tasks;
    }
    private function guardarImagen($array_File){
        
        if(!empty($array_File)){
                $file_path = "./uploads/";
                $file_path = $file_path . basename( $array_File['uploaded_file']['name']);
                if(move_uploaded_file($array_File['uploaded_file']['tmp_name'], $file_path) ){
                    $rutaImagen = 'uploads/'.basename( $array_File['uploaded_file']['name']);
                    echo "success";
                } else{
                    echo "fail";
                    print_r(error_get_last());
                }
            } else {
                $url = 'http://thecatapi.com/api/images/get?format=src&type=gif';
                $img = './uploads/'.$this->generateRandomString().'.gif';
                file_put_contents($img, file_get_contents($url));
                //$rutaImagen = 'uploads/'.$this->generateRandomString().'.gif';;
                $rutaImagen = substr($img, 2);
            }
            return $rutaImagen;
    }
    
    function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
}

    /**
     * Updating task
     * @param String $task_id id of the task
     * @param String $task task text
     * @param String $status task status
     */
    public function updateCategoria($user_id, $categoria_id, $titulo, $descripcion, $array_file=null) {
        $rutaImg = $this->guardarImagen($array_file);
        $stmt = $this->conn->prepare("UPDATE categorias c set c.titulo = ?, c.descripcion = ?, c.url_foto = ? WHERE c.id_categoria = ? AND c.id_usuario = ?");
        $stmt->bind_param("sssii", $titulo, $descripcion, $rutaImg,$categoria_id, $user_id['id']);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /**
     * Deleting a task
     * @param String $task_id id of the task to delete
     */
    public function deleteCategoria($user_id, $categoria_id) {
        $stmt = $this->conn->prepare("DELETE c FROM categorias c, usuarios u WHERE c.id_categoria = ?  AND u.id_usuario = ?");
        $stmt->bind_param("ii", $categoria_id, $user_id['id']);
        $stmt->execute();
        $num_affected_rows = $stmt->affected_rows;
        $stmt->close();
        return $num_affected_rows > 0;
    }

    /* ------------- `user_tasks` table method ------------------ */

    /**
     * Function to assign a task to user
     * @param String $user_id id of the user
     * @param String $task_id id of the task
     * /
    public function createUserTask($user_id, $task_id) {
        $stmt = $this->conn->prepare("INSERT INTO user_tasks(user_id, task_id) values(?, ?)");
        $stmt->bind_param("ii", $user_id, $task_id);
        $result = $stmt->execute();

        if (false === $result) {
            die('execute() failed: ' . htmlspecialchars($stmt->error));
        }
        $stmt->close();
        return $result;
    }
*/
}

?>
