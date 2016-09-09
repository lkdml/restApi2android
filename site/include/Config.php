<?php
/**
 * Database configuration
 */
define('DB_USERNAME', getenv('C9_USER'));
define('DB_PASSWORD', '');
define('DB_HOST', getenv('IP'));
define('DB_NAME', 'tp_categorias');

define('USER_CREATED_SUCCESSFULLY', 0);
define('USER_CREATE_FAILED', 1);
define('USER_ALREADY_EXISTED', 2);

?>
