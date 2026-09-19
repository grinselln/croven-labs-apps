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
            'pto_needed'   => trim($b['pto_needed'] ?? '') ?: null,
            'pto_approved' => trim($b['pto_approved'] ?? '') ?: null,
            'ticket_type'  => trim($b['ticket_type'] ?? '') ?: null,
            'seated'       => trim($b['seated'] ?? '') ?: null,
        ];

        // NOTE: sp_countdowns_add_item currently accepts the original 11 params.
        // The 4 new fields are set via a follow-up UPDATE after insert until the
        // stored procedure itself is updated to accept them.
        $origFields = array_slice($fields, 0, 11);
        $stmt = $pdo->prepare('CALL sp_countdowns_add_item(?,?,?,?,?,?,?,?,?,?,?)');
        $stmt->execute(array_values($origFields));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor(); // release the proc's result set before further queries

        $fields['id'] = (int) ($row['id'] ?? 0);

        $upd = $pdo->prepare(
            'UPDATE countdowns_items SET pto_needed=?, pto_approved=?, ticket_type=?, seated=? WHERE id=?'
        );
        $upd->execute([
            $fields['pto_needed'], $fields['pto_approved'],
            $fields['ticket_type'], $fields['seated'], $fields['id'],
        ]);

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
                end_Date=?, end_Time=?, Calendar=?, Guests=?, Notes=?,
                pto_needed=?, pto_approved=?, ticket_type=?, seated=?
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
            trim($b['pto_needed'] ?? '') ?: null,
            trim($b['pto_approved'] ?? '') ?: null,
            trim($b['ticket_type'] ?? '') ?: null,
            trim($b['seated'] ?? '') ?: null,
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
