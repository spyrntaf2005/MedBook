<?php session_start(); ?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Σχετικά με εμάς — MedBook</title>
  <meta name="description" content="Λίγα λόγια για το ιατρείο μας, ώρες λειτουργίας και στοιχεία επικοινωνίας.">
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
    <a href="about.php" style="color:var(--primary);font-weight:700;">Σχετικά με εμάς</a>
    <a href="services.php">Οι Υπηρεσίες μας</a>
    <a href="appointment.php">Κλείστε Ραντεβού</a>
  </div>
  <div class="navbar-right">
    <a href="login.php" class="btn-nav-login">Σύνδεση Ιατρού</a>
  </div>
</nav>

<!-- ABOUT SECTION -->
<section class="section" style="min-height: calc(100vh - 140px);">
  <div class="container">
    
    <div class="card" style="max-width: 760px; margin: 0 auto;">
      <h2 class="card-title" style="margin-bottom: 1rem;">👨‍⚕️ Σχετικά με το Ιατρείο μας</h2>
      <p style="line-height:1.7; color:var(--text-muted); margin-bottom:1.5rem;">
        Καλώς ήρθατε στο ιατρείο μας. Παρέχουμε ιατρικές υπηρεσίες υψηλού επιπέδου, με έμφαση στην πρόληψη, την έγκαιρη διάγνωση και τη σωστή θεραπεία. 
        Ο χώρος μας είναι εξοπλισμένος με σύγχρονα μηχανήματα, ενώ παράλληλα φροντίζουμε να διατηρούμε ένα φιλικό και ασφαλές περιβάλλον για όλους τους ασθενείς μας.
      </p>
      <p style="line-height:1.7; color:var(--text-muted);">
        Κύριο μέλημά μας είναι η ανθρώπινη προσέγγιση και η οικοδόμηση μιας σχέσης εμπιστοσύνης με κάθε ασθενή. Μέσω της πλατφόρμας MedBook, μπορείτε να κλείνετε τα ραντεβού σας εύκολα, γρήγορα και με απόλυτη ασφάλεια των προσωπικών σας δεδομένων.
      </p>
    </div>

    <!-- Πληροφορίες Ιατρείου -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:1.2rem;margin-top:2.5rem;max-width:760px;margin-left:auto;margin-right:auto;">
      <div class="card" style="text-align:center;padding:1.5rem;">
        <div style="font-size:2.5rem;margin-bottom:0.5rem;">🕐</div>
        <h3 style="font-size:1rem;font-weight:700;color:var(--dark);">Ώρες Λειτουργίας</h3>
        <p style="font-size:0.85rem;color:var(--text-muted);">Δευτέρα – Παρασκευή<br>09:00 – 18:00</p>
      </div>
      <div class="card" style="text-align:center;padding:1.5rem;">
        <div style="font-size:2.5rem;margin-bottom:0.5rem;">📞</div>
        <h3 style="font-size:1rem;font-weight:700;color:var(--dark);">Επικοινωνία</h3>
        <p style="font-size:0.85rem;color:var(--text-muted);">2101234567<br>info@iatreio.gr</p>
      </div>
      <a href="https://maps.google.com/?q=Λεωφ.+Αθηνών+42,+Αθήνα+10431" target="_blank" rel="noopener noreferrer" style="text-decoration:none;">
        <div class="card" style="text-align:center;padding:1.5rem;cursor:pointer;transition:transform 0.15s ease,box-shadow 0.15s ease;" onmouseover="this.style.transform='translateY(-3px)';this.style.boxShadow='0 8px 24px rgba(0,0,0,0.1)'" onmouseout="this.style.transform='';this.style.boxShadow=''">
          <div style="font-size:2.5rem;margin-bottom:0.5rem;">📍</div>
          <h3 style="font-size:1rem;font-weight:700;color:var(--dark);">Διεύθυνση</h3>
          <p style="font-size:0.85rem;color:var(--text-muted);">Λεωφ. Αθηνών 42<br>Αθήνα, 10431</p>
        </div>
      </a>
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
