<?php
if (!defined('APP_ROOT')) {
    // bootstrap.php lives in /config, so APP_ROOT is one level up
    define('APP_ROOT', dirname(__DIR__));
}

define('DB_HOSTED_FILE', APP_ROOT . '/db/db_hosted.php');

if (!file_exists(DB_HOSTED_FILE)) {
    die("Configuration error: credentials file not found at expected path.");
}
require_once DB_HOSTED_FILE;





