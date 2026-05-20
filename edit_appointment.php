<?php
/**
 * edit_appointment.php - Επεξεργασία Ραντεβού από τον Ιατρό
 * Σε αυτή τη σελίδα ο ιατρός μπορεί να αλλάξει όλα τα στοιχεία 
 * ενός ραντεβού (π.χ. να διορθώσει το τηλέφωνο ή να αλλάξει την ώρα).
 */
session_start();
require_once 'config/db.php';

// Έλεγχος πρόσβασης: Μόνο συνδεδεμένοι ιατροί επιτρέπονται
if (empty($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit;
}
$doctorId = (int)$_SESSION['doctor_id'];

// Δημιουργία ή ανάκτηση του CSRF token για ασφάλεια της φόρμας
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

$pdo = getDB();
$id  = (int)($_GET['id'] ?? 0); // Το ID του ραντεβού που επεξεργαζόμαστε

// Φόρτωση ραντεβού (εξασφαλίζοντας ότι ανήκει αποκλειστικά σε αυτόν τον ιατρό)
$stmt = $pdo->prepare(
    "SELECT * FROM appointments WHERE id = :id AND doctor_id = :doctor_id LIMIT 1"
);
$stmt->execute([':id' => $id, ':doctor_id' => $doctorId]);
$appt = $stmt->fetch();

// Αν δεν βρεθεί ραντεβού (π.χ. πείραξαν το URL), επιστροφή στο dashboard
if (!$appt) {
    $_SESSION['flash_error'] = 'Το ραντεβού δεν βρέθηκε.';
    header('Location: dashboard.php');
    exit;
}

$errors   = [];
$success  = false;

/* ΕΠΕΞΕΡΓΑΣΙΑ POST (Όταν πατηθεί η "Αποθήκευση") */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
    ) {
        $errors[] = 'Μη έγκυρο αίτημα (CSRF).';
    } else {
        $name   = trim($_POST['patient_name']   ?? '');
        $amka   = trim($_POST['amka']           ?? '');
        $phone  = trim($_POST['patient_phone']  ?? '');
        $email  = trim($_POST['patient_email']  ?? '');
        $reason = trim($_POST['visit_reason']   ?? '');
        $date   = trim($_POST['appointment_date'] ?? '');
        $time   = trim($_POST['appointment_time'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $notes  = trim($_POST['notes'] ?? '');

        if (mb_strlen($name) < 3)   $errors[] = 'Το ονοματεπώνυμο πρέπει να έχει τουλάχιστον 3 χαρακτήρες.';
        if (!preg_match('/^[0-9]{11}$/', $amka)) $errors[] = 'Ο ΑΜΚΑ πρέπει να έχει 11 ψηφία.';
        if (!preg_match('/^[0-9]{10}$/', $phone)) $errors[] = 'Μη έγκυρο τηλέφωνο (10 ψηφία).';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Μη έγκυρο email.';
        if (mb_strlen($reason) < 5) $errors[] = 'Ο λόγος επίσκεψης είναι πολύ σύντομος.';
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) $errors[] = 'Μη έγκυρη ημερομηνία.';

        $validTimes = [];
        for ($h = 9; $h < 18; $h++) { $validTimes[] = sprintf('%02d:00',$h); $validTimes[] = sprintf('%02d:30',$h); }
        if (!in_array($time, $validTimes, true)) $errors[] = 'Μη έγκυρη ώρα.';
        if (!in_array($status, ['pending','confirmed','cancelled'], true)) $errors[] = 'Μη έγκυρη κατάσταση.';

        if (empty($errors)) {
            // Έλεγχος διαθεσιμότητας (εξαιρώντας το τρέχον ραντεβού)
            $chk = $pdo->prepare(
                "SELECT COUNT(*) AS cnt FROM appointments
                 WHERE doctor_id = :doctor_id AND appointment_date = :date
                   AND appointment_time = :time AND status != 'cancelled' AND id != :id"
            );
            $chk->execute([':doctor_id'=>$doctorId,':date'=>$date,':time'=>$time,':id'=>$id]);
            if ((int)$chk->fetch()['cnt'] > 0) {
                $errors[] = "Η ώρα {$time} στις {$date} είναι ήδη κατειλημμένη.";
            } else {
                $upd = $pdo->prepare(
                    "UPDATE appointments SET
                       patient_name=:name, amka=:amka, patient_phone=:phone, patient_email=:email,
                       visit_reason=:reason, appointment_date=:date, appointment_time=:time,
                       status=:status, notes=:notes
                     WHERE id=:id AND doctor_id=:doctor_id"
                );
                $upd->execute([
                    ':name'=>$name,':amka'=>$amka,':phone'=>$phone,':email'=>$email,
                    ':reason'=>$reason,':date'=>$date,':time'=>$time,
                    ':status'=>$status,':notes'=>$notes,':id'=>$id,':doctor_id'=>$doctorId
                ]);
                $_SESSION['flash_success'] = "✅ Το ραντεβού #$id ενημερώθηκε επιτυχώς.";
                header('Location: dashboard.php');
                exit;
            }
        }
        // Ενημέρωση $appt για re-populate
        $appt = array_merge($appt, compact('name','amka','phone','email','reason','date','time','status','notes'));
    }
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_token'];
}

$timeSlots = [];
for ($h = 9; $h < 18; $h++) { $timeSlots[] = sprintf('%02d:00',$h); $timeSlots[] = sprintf('%02d:30',$h); }
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Επεξεργασία Ραντεβού #<?= $id ?> - MedBook</title>
  <link rel="stylesheet" href="css/style.css">
</head>
<body>

<nav class="navbar">
  <div class="navbar-brand"><span class="icon">🏥</span><span>MedBook</span></div>
  <div class="navbar-links">
    <a href="dashboard.php">← Dashboard</a>
    <a href="logout.php">Αποσύνδεση</a>
  </div>
</nav>

<section class="section">
  <div class="container">
    <div class="card" style="max-width:760px;margin:0 auto;">
      <h2 class="card-title">✏️ Επεξεργασία Ραντεβού <span style="color:var(--primary);">#<?= $id ?></span></h2>
      <p class="card-subtitle">Τροποποιήστε τα στοιχεία του ραντεβού και πατήστε «Αποθήκευση».</p>

      <?php if (!empty($errors)): ?>
        <div class="alert alert-danger"><?= implode('<br>', array_map('htmlspecialchars', $errors)) ?></div>
      <?php endif; ?>

      <form action="edit_appointment.php?id=<?= $id ?>" method="POST">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <div class="form-grid">
          <div class="form-group">
            <label>Ονοματεπώνυμο <span class="req">*</span></label>
            <input type="text" name="patient_name" value="<?= htmlspecialchars($appt['patient_name'] ?? '') ?>" maxlength="100">
          </div>
          <div class="form-group">
            <label>ΑΜΚΑ <span class="req">*</span></label>
            <input type="text" name="amka" value="<?= htmlspecialchars($appt['amka'] ?? '') ?>" maxlength="11" pattern="\d{11}">
          </div>
          <div class="form-group">
            <label>Τηλέφωνο <span class="req">*</span></label>
            <input type="tel" name="patient_phone" value="<?= htmlspecialchars($appt['patient_phone'] ?? '') ?>" maxlength="20">
          </div>
          <div class="form-group full">
            <label>Email <span class="req">*</span></label>
            <input type="email" name="patient_email" value="<?= htmlspecialchars($appt['patient_email'] ?? '') ?>" maxlength="100">
          </div>
          <div class="form-group full">
            <label>Λόγος Επίσκεψης <span class="req">*</span></label>
            <textarea name="visit_reason" maxlength="1000"><?= htmlspecialchars($appt['visit_reason'] ?? '') ?></textarea>
          </div>
          <div class="form-group">
            <label>Ημερομηνία <span class="req">*</span></label>
            <input type="date" name="appointment_date" value="<?= htmlspecialchars($appt['appointment_date'] ?? '') ?>">
          </div>
          <div class="form-group">
            <label>Ώρα <span class="req">*</span></label>
            <select name="appointment_time">
              <?php foreach ($timeSlots as $slot): ?>
              <option value="<?= $slot ?>" <?= (substr($appt['appointment_time']??'',0,5)===$slot)?'selected':'' ?>><?= $slot ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label>Κατάσταση <span class="req">*</span></label>
            <select name="status">
              <option value="pending"   <?= ($appt['status']==='pending')  ?'selected':'' ?>>Εκκρεμεί</option>
              <option value="confirmed" <?= ($appt['status']==='confirmed')?'selected':'' ?>>Επιβεβαιωμένο</option>
              <option value="cancelled" <?= ($appt['status']==='cancelled')?'selected':'' ?>>Ακυρωμένο</option>
            </select>
          </div>
          <div class="form-group full">
            <label>Σημειώσεις Ιατρού</label>
            <textarea name="notes" placeholder="Προαιρετικές σημειώσεις..."><?= htmlspecialchars($appt['notes'] ?? '') ?></textarea>
          </div>
        </div>

        <div class="flex gap-1 mt-3 flex-wrap">
          <button type="submit" class="btn btn-primary btn-lg">💾 Αποθήκευση</button>
          <a href="dashboard.php" class="btn btn-outline btn-lg">← Ακύρωση</a>
        </div>
      </form>
    </div>
  </div>
</section>

<footer>
  <p>© 2026 MedBook &mdash; Πίνακας Ελέγχου</p>
</footer>
<script src="js/main.js"></script>
</body>
</html>
