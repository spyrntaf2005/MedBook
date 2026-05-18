<?php
session_start();

// CSRF token δημιουργία
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf = $_SESSION['csrf_token'];

// Ώρες ραντεβού (κάθε 30 λεπτά, 09:00–17:30)
$timeSlots = [];
for ($h = 9; $h < 18; $h++) {
    $timeSlots[] = sprintf('%02d:00', $h);
    $timeSlots[] = sprintf('%02d:30', $h);
}

// Διατήρηση τιμών φόρμας μετά από σφάλμα
$old = $_SESSION['form_old'] ?? [];
unset($_SESSION['form_old']);
$flashError   = $_SESSION['flash_error']   ?? null;
$flashWarning = $_SESSION['flash_warning'] ?? null;
unset($_SESSION['flash_error'], $_SESSION['flash_warning']);
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="<?= htmlspecialchars($csrf) ?>">
  <title>Κλείστε Ραντεβού — MedBook</title>
  <meta name="description" content="Κλείστε εύκολα και γρήγορα ιατρικό ραντεβού online.">
  <link rel="stylesheet" href="css/style.css?v=5">
</head>
<body>

<!-- NAVBAR -->
<nav class="navbar">
  <div class="navbar-brand">
    <span class="icon">🏥</span>
    <span>MedBook</span>
  </div>
  <div class="navbar-center">
    <a href="index.php">Αρχική</a>
    <a href="about.php">Σχετικά με εμάς</a>
    <a href="services.php">Οι Υπηρεσίες μας</a>
    <a href="appointment.php" style="color:var(--primary);font-weight:700;">Κλείστε Ραντεβού</a>
  </div>
  <div class="navbar-right">
    <a href="login.php" class="btn-nav-login">Σύνδεση Ιατρού</a>
  </div>
</nav>

<!-- BOOKING FORM -->
<section class="section" style="min-height: calc(100vh - 140px);">
  <div class="container">
    <div class="card" style="max-width: 760px; margin: 0 auto;">
      <h2 class="card-title">📋 Φόρμα Κλεισίματος Ραντεβού</h2>
      <p class="card-subtitle">Συμπληρώστε τα παρακάτω στοιχεία. Όλα τα πεδία με <span style="color:var(--danger)">*</span> είναι υποχρεωτικά.</p>

      <!-- Flash μηνύματα -->
      <?php if ($flashError): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($flashError) ?></div>
      <?php endif; ?>
      <?php if ($flashWarning): ?>
        <div class="alert alert-warning"><?= htmlspecialchars($flashWarning) ?></div>
      <?php endif; ?>

      <form id="bookingForm" action="book_appointment.php" method="POST" novalidate>
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

        <div class="form-grid">

          <!-- Ονοματεπώνυμο -->
          <div class="form-group">
            <label for="patient_name">Ονοματεπώνυμο <span class="req">*</span></label>
            <input type="text" id="patient_name" name="patient_name"
                   placeholder="π.χ. Μαρία Παπαδοπούλου"
                   value="<?= htmlspecialchars($old['patient_name'] ?? '') ?>"
                   maxlength="100" autocomplete="name">
            <span class="error-msg" id="errorName"></span>
          </div>

          <!-- ΑΜΚΑ -->
          <div class="form-group">
            <label for="amka">ΑΜΚΑ <span class="req">*</span></label>
            <input type="text" id="amka" name="amka"
                   placeholder="π.χ. 12345678901"
                   value="<?= htmlspecialchars($old['amka'] ?? '') ?>"
                   maxlength="11" pattern="\d{11}" title="Ο ΑΜΚΑ πρέπει να αποτελείται από ακριβώς 11 ψηφία">
            <span class="error-msg" id="errorAmka"></span>
          </div>

          <!-- Τηλέφωνο -->
          <div class="form-group">
            <label for="patient_phone">Τηλέφωνο <span class="req">*</span></label>
            <input type="tel" id="patient_phone" name="patient_phone"
                   placeholder="π.χ. 6901234567"
                   value="<?= htmlspecialchars($old['patient_phone'] ?? '') ?>"
                   maxlength="20" autocomplete="tel">
            <span class="error-msg" id="errorPhone"></span>
          </div>

          <!-- Email -->
          <div class="form-group full">
            <label for="patient_email">Διεύθυνση Email <span class="req">*</span></label>
            <input type="email" id="patient_email" name="patient_email"
                   placeholder="π.χ. maria@example.com"
                   value="<?= htmlspecialchars($old['patient_email'] ?? '') ?>"
                   maxlength="100" autocomplete="email">
            <span class="error-msg" id="errorEmail"></span>
          </div>

          <!-- Λόγος Επίσκεψης -->
          <div class="form-group full">
            <label for="visit_reason">Λόγος Επίσκεψης <span class="req">*</span></label>
            <textarea id="visit_reason" name="visit_reason"
                      placeholder="Περιγράψτε συνοπτικά τον λόγο της επίσκεψής σας..."
                      maxlength="1000"><?= htmlspecialchars($old['visit_reason'] ?? '') ?></textarea>
            <span class="error-msg" id="errorReason"></span>
          </div>

          <!-- Ημερομηνία -->
          <div class="form-group">
            <label for="appointment_date">Επιθυμητή Ημερομηνία <span class="req">*</span></label>
            <input type="date" id="appointment_date" name="appointment_date"
                   value="<?= htmlspecialchars($old['appointment_date'] ?? '') ?>">
            <span class="error-msg" id="errorDate"></span>
          </div>

          <!-- Ώρα -->
          <div class="form-group">
            <label for="appointment_time">Επιθυμητή Ώρα <span class="req">*</span></label>
            <select id="appointment_time" name="appointment_time">
              <option value="">— Επιλέξτε ώρα —</option>
              <?php foreach ($timeSlots as $slot): ?>
                <option value="<?= $slot ?>"
                  <?= (($old['appointment_time'] ?? '') === $slot) ? 'selected' : '' ?>>
                  <?= $slot ?>
                </option>
              <?php endforeach; ?>
            </select>
            <span class="error-msg" id="errorTime"></span>
          </div>

        </div><!-- /form-grid -->

        <div class="mt-3">
          <button type="submit" class="btn btn-primary btn-lg btn-full" id="submitBtn">
            📅 Υποβολή Ραντεβού
          </button>
        </div>

        <p class="text-center mt-2" style="font-size:0.8rem;color:var(--text-muted);">
          🔒 Τα στοιχεία σας προστατεύονται σύμφωνα με τον ΓΚΠΔ (GDPR).
        </p>
      </form>
    </div>
  </div>
</section>

<footer>
  <p>© 2026 MedBook &mdash; Σύστημα Διαχείρισης Ιατρικών Ραντεβού | Developed by <span>SPNT INTUSTRIES </span></p>
</footer>

<script src="js/main.js"></script>
<script src="js/chatbot.js?v=7"></script>
</body>
</html>
