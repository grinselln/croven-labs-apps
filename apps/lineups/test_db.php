<?php

require_once dirname(__DIR__, 2) . '/config/bootstrap.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

try {
    $result = $pdo->query('SELECT 1');
    echo 'SUCCESS - Database connected!';
} catch (Exception $e) {
    echo 'FAILED: ' . $e->getMessage();
}

var_dump($pdo->query("SELECT * FROM lineups_list LIMIT 5")->fetchAll());
