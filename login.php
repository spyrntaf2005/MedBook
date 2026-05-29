<?php
/**
 * login.php - Σελίδα Σύνδεσης Ιατρού
 * Εδώ οι ιατροί κάνουν αυθεντικοποίηση για να μπουν στο διαχειριστικό.
 */
session_start(); // Έναρξη του session
require_once 'config/db.php'; // Σύνδεση με τη βάση δεδομένων

// Προστασία: Αν είναι ήδη συνδεδεμένος ο ιατρός, δεν υπάρχει λόγος να βλέπει τη login.
// Τον κάνουμε άμεσα ανακατεύθυνση (redirect) στο dashboard.
if (!empty($_SESSION['doctor_id'])) {
    header('Location: dashboard.php');
    exit;
}

// CSRF token: Μηχανισμός προστασίας (αποτροπή πλαστογράφησης αιτήματος από άλλο site)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); // Δημιουργία token
}
$csrf = $_SESSION['csrf_token'];

$error = ''; // Μεταβλητή για αποθήκευση μηνυμάτων λάθους
$maxAttempts = 5; // Μέγιστος επιτρεπτός αριθμός λανθασμένων προσπαθειών
$lockoutTime = 300; // Χρόνος κλειδώματος σε δευτερόλεπτα (5 λεπτά)

/* ΕΠΕΞΕΡΓΑΣΙΑ LOGIN (Όταν ο χρήστης πατάει "Σύνδεση") */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Έλεγχος εγκυρότητας του CSRF token
    if (
        empty($_POST['csrf_token']) ||
        !hash_equals($_SESSION['csrf_token'] ?? '', $_POST['csrf_token'])
    ) {
        $error = 'Μη έγκυρο αίτημα. Παρακαλώ δοκιμάστε ξανά.';
    } else {

        // Brute-force protection: Έλεγχος προσπαθειών για αποτροπή μαζικών δοκιμών κωδικών
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['last_attempt_time'] ?? 0;

        // Αν ξεπέρασε το όριο, κλειδώνουμε το login προσωρινά
        if ($attempts >= $maxAttempts && (time() - $lastAttempt) < $lockoutTime) {
            $remaining = $lockoutTime - (time() - $lastAttempt);
            $error = "Πολλές αποτυχημένες προσπάθειες. Δοκιμάστε ξανά σε " . ceil($remaining/60) . " λεπτό(ά).";
        } else {
            // Αν πέρασε ο χρόνος τιμωρίας, μηδενίζουμε τις προσπάθειες
            if ((time() - $lastAttempt) >= $lockoutTime) {
                $_SESSION['login_attempts'] = 0;
            }

            // Λήψη των στοιχείων που πληκτρολόγησε ο χρήστης
            $username = trim($_POST['username'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($username) || empty($password)) {
                $error = 'Παρακαλώ συμπληρώστε όλα τα πεδία.';
            } else {
                $pdo = getDB();
                // Αναζήτηση του ιατρού στη βάση δεδομένων με βάση το username
                $stmt = $pdo->prepare(
                    "SELECT id, username, password, full_name, specialty
                     FROM doctors
                     WHERE username = :username
                     LIMIT 1"
                );
                $stmt->execute([':username' => $username]);
                $doctor = $stmt->fetch();

                // Έλεγχος αν βρέθηκε ο ιατρός ΚΑΙ αν ο κωδικός ταιριάζει με το κρυπτογραφημένο (hash)
                if ($doctor && password_verify($password, $doctor['password'])) {
                    // Επιτυχής Σύνδεση
                    session_regenerate_id(true); // Αλλαγή του Session ID για ασφάλεια (αποτροπή session fixation)
                    
                    // Αποθήκευση των στοιχείων στο Session για να τον αναγνωρίζουμε σε κάθε σελίδα
                    $_SESSION['doctor_id']    = $doctor['id'];
                    $_SESSION['doctor_name']  = $doctor['full_name'];
                    $_SESSION['doctor_spec']  = $doctor['specialty'];
                    $_SESSION['doctor_user']  = $doctor['username'];
                    
                    // Καθαρισμός των μεταβλητών brute-force
                    unset($_SESSION['login_attempts'], $_SESSION['last_attempt_time']);
                    
                    // Ανακατεύθυνση στον Πίνακα Ελέγχου
                    header('Location: dashboard.php');
                    exit;
                } else {
                    // Αποτυχημένη Σύνδεση
                    $_SESSION['login_attempts'] = ($attempts + 1); // Αυξάνουμε τις λανθασμένες προσπάθειες
                    $_SESSION['last_attempt_time'] = time(); // Καταγράφουμε την ώρα αποτυχίας
                    $remaining = $maxAttempts - ($_SESSION['login_attempts']);
                    
                    $error = 'Λάθος όνομα χρήστη ή κωδικός.' .
                             ($remaining > 0 ? " Απομένουν {$remaining} προσπάθεια(ες)." : '');
                }
            }
        }
    }
    // Ανανέωση του CSRF token μετά από κάθε POST αίτημα για μέγιστη ασφάλεια
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    $csrf = $_SESSION['csrf_token'];
}
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Σύνδεση Ιατρού - MedBook</title>
  <meta name="description" content="Ασφαλής σύνδεση στο σύστημα διαχείρισης ιατρικών ραντεβού.">
  <link rel="stylesheet" href="css/style.css?v=5">
</head>
<body>

<div class="login-wrapper">
  <div class="login-box">

    <div class="login-logo">
      <span class="icon">🩺</span>
      <h2>Καλως ορίσατε, γιατρέ!</h2>
      <p>Παρακαλώ συμπληρώστε τα στοιχεία σύνδεσης σας</p>
    </div>

    <?php if ($error): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form id="loginForm" action="login.php" method="POST" novalidate>
      <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf) ?>">

      <div class="form-group mb-2">
        <label for="username">Όνομα Χρήστη</label>
        <input type="text" id="username" name="username"
               placeholder="Εισάγετε username"
               maxlength="50" autocomplete="username" autofocus>
        <span class="error-msg" id="errorUsername"></span>
      </div>

      <div class="form-group mb-3">
        <label for="password">Κωδικός Πρόσβασης</label>
        <div style="position: relative;">
          <input type="password" id="password" name="password"
                 placeholder="Εισάγετε κωδικό"
                 maxlength="100" autocomplete="current-password"
                 style="padding-right: 40px;">
          <span id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; user-select: none; opacity: 0.7;" title="Πατήστε παρατεταμένα για εμφάνιση">👁️</span>
        </div>
        <span class="error-msg" id="errorPassword"></span>
      </div>

      <button type="submit" class="btn btn-primary btn-full btn-lg">
       Σύνδεση
      </button>
    </form>

    <div class="mt-3 text-center" style="font-size:0.82rem;color:var(--text-muted);">
      <a href="index.php" style="color:var(--primary);">← Επιστροφή στη φόρμα ασθενών</a>
    </div>


  </div>
</div>

<script src="js/main.js"></script>
<script>
  const togglePassword = document.getElementById('togglePassword');
  const passwordInput = document.getElementById('password');

  const showPassword = () => {
    passwordInput.type = 'text';
    togglePassword.style.opacity = '1';
  };

  const hidePassword = () => {
    passwordInput.type = 'password';
    togglePassword.style.opacity = '0.7';
  };

  // Mouse events
  togglePassword.addEventListener('mousedown', showPassword);
  togglePassword.addEventListener('mouseup', hidePassword);
  togglePassword.addEventListener('mouseleave', hidePassword);

  // Touch events for mobile
  togglePassword.addEventListener('touchstart', (e) => {
    e.preventDefault(); // Prevent default to avoid simulating mousedown
    showPassword();
  });
  togglePassword.addEventListener('touchend', hidePassword);
  togglePassword.addEventListener('touchcancel', hidePassword);
</script>
</body>
</html>
