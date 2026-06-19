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
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'set_schedule') {
    $festId  = isset($_POST['festival_id']) ? (int)$_POST['festival_id'] : 0;
    $transId = isset($_POST['trans_id'])    ? (int)$_POST['trans_id']    : 0;
    $value   = isset($_POST['schedule'])    ? ((int)$_POST['schedule'] ? 1 : 0) : null;

    if (!$festId)             json_error('festival_id is required.');
    if (!$transId)            json_error('trans_id is required.');
    if ($value === null)      json_error('schedule value (0 or 1) is required.');

    try {
        $stmt = $pdo->prepare("CALL sp_lineups_set_schedule(:fid, :tid, :val)");
        $stmt->execute([':fid' => $festId, ':tid' => $transId, ':val' => $value]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt->closeCursor();

        echo json_encode([
            'success'  => true,
            'trans_id' => (int)$result['trans_id'],
            'schedule' => (int)$result['schedule'],
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '45000') {
            json_error('Transaction not found for this festival.', 404);
        }
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
        // in index.php's setlist_data.
        $stmt = $pdo->prepare(
            "SELECT valid_days, stage_colors, dayAccents, festival_name
               FROM vw_lineups_festival_config
              WHERE festival_id = :fid LIMIT 1"
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

        $stmt2 = $pdo->prepare(
            "SELECT ID, performer, day, stage,
                    start_Time AS start_time, end_Time AS end_time
               FROM vw_lineups_transactions_detail
              WHERE festival_ID = ? AND `schedule` = 1
              ORDER BY day_ord, start_minutes"
        );
        $stmt2->execute([$fid]);
        $transactions = $stmt2->fetchAll(PDO::FETCH_ASSOC);

        // Group by day
        $schedule = [];
        foreach ($days as $day) $schedule[$day] = [];
        foreach ($transactions as $tx) {
            $txDay = ucfirst(strtolower($tx['day'] ?? ''));
            if (array_key_exists($txDay, $schedule)) {
                $schedule[$txDay][] = [
                    'ID'         => (int)$tx['ID'],
                    'performer'  => $tx['performer']  ?? '',
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