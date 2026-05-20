<?php
/**
 * dashboard.php - Πίνακας Ελέγχου Ιατρού
 * Αυτή η σελίδα είναι το κεντρικό διαχειριστικό εργαλείο.
 * Μόνο συνδεδεμένοι χρήστες (ιατροί) έχουν πρόσβαση εδώ.
 */
session_start(); // Έναρξη του session
require_once 'config/db.php'; // Σύνδεση με τη βάση δεδομένων

// Προστασία: Αν δεν υπάρχει το 'doctor_id' στο session, 
// σημαίνει ότι δεν έχει κάνει login. Τον ανακατευθύνουμε στη login.php!
if (empty($_SESSION['doctor_id'])) {
    header('Location: login.php');
    exit;
}

// Αποθήκευση στοιχείων του ιατρού σε μεταβλητές
$doctorId   = (int)$_SESSION['doctor_id'];
$doctorName = htmlspecialchars($_SESSION['doctor_name'] ?? 'Ιατρός');

// CSRF token: Μηχανισμός προστασίας από επιθέσεις (Cross-Site Request Forgery)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Δημιουργεί έναν τυχαίο κωδικό
}
$csrf = $_SESSION['csrf_token'];

$pdo = getDB(); // Παίρνουμε το αντικείμενο PDO για επικοινωνία με τη MySQL

/* ΦΙΛΤΡΑ (Δυναμική Αναζήτηση) */
// Παίρνουμε τις τιμές από το URL (π.χ. dashboard.php?date=...&status=...)
$filterDate   = trim($_GET['date']   ?? '');
$filterStatus = trim($_GET['status'] ?? '');

// Δημιουργία των συνθηκών (WHERE) για το SQL Query
$where  = ['doctor_id = :doctor_id']; // Δείχνουμε μόνο τα ραντεβού αυτού του ιατρού
$params = [':doctor_id' => $doctorId];

// Αν ο ιατρός έδωσε ημερομηνία, την προσθέτουμε στο φίλτρο
if ($filterDate && preg_match('/^\d{4}-\d{2}-\d{2}$/', $filterDate)) {
    $where[]             = 'appointment_date = :date';
    $params[':date']     = $filterDate;
}
// Αν έδωσε κατάσταση (status), την προσθέτουμε στο φίλτρο
if (in_array($filterStatus, ['pending','confirmed','cancelled'], true)) {
    $where[]              = 'status = :status';
    $params[':status']    = $filterStatus;
}

// Ενώνουμε όλες τις συνθήκες με τη λέξη ' AND ' για το τελικό query
$whereSQL = implode(' AND ', $where);

/* ΣΤΑΤΙΣΤΙΚΑ */
// Μετράμε πόσα ραντεβού υπάρχουν συνολικά και πόσα ανά κατηγορία (για τα κουτάκια ψηλά)
$statStmt = $pdo->prepare(
    "SELECT
       COUNT(*) AS total,
       SUM(status = 'pending')   AS pending,
       SUM(status = 'confirmed') AS confirmed,
       SUM(status = 'cancelled') AS cancelled
     FROM appointments
     WHERE doctor_id = :doctor_id"
);
$statStmt->execute([':doctor_id' => $doctorId]);
$stats = $statStmt->fetch();

/* ΡΑΝΤΕΒΟΥ */
// Τραβάμε όλα τα ραντεβού που ταιριάζουν με τα φίλτρα
$apptStmt = $pdo->prepare(
    "SELECT id, patient_name, amka, patient_phone, patient_email,
            visit_reason, appointment_date, appointment_time, status, created_at
     FROM appointments
     WHERE {$whereSQL}
     ORDER BY appointment_date ASC, appointment_time ASC" // Ταξινόμηση ανά ημερομηνία & ώρα
);
$apptStmt->execute($params);
$appointments = $apptStmt->fetchAll(); // Φέρνουμε όλα τα αποτελέσματα ως πίνακα

/* FLASH MESSAGES */
// Μηνύματα επιτυχίας ή σφάλματος (π.χ. "Αποθηκεύτηκε!")
$flashSuccess = $_SESSION['flash_success'] ?? null;
$flashError   = $_SESSION['flash_error']   ?? null;
// Μόλις τα διαβάσουμε, τα διαγράφουμε (unset) για να μην εμφανιστούν ξανά σε ανανέωση
unset($_SESSION['flash_success'], $_SESSION['flash_error']);

/* STATUS LABELS */
// Ελληνικές μεταφράσεις και κλάσεις CSS για τα "ταμπελάκια" κατάστασης των ραντεβού
$statusLabel = ['pending'=>'Εκκρεμεί','confirmed'=>'Επιβεβαιωμένο','cancelled'=>'Ακυρωμένο'];
$statusClass = ['pending'=>'status-pending','confirmed'=>'status-confirmed','cancelled'=>'status-cancelled'];
$statusDot   = ['pending'=>'','confirmed'=>'','cancelled'=>''];

/* FORMAT DATE */
/**
 * Βοηθητική συνάρτηση: Μετατρέπει την ημερομηνία 2026-05-30 σε πιο φιλική "30 Μαΐ 2026"
 */
function greekDate(string $dateStr): string {
    $months = ['01'=>'Ιαν','02'=>'Φεβ','03'=>'Μαρ','04'=>'Απρ','05'=>'Μαΐ','06'=>'Ιουν',
               '07'=>'Ιουλ','08'=>'Αυγ','09'=>'Σεπ','10'=>'Οκτ','11'=>'Νοε','12'=>'Δεκ'];
    [$y, $m, $d] = explode('-', $dateStr);
    return "{$d} " . ($months[$m] ?? $m) . " {$y}";
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
  <title>Dashboard - MedBook</title>
  <meta name="description" content="Πίνακας διαχείρισης ιατρικών ραντεβού.">
  <link rel="stylesheet" href="css/style.css?v=5">
</head>
<body>

<!-- DASHBOARD HEADER -->
<div class="dash-header">
  <div>
    <h2 style="margin: 0;">Καλωσήρθατε, <?= $doctorName ?> &mdash; <span style="font-weight: normal; font-size: 1.1rem; color: var(--text-muted);"><?= htmlspecialchars($_SESSION['doctor_spec'] ?? '') ?></span></h2>
  </div>
  <div class="flex gap-1 flex-wrap">
    <a href="index.php" class="btn btn-outline" style="color:#fff;border-color:rgba(255,255,255,0.4);">Σελίδα Ασθενών</a>
    <a href="logout.php" class="btn btn-danger">Αποσύνδεση</a>
  </div>
</div>

<div class="container section">

  <!-- FLASH MESSAGES -->
  <?php if ($flashSuccess): ?>
    <div class="alert alert-success"><?= htmlspecialchars($flashSuccess) ?></div>
  <?php endif; ?>
  <?php if ($flashError): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
  <?php endif; ?>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon blue" style="font-weight: 800; color: var(--primary-dark);"><?= (int)$stats['total'] ?></div>
      <div>
        <div class="stat-label" style="font-size: 1rem; font-weight: 600; color: var(--dark); margin-top: 0;">Σύνολο Ραντεβού</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon yellow" style="font-weight: 800; color: #92400e;"><?= (int)$stats['pending'] ?></div>
      <div>
        <div class="stat-label" style="font-size: 1rem; font-weight: 600; color: var(--dark); margin-top: 0;">Εκκρεμή</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon green" style="font-weight: 800; color: #065f46;"><?= (int)$stats['confirmed'] ?></div>
      <div>
        <div class="stat-label" style="font-size: 1rem; font-weight: 600; color: var(--dark); margin-top: 0;">Επιβεβαιωμένα</div>
      </div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red" style="font-weight: 800; color: #b91c1c;"><?= (int)$stats['cancelled'] ?></div>
      <div>
        <div class="stat-label" style="font-size: 1rem; font-weight: 600; color: var(--dark); margin-top: 0;">Ακυρωμένα</div>
      </div>
    </div>
  </div>

  <!-- FILTERS -->
  <form method="GET" action="dashboard.php">
    <div class="filters-bar">
      <div class="form-group">
        <label for="date">Ημερομηνία</label>
        <input type="date" id="date" name="date" value="<?= htmlspecialchars($filterDate) ?>">
      </div>
      <div class="form-group">
        <label for="status">Κατάσταση</label>
        <select id="status" name="status">
          <option value="">— Όλες —</option>
          <option value="pending"   <?= $filterStatus==='pending'   ?'selected':'' ?>>Εκκρεμή</option>
          <option value="confirmed" <?= $filterStatus==='confirmed' ?'selected':'' ?>>Επιβεβαιωμένα</option>
          <option value="cancelled" <?= $filterStatus==='cancelled' ?'selected':'' ?>>Ακυρωμένα</option>
        </select>
      </div>
      <div class="form-group">
        <label for="tableSearch">Αναζήτηση</label>
        <input type="text" id="tableSearch" placeholder="Search">
      </div>
      <div>
        <button type="submit" class="btn btn-primary">🔍</button>
        <a href="dashboard.php" class="btn btn-outline" style="margin-left:0.5rem;">Καθαρισμός</a>
      </div>
    </div>
  </form>

  <!-- TABLE -->
  <?php if (empty($appointments)): ?>
    <div class="alert alert-info">Δεν βρέθηκαν ραντεβού με τα επιλεγμένα φίλτρα.</div>
  <?php else: ?>
  <div class="table-wrapper">
    <table>
      <thead>
        <tr>
          <th>#ID</th>
          <th>Ασθενής</th>
          <th>ΑΜΚΑ</th>
          <th>Επικοινωνία</th>
          <th>Λόγος</th>
          <th>Ημερομηνία</th>
          <th>Ώρα</th>
          <th>Κατάσταση</th>
          <th>Ενέργειες</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($appointments as $a): ?>
        <tr>
          <td><strong>#<?= (int)$a['id'] ?></strong></td>
          <td>
            <strong><?= htmlspecialchars($a['patient_name']) ?></strong>
          </td>
          <td><?= htmlspecialchars($a['amka'] ?? '') ?></td>
          <td>
            <div style="font-size:0.82rem;">
              <?= htmlspecialchars($a['patient_phone']) ?><br>
              <?= htmlspecialchars($a['patient_email']) ?>
            </div>
          </td>
          <td style="max-width:180px;">
            <span title="<?= htmlspecialchars($a['visit_reason']) ?>" style="font-size:0.85rem;">
              <?= htmlspecialchars(mb_substr($a['visit_reason'], 0, 50)) ?>
              <?= mb_strlen($a['visit_reason']) > 50 ? '…' : '' ?>
            </span>
          </td>
          <td style="white-space:nowrap;"><?= greekDate($a['appointment_date']) ?></td>
          <td style="white-space:nowrap;font-weight:600;"><?= htmlspecialchars(substr($a['appointment_time'],0,5)) ?></td>
          <td>
            <span class="status-badge <?= $statusClass[$a['status']] ?>">
              <?= $statusDot[$a['status']] ?> <?= $statusLabel[$a['status']] ?>
            </span>
          </td>
          <td>
            <div class="flex gap-1 flex-wrap">
              <!-- Επεξεργασία -->
              <a href="edit_appointment.php?id=<?= (int)$a['id'] ?>" class="btn btn-warning btn-sm" title="Επεξεργασία">Επεξ.</a>
              <!-- Status buttons -->
              <?php if ($a['status'] !== 'confirmed'): ?>
              <button class="btn btn-success btn-sm btn-status"
                      data-id="<?= (int)$a['id'] ?>" data-status="confirmed" title="Επιβεβαίωση">Επιβεβ.</button>
              <?php endif; ?>
              <?php if ($a['status'] !== 'cancelled'): ?>
              <button class="btn btn-danger btn-sm btn-status"
                      data-id="<?= (int)$a['id'] ?>" data-status="cancelled" title="Ακύρωση">Ακύρ.</button>
              <?php endif; ?>
              <!-- Διαγραφή -->
              <a href="delete_appointment.php?id=<?= (int)$a['id'] ?>&csrf_token=<?= urlencode($csrf) ?>"
                 class="btn btn-sm btn-delete-confirm"
                 style="background:#6b7280;color:#fff;" title="Διαγραφή">Διαγρ.</a>
            </div>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>

  <?php endif; ?>

</div>

<footer>
  <p>© 2026 MedBook &mdash; Σύστημα Διαχείρισης Ραντεβού | Developed by <span>SPNT INTUSTRIES </span></p>
</footer>

<script src="js/main.js"></script>
</body>
</html>
