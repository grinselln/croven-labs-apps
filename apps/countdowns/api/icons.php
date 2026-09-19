<?php
// icons.php
// GET /icons.php -> [ { class, label } ] — same list the website's icon picker uses.
// Icon values are one of: FontAwesome class ("fa-solid fa-heart"), an
// "iconify:<name>" string, or a plain emoji — see index.php's rendering logic
// for how each type is displayed.
//
// Requires header: X-Api-Key: <your key>

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/response.php';
require_once __DIR__ . '/includes/auth_middleware.php';

require_api_key();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json_error('Method not allowed', 405);
}

$stmt = $pdo->query('SELECT class, label FROM icons ORDER BY sort_order ASC, id ASC');
json_response($stmt->fetchAll());
