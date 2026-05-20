<?php
/**
 * book_appointment.php
 * Επεξεργασία φόρμας κλεισίματος ραντεβού
 * Ασφάλεια: CSRF, PDO Prepared Statements, XSS, validation
 * Σε αυτό το αρχείο δεν υπάρχει HTML, λειτουργεί μόνο στο παρασκήνιο (backend)
 * για να δεχτεί τα δεδομένα από το appointment.php.
 */
session_start();
require_once 'config/db.php';

// Μόνο αιτήματα τύπου POST (μέσω φόρμας) επιτρέπονται εδώ.
// Αν κάποιος πάει να μπει γράφοντας το URL, τον διώχνουμε πίσω στη φόρμα.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: appointment.php');
    exit;
}

/* CSRF ΠΡΟΣΤΑΣΙΑ */
if (
    empty($_POST['csrf_token']) ||
    !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
) {
    $_SESSION['flash_error'] = 'Μη έγκυρο αίτημα (CSRF). Παρακαλώ δοκιμάστε ξανά.';
    header('Location: appointment.php');
    exit;
}
// Ανανέωση CSRF token μετά από κάθε χρήση
$_SESSION['csrf_token'] = bin2hex(random_bytes(32));

/* SANITIZE & VALIDATE */
$name   = trim($_POST['patient_name']   ?? '');
$amka   = trim($_POST['amka']           ?? '');
$phone  = trim($_POST['patient_phone']  ?? '');
$email  = trim($_POST['patient_email']  ?? '');
$reason = trim($_POST['visit_reason']   ?? '');
$date   = trim($_POST['appointment_date'] ?? '');
$time   = trim($_POST['appointment_time'] ?? '');

$errors = [];

if (mb_strlen($name) < 3 || mb_strlen($name) > 100) {
    $errors[] = 'Το ονοματεπώνυμο πρέπει να έχει 3–100 χαρακτήρες.';
}

if (!preg_match('/^[0-9]{10}$/', $phone)) {
    $errors[] = 'Το τηλέφωνο πρέπει να αποτελείται από 10 ψηφία.';
}

if (!preg_match('/^[0-9]{11}$/', $amka)) {
    $errors[] = 'Ο ΑΜΚΑ πρέπει να αποτελείται ακριβώς από 11 ψηφία.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 100) {
    $errors[] = 'Μη έγκυρη διεύθυνση email.';
}

if (mb_strlen($reason) < 5 || mb_strlen($reason) > 1000) {
    $errors[] = 'Ο λόγος επίσκεψης πρέπει να έχει 5–1000 χαρακτήρες.';
}

// Έλεγχος ημερομηνίας (δεν επιτρέπεται παρελθόν)
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
    $errors[] = 'Μη έγκυρη μορφή ημερομηνίας.';
} elseif ($date < date('Y-m-d')) {
    $errors[] = 'Δεν μπορείτε να κλείσετε ραντεβού σε παρελθοντική ημερομηνία.';
}

// Έλεγχος ώρας (format HH:MM)
$validTimes = [];
for ($h = 9; $h < 18; $h++) {
    $validTimes[] = sprintf('%02d:00', $h);
    $validTimes[] = sprintf('%02d:30', $h);
}
if (!in_array($time, $validTimes, true)) {
    $errors[] = 'Μη έγκυρη ώρα ραντεβού.';
}

/* ΑΝ ΥΠΑΡΧΟΥΝ ΣΦΑΛΜΑΤΑ */
if (!empty($errors)) {
    $_SESSION['flash_error'] = implode('<br>', $errors);
    $_SESSION['form_old']    = [
        'patient_name'     => $name,
        'amka'             => $amka,
        'patient_phone'    => $phone,
        'patient_email'    => $email,
        'visit_reason'     => $reason,
        'appointment_date' => $date,
        'appointment_time' => $time,
    ];
    header('Location: appointment.php');
    exit;
}

/* ΕΛΕΓΧΟΣ ΔΙΑΘΕΣΙΜΟΤΗΤΑΣ (Anti-Double-Booking) */
$pdo = getDB();
$doctorId = 1; // Μοναδικός ιατρός

try {
    // Χρήση transaction για αποφυγή race condition
    $pdo->beginTransaction();

    $checkStmt = $pdo->prepare(
        "SELECT COUNT(*) AS cnt
         FROM appointments
         WHERE doctor_id        = :doctor_id
           AND appointment_date = :date
           AND appointment_time = :time
           AND status          != 'cancelled'
         FOR UPDATE"
    );
    $checkStmt->execute([
        ':doctor_id' => $doctorId,
        ':date'      => $date,
        ':time'      => $time,
    ]);
    $row = $checkStmt->fetch();

    if ((int)$row['cnt'] > 0) {
        $pdo->rollBack();
        $_SESSION['flash_warning'] = "⚠️ Η ώρα {$time} στις " . date('d/m/Y', strtotime($date)) . " δεν είναι διαθέσιμη. Παρακαλώ επιλέξτε άλλη ώρα ή ημερομηνία.";
        $_SESSION['form_old'] = [
            'patient_name'     => $name,
            'amka'             => $amka,
            'patient_phone'    => $phone,
            'patient_email'    => $email,
            'visit_reason'     => $reason,
            'appointment_date' => $date,
            'appointment_time' => $time,
        ];
        header('Location: appointment.php');
        exit;
    }

    /* ΑΠΟΘΗΚΕΥΣΗ ΡΑΝΤΕΒΟΥ */
    $insertStmt = $pdo->prepare(
        "INSERT INTO appointments
            (doctor_id, patient_name, amka, patient_phone, patient_email, visit_reason, appointment_date, appointment_time, status)
         VALUES
            (:doctor_id, :name, :amka, :phone, :email, :reason, :date, :time, 'pending')"
    );
    $insertStmt->execute([
        ':doctor_id' => $doctorId,
        ':name'      => $name,
        ':amka'      => $amka,
        ':phone'     => $phone,
        ':email'     => $email,
        ':reason'    => $reason,
        ':date'      => $date,
        ':time'      => $time,
    ]);
    $newId = $pdo->lastInsertId();
    $pdo->commit();

    // Αποθήκευση στοιχείων για σελίδα επιβεβαίωσης
    $_SESSION['confirmed_appointment'] = [
        'id'    => $newId,
        'name'  => $name,
        'phone' => $phone,
        'email' => $email,
        'date'  => $date,
        'time'  => $time,
    ];
    header('Location: confirmation.php');
    exit;

} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Appointment insert error: " . $e->getMessage());
    $_SESSION['flash_error'] = 'Προέκυψε σφάλμα κατά την αποθήκευση. Παρακαλώ δοκιμάστε ξανά.';
    header('Location: appointment.php');
    exit;
}
