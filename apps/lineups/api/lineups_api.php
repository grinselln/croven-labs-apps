<?php
// ─── lineups_api.php ──────────────────────────────────────────────────────────
// Lightweight JSON API for the Festivals app.
//
// Actions (all return JSON):
//   GET  ?action=lockscreen_data&festival_id=N   – days, stageColors, dayAccents,
//                                                   and schedule=1 transactions
//   POST action=set_schedule                      – toggle lineups_transactions.schedule
// ─────────────────────────────────────────────────────────────────────────────

require_once __DIR__ . '/../db/db_hosted.php';

header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// ── Helper ────────────────────────────────────────────────────────────────────
function json_error(string $msg, int $code = 400): never {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $msg]);
    exit;
}

// ═════════════════════════════════════════════════════════════════════════════
// POST  action=set_schedule
//   Body: festival_id (int), trans_id (int), schedule (0|1)
// ═════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'set_schedule') {
    $festId  = isset($_POST['festival_id']) ? (int)$_POST['festival_id'] : 0;
    $transId = isset($_POST['trans_id'])    ? (int)$_POST['trans_id']    : 0;
    $value   = isset($_POST['schedule'])    ? ((int)$_POST['schedule'] ? 1 : 0) : null;

    if (!$festId)             json_error('festival_id is required.');
    if (!$transId)            json_error('trans_id is required.');
    if ($value === null)      json_error('schedule value (0 or 1) is required.');

    try {
        // Verify the transaction belongs to this festival (security check)
        $chk = $pdo->prepare(
            "SELECT ID FROM lineups_transactions WHERE ID = :tid AND festival_ID = :fid LIMIT 1"
        );
        $chk->execute([':tid' => $transId, ':fid' => $festId]);
        if (!$chk->fetch()) {
            json_error('Transaction not found for this festival.', 404);
        }

        $stmt = $pdo->prepare(
            "UPDATE lineups_transactions SET `schedule` = :val WHERE ID = :tid AND festival_ID = :fid"
        );
        $stmt->execute([':val' => $value, ':tid' => $transId, ':fid' => $festId]);

        echo json_encode(['success' => true, 'trans_id' => $transId, 'schedule' => $value]);
    } catch (PDOException $e) {
        json_error('Database error: ' . $e->getMessage(), 500);
    }
    exit;
}

// ═════════════════════════════════════════════════════════════════════════════
// GET  action=lockscreen_data&festival_id=N
// ═════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $action === 'lockscreen_data') {
    $fid = isset($_GET['festival_id']) ? (int)$_GET['festival_id'] : 0;
    if (!$fid) json_error('festival_id is required.');

    try {
        // Pull config columns from lineups_import_logic
        $stmt = $pdo->prepare(
            "SELECT il.valid_days, il.stage_colors, il.dayAccents,
                    CONCAT(e.event_Year, ' ', e.event_Name) AS festival_name
               FROM lineups_import_logic il
               JOIN lineups_list e ON il.festival_id = e.id
              WHERE il.festival_id = :fid LIMIT 1"
        );
        $stmt->execute([':fid' => $fid]);
        $logic = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$logic) {
            json_error("No lineups_import_logic record found for festival ID {$fid}.", 404);
        }

        // Decode JSON columns; fall back to empty array on bad/missing data
        $validDays   = json_decode($logic['valid_days']   ?? '[]', true) ?: [];
        $stageColors = json_decode($logic['stage_colors'] ?? '[]', true) ?: [];
        $dayAccents  = json_decode($logic['dayAccents']  ?? '[]', true) ?: [];

        // Normalise day names
        $days = array_map('ucfirst', array_map('strtolower', $validDays));

        // Pull schedule=1 transactions for this festival, ordered by day then time
        // Build a dynamic FIELD() for the festival's actual day list (falls back to alpha)
        if (!empty($days)) {
            $placeholders = implode(',', array_fill(0, count($days), '?'));
            $fieldArgs    = array_merge([$fid], $days);
            $sql = "SELECT ID, performer, performer, day, stage, start_time, end_time
                      FROM lineups_transactions
                     WHERE festival_ID = ?
                       AND `schedule` = 1
                     ORDER BY FIELD(day, {$placeholders}), start_time ASC";
            $stmt2 = $pdo->prepare($sql);
            $stmt2->execute($fieldArgs);
        } else {
            $stmt2 = $pdo->prepare(
                "SELECT ID, performer, performer, day, stage, start_time, end_time
                   FROM lineups_transactions
                  WHERE festival_ID = ? AND `schedule` = 1
                  ORDER BY day ASC, start_time ASC"
            );
            $stmt2->execute([$fid]);
        }
        $transactions = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Group by day
        $schedule = [];
        foreach ($days as $day) $schedule[$day] = [];
        foreach ($transactions as $tx) {
            $txDay = ucfirst(strtolower($tx['day'] ?? ''));
            if (array_key_exists($txDay, $schedule)) {
                $schedule[$txDay][] = [
                    'ID'         => (int)$tx['ID'],
                    'performer'  => $tx['performer']  ?? $tx['performer'] ?? '',
                    'stage'      => $tx['stage']       ?? '',
                    'start_time' => $tx['start_time']  ?? '',
                    'end_time'   => $tx['end_time']    ?? '',
                ];
            }
        }

        echo json_encode([
            'success'       => true,
            'festival_name' => $logic['festival_name'],
            'days'          => $days,
            'stage_colors'  => $stageColors,
            'dayAccents'   => $dayAccents,
            'schedule'      => $schedule,
        ]);

    } catch (PDOException $e) {
        json_error('Database error: ' . $e->getMessage(), 500);
    }
    exit;
}

// ── Catch-all ─────────────────────────────────────────────────────────────────
json_error('Unknown or missing action.', 400);