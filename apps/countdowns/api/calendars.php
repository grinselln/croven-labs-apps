<?php
// calendars.php
// GET    /calendars.php          -> list calendars with item counts (vw_countdowns_calendar_usage)
// POST   /calendars.php          -> create calendar { "label": "..." }
// DELETE /calendars.php?id=123   -> delete calendar (unassigns events, doesn't delete them —
//                                    matches sp_countdowns_delete_calendar behavior)
//
// All requests require header: X-Api-Key: <your key>

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/response.php';
require_once __DIR__ . '/includes/auth_middleware.php';

require_api_key();

$method = $_SERVER['REQUEST_METHOD'];
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;

switch ($method) {
    case 'GET':
        $stmt = $pdo->query(
            'SELECT id, label, item_count FROM vw_countdowns_calendar_usage ORDER BY label'
        );
        json_response($stmt->fetchAll());
        break;

    case 'POST':
        $b = json_body();
        $label = trim($b['label'] ?? '');
        if ($label === '') {
            json_error('Label required', 422);
        }
        $stmt = $pdo->prepare('INSERT INTO countdowns_calendar (label) VALUES (?)');
        $stmt->execute([$label]);
        json_response(['id' => (int) $pdo->lastInsertId(), 'label' => $label], 201);
        break;

    case 'DELETE':
        if (!$id) {
            json_error('id query param required', 422);
        }
        // Sets Calendar = NULL on any events using this calendar rather than
        // deleting them — same behavior as the website.
        $stmt = $pdo->prepare('CALL sp_countdowns_delete_calendar(?)');
        $stmt->execute([$id]);
        $stmt->closeCursor();
        json_response(['deleted' => true]);
        break;

    default:
        json_error('Method not allowed', 405);
}
