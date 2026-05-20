<?php
/**
 * services.php - Υπηρεσίες
 * Λίστα με τις ιατρικές υπηρεσίες που προσφέρει το ιατρείο.
 */
 session_start(); ?>
<!DOCTYPE html>
<html lang="el">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Οι Υπηρεσίες μας - MedBook</title>
  <meta name="description" content="Γνωρίστε τις ιατρικές υπηρεσίες και τις παροχές του ιατρείου μας.">
  <link rel="stylesheet" href="css/style.css?v=5">
</head>
<body>

<!-- NAVBAR -->
<?php include 'includes/navbar.php'; ?>

<!-- SERVICES SECTION -->
<section class="section" style="min-height: calc(100vh - 140px);">
  <div class="container">
    
    <div style="text-align: center; margin-bottom: 3rem;">
      <h2 style="font-size: 2.2rem; color: var(--dark); margin-bottom: 0.5rem;">🩺 Οι Υπηρεσίες μας</h2>
      <p style="color: var(--text-muted); font-size: 1.1rem;">Ολοκληρωμένη φροντίδα και πρόληψη, προσαρμοσμένη στις δικές σας ανάγκες.</p>
    </div>

    <!-- Services Grid -->
    <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(280px,1fr));gap:2rem;max-width:960px;margin:0 auto;">
      
      <div class="card" style="text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">🩺</div>
        <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);margin-bottom:0.8rem;">Γενικός Έλεγχος (Check-up)</h3>
        <p style="font-size:0.95rem;color:var(--text-muted);line-height:1.6;">
          Πλήρης κλινική εξέταση, αξιολόγηση ιατρικού ιστορικού και καθοδήγηση για τη βελτίωση της γενικής σας υγείας.
        </p>
      </div>

      <div class="card" style="text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">💊</div>
        <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);margin-bottom:0.8rem;">Ηλεκτρονική Συνταγογράφηση</h3>
        <p style="font-size:0.95rem;color:var(--text-muted);line-height:1.6;">
          Άμεση έκδοση ηλεκτρονικών συνταγών και παραπεμπτικών για εργαστηριακές εξετάσεις, με βάση το ιατρικό σας ιστορικό.
        </p>
      </div>

      <div class="card" style="text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">🫀</div>
        <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);margin-bottom:0.8rem;">Ηλεκτροκαρδιογράφημα</h3>
        <p style="font-size:0.95rem;color:var(--text-muted);line-height:1.6;">
          Σύγχρονος καρδιολογικός έλεγχος με ψηφιακό καρδιογράφο, για έγκαιρη πρόληψη και διάγνωση παθήσεων.
        </p>
      </div>

      <div class="card" style="text-align:center;">
        <div style="font-size:3rem;margin-bottom:1rem;">📄</div>
        <h3 style="font-size:1.2rem;font-weight:700;color:var(--dark);margin-bottom:0.8rem;">Πιστοποιητικά Υγείας</h3>
        <p style="font-size:0.95rem;color:var(--text-muted);line-height:1.6;">
          Χορήγηση πιστοποιητικών υγείας για εργασία, σχολείο, αθλητικές δραστηριότητες ή γυμναστήριο.
        </p>
      </div>

    </div>

    <!-- Fine Print Disclaimer -->
    <p style="text-align: center; font-size: 0.85rem; color: var(--text-muted); opacity: 0.7; margin-top: 3rem;">
      * Οι παραπάνω υπηρεσίες είναι ενδεικτικές. Το ιατρείο μας αναλαμβάνει πληθώρα επιπλέον εξειδικευμένων εξετάσεων και θεραπειών. <br>
      Για περισσότερες πληροφορίες, παρακαλούμε επικοινωνήστε μαζί μας.
    </p>
  </div>
</section>

<?php include 'includes/footer.php'; ?>

<script src="js/main.js"></script>
<script src="js/chatbot.js?v=8"></script>
</body>
</html>
