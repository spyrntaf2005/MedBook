<?php
/**
 * update_status.php - AJAX endpoint για αλλαγή κατάστασης ραντεβού
 */
session_start();
require_once 'config/db.php';

header('Content-Type: application/json; charset=utf-8');

// Auth check
if (empty($_SESSION['doctor_id'])) {
    echo json_encode(['success' => false, 'message' => 'Μη εξουσιοδοτημένη πρόσβαση.']);
    exit;
}

// CSRF check
$token = $_POST['csrf_token'] ?? '';
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
    echo json_encode(['success' => false, 'message' => 'Μη έγκυρο CSRF token.']);
    exit;
}

$id       = (int)($_POST['id']     ?? 0);
$status   = trim($_POST['status']  ?? '');
$doctorId = (int)$_SESSION['doctor_id'];

if ($id <= 0 || !in_array($status, ['pending','confirmed','cancelled'], true)) {
    echo json_encode(['success' => false, 'message' => 'Μη έγκυρα δεδομένα.']);
    exit;
}

try {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        "UPDATE appointments SET status = :status
         WHERE id = :id AND doctor_id = :doctor_id"
    );
    $stmt->execute([
        ':status'    => $status,
        ':id'        => $id,
        ':doctor_id' => $doctorId,
    ]);

    if ($stmt->rowCount() > 0) {
        // Ανανέωση CSRF
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        echo json_encode(['success' => true, 'new_status' => $status]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Το ραντεβού δεν βρέθηκε.']);
    }
} catch (PDOException $e) {
    error_log("Status update error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Σφάλμα βάσης δεδομένων.']);
}
