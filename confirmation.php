e<?php
/**
 * confirmation.php - Σελίδα Επιβεβαίωσης Ραντεβού
 */
session_start();

// Αν δεν υπάρχουν δεδομένα επιβεβαίωσης, redirect
if (empty($_SESSION['confirmed_appointment'])) {
    header('Location: index.php');
    exit;
}

$appt = $_SESSION['confirmed_appointment'];
unset($_SESSION['confirmed_appointment']);

$dateFormatted = date('l, d F Y', strtotime($appt['date']));
// Ελληνικές ημέρες
$days = ['Monday'=>'Δευτέρα','Tuesday'=>'Τρίτη','Wednesday'=>'Τετάρτη',
         'Thursday'=>'Πέμπτη','Friday'=>'Παρασκευή','Saturday'=>'Σάββατο','Sunday'=>'Κυριακή'];
$months = ['January'=>'Ιανουαρίου','February'=>'Φεβρουαρίου','March'=>'Μαρτίου',
           'April'=>'Απριλίου','May'=>'Μαΐου','June'=>'Ιουνίου','July'=>'Ιουλίου',
           'August'=>'Αυγούστου','September'=>'Σεπτεμβρίου','October'=>'Οκτωβρίου',
           'November'=>'Νοεμβρίου','December'=>'Δεκεμβρίου'];
foreach ($days as $en => $gr)   $dateFormatted = str_replace($en, $gr, $dateFormatted);
foreach ($months as $en => $gr) $dateFormatted = str_replace($en, $gr, $dateFormatted);
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Επιβεβαίωση Ραντεβού - MedBook</title>
  <meta name="description" content="Το ραντεβού σας έχει καταχωρηθεί επιτυχώς.">
  <link rel="stylesheet" href="css/style.css?v=6">
</head>
<body>

<?php include 'includes/navbar.php'; ?>

<div class="confirm-wrapper">
  <div class="confirm-card">
    <div class="confirm-icon">✅</div>
    <h2>Το Ραντεβού σας Καταχωρήθηκε!</h2>
    <p>Ευχαριστούμε, <strong><?= htmlspecialchars($appt['name']) ?></strong>. Θα επικοινωνήσουμε μαζί σας σύντομα για επιβεβαίωση.</p>

    <div class="confirm-details">
      <div class="detail-row">
        <span class="detail-label">🔖 Αριθμός Ραντεβού</span>
        <span class="detail-value">#<?= htmlspecialchars((string)$appt['id']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">👤 Ασθενής</span>
        <span class="detail-value"><?= htmlspecialchars($appt['name']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">📅 Ημερομηνία</span>
        <span class="detail-value"><?= htmlspecialchars($dateFormatted) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">🕐 Ώρα</span>
        <span class="detail-value"><?= htmlspecialchars($appt['time']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">📞 Τηλέφωνο</span>
        <span class="detail-value"><?= htmlspecialchars($appt['phone']) ?></span>
      </div>
      <div class="detail-row">
        <span class="detail-label">📧 Email</span>
        <span class="detail-value"><?= htmlspecialchars($appt['email']) ?></span>
      </div>
    </div>

    <div class="alert alert-info" style="text-align:left;">
      ℹ️ Ο αριθμός του ραντεβού σας είναι: <strong>#<?= htmlspecialchars((string)$appt['id']) ?></strong>. Για οτιδήποτε χρειαστείτε, επικοινωνήστε μαζί μας τηλεφωνικώς.
    </div>

    <a href="index.php" class="btn btn-primary btn-full">
      ← Κλείσιμο Νέου Ραντεβού
    </a>
  </div>
</div>

<?php include 'includes/footer.php'; ?>

</body>
</html>
