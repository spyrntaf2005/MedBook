<?php
/**
 * index.php - Αρχική Σελίδα
 * Εδώ είναι η κεντρική σελίδα της εφαρμογής MedBook.
 */

session_start();
?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Ιατρικά Ραντεβού - Κλείστε το Ραντεβού σας</title>
  <meta name="description" content="Κλείστε εύκολα και γρήγορα ιατρικό ραντεβού online. Δεν απαιτείται εγγραφή.">
  <link rel="stylesheet" href="css/style.css?v=5">
  <style>
    /* Styling for the large navigation cards */
    .nav-card {
      text-decoration: none;
      color: inherit;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2.5rem 1rem;
      border: 2px solid transparent;
      border-radius: var(--radius);
      background: #fff;
      box-shadow: 0 4px 6px rgba(0,0,0,0.05);
    }
    .nav-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 20px rgba(0,0,0,0.1);
      border-color: var(--primary);
    }
    .nav-card .icon-large {
      font-size: 3.5rem;
      margin-bottom: 1rem;
    }
  </style>
</head>
<body>

<!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>

<!-- HERO -->
<section class="hero">
  <div class="hero-content">
    <h1>Κλείστε το Ραντεβού σας Online</h1>
    <p>Από την άνεση του σπιτιού σας, 24/7.</p>
    <div class="hero-badges">
      <span class="badge">✅ Εύκολα </span>
      <span class="badge">⚡ Γρήγορα </span>
      <span class="badge">🔒 Με ασφάλεια </span>
    </div>
  </div>
</section>

<!-- WELCOME MESSAGE -->
<section style="padding: 3rem 1rem 1rem; max-width: 800px; margin: 0 auto; text-align: center;">
  <h2 style="color: var(--dark); margin-bottom: 1rem; font-size: 1.8rem;">Καλώς ήρθατε στο MedBook</h2>
  <p style="color: var(--text-muted); line-height: 1.7; font-size: 1.05rem;">
    Το <strong>MedBook</strong> είναι η νέα ψηφιακή πλατφόρμα του ιατρείου μας, σχεδιασμένη για δική σας διευκόλυνση. 
    Εδώ μπορείτε να δείτε άμεσα τις διαθέσιμες ώρες και να κλείσετε το ραντεβού σας μέσα σε λίγα λεπτά. 
    Δεν χρειάζεται να δημιουργήσετε λογαριασμό ή να θυμάστε κωδικούς. Επιλέξτε παρακάτω την ενότητα που επιθυμείτε!
  </p>
</section>

<!-- NAVIGATION CARDS -->
<section class="section" style="padding-top: 1.5rem; min-height: calc(100vh - 580px);">
  <div class="container">
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:1.5rem;max-width:960px;margin:0 auto;">
      
      <!-- Card 1 -->
      <a href="appointment.php" class="nav-card">
        <div class="icon-large">📅</div>
        <h3 style="font-size:1.3rem;font-weight:700;color:var(--dark);margin-bottom:0.75rem;">Νέο Ραντεβού</h3>
        <p style="font-size:0.95rem;color:var(--text-muted);text-align:center;">Επιλέξτε ημερομηνία και ώρα και κλείστε άμεσα το ραντεβού σας.</p>
      </a>

      <!-- Card 2 -->
      <a href="about.php" class="nav-card">
        <div class="icon-large">👨‍⚕️</div>
        <h3 style="font-size:1.3rem;font-weight:700;color:var(--dark);margin-bottom:0.75rem;">Το Ιατρείο</h3>
        <p style="font-size:0.95rem;color:var(--text-muted);text-align:center;">Μάθετε πληροφορίες για εμάς, δείτε τις ώρες λειτουργίας και τα στοιχεία επικοινωνίας.</p>
      </a>

      <!-- Card 3 -->
      <a href="services.php" class="nav-card">
        <div class="icon-large">🩺</div>
        <h3 style="font-size:1.3rem;font-weight:700;color:var(--dark);margin-bottom:0.75rem;">Οι Υπηρεσίες μας</h3>
        <p style="font-size:0.95rem;color:var(--text-muted);text-align:center;">Δείτε ενδεικτικά τις ιατρικές υπηρεσίες, τις εξετάσεις και τις παροχές μας.</p>
      </a>

    </div>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="js/main.js"></script>
<script src="js/chatbot.js?v=8"></script>
</body>
</html>
