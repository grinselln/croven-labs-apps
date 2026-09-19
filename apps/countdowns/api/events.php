<?php
// events.php
// GET    /events.php                 -> list all events (reads vw_countdowns_items)
// GET    /events.php?upcoming=true    -> only future events (is_past = 0)
// GET    /events.php?id=123           -> single event
// POST   /events.php                  -> create event (calls sp_countdowns_add_item)
// PATCH  /events.php?id=123           -> update event (matches index.php's 'edit' UPDATE)
// DELETE /events.php?id=123           -> delete event
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
        if ($id) {
            $stmt = $pdo->prepare('SELECT * FROM vw_countdowns_items WHERE id = :id');
            $stmt->execute(['id' => $id]);
            $event = $stmt->fetch();
            if (!$event) {
                json_error('Event not found', 404);
            }
            json_response($event);
        } else {
            $onlyUpcoming = ($_GET['upcoming'] ?? 'false') === 'true';
            $sql = 'SELECT * FROM vw_countdowns_items';
            if ($onlyUpcoming) {
                $sql .= ' WHERE is_past = 0';
            }
            $sql .= ' ORDER BY start_Date ASC, id DESC';
            $stmt = $pdo->query($sql);
            json_response($stmt->fetchAll());
        }
        break;

    case 'POST':
        $b = json_body();
        $title = trim($b['title'] ?? '');
        if ($title === '') {
            json_error('Title required', 422);
        }

        $fields = [
            'title'      => $title,
            'location'   => trim($b['location'] ?? ''),
            'icon'       => trim($b['icon'] ?? ''),
            'color'      => trim($b['color'] ?? '#c0392b'),
            'start_Date' => $b['start_date'] ?: null,
            'start_Time' => $b['start_time'] ?: null,
            'end_Date'   => $b['end_date'] ?: null,
            'end_Time'   => $b['end_time'] ?: null,
            'Calendar'   => $b['calendar'] ? (int) $b['calendar'] : null,
            'Guests'     => trim($b['guests'] ?? ''),
            'Notes'      => trim($b['notes'] ?? ''),
        ];

        $stmt = $pdo->prepare('CALL sp_countdowns_add_item(?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array_values($fields));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // release the proc's result set before further queries

        $fields['id'] = (int) ($row['id'] ?? 0);
        json_response($fields, 201);
        break;

    case 'PATCH':
        if (!$id) {
            json_error('id query param required', 422);
        }
        $b = json_body();

        // Match index.php's edit behavior: full-row update, same column order.
        $stmt = $pdo->prepare(
            'UPDATE countdowns_items SET
                title=?, location=?, icon=?, color=?, start_Date=?, start_Time=?,
                end_Date=?, end_Time=?, Calendar=?, Guests=?, Notes=?
             WHERE id=?'
        );
        $stmt->execute([
            trim($b['title'] ?? ''),
            trim($b['location'] ?? ''),
            trim($b['icon'] ?? ''),
            trim($b['color'] ?? '#c0392b'),
            $b['start_date'] ?: null,
            $b['start_time'] ?: null,
            $b['end_date'] ?: null,
            $b['end_time'] ?: null,
            $b['calendar'] ? (int) $b['calendar'] : null,
            trim($b['guests'] ?? ''),
            trim($b['notes'] ?? ''),
            $id,
        ]);
        json_response(['updated' => true]);
        break;

    case 'DELETE':
        if (!$id) {
            json_error('id query param required', 422);
        }
        $pdo->prepare('DELETE FROM countdowns_items WHERE id = ?')->execute([$id]);
        json_response(['deleted' => true]);
        break;

    default:
        json_error('Method not allowed', 405);
}
