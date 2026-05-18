<?php
/**
 * delete_appointment.php — Διαγραφή Ραντεβού
 */
session_start();
require_once 'config/db.php';

if (empty($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit;
}
$doctorId = (int)$_SESSION['doctor_id'];
$id       = (int)($_GET['id'] ?? 0);
$token    = $_GET['csrf_token'] ?? '';

// CSRF validation
if (!hash_equals($_SESSION['csrf_token'] ?? '', $token) || $id <= 0) {
    $_SESSION['flash_error'] = 'Μη έγκυρο αίτημα.';
    header('Location: dashboard.php');
    exit;
}

$pdo  = getDB();
$stmt = $pdo->prepare(
    "DELETE FROM appointments WHERE id = :id AND doctor_id = :doctor_id"
);
$stmt->execute([':id' => $id, ':doctor_id' => $doctorId]);

if ($stmt->rowCount() > 0) {
    $_SESSION['flash_success'] = "🗑️ Το ραντεβού #$id διαγράφηκε επιτυχώς.";
} else {
    $_SESSION['flash_error'] = 'Το ραντεβού δεν βρέθηκε ή δεν έχετε δικαίωμα διαγραφής.';
}

// Ανανέωση CSRF token μετά την ενέργεια
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

header('Location: dashboard.php');
exit;
