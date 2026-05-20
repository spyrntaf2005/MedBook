<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);
?>
<nav class="navbar">
  <div class="navbar-brand">
    <span>MedBook</span>
  </div>
  <div class="navbar-center">
    <a href="index.php" <?= $currentPage === 'index.php' ? 'style="color:var(--primary);font-weight:700;"' : '' ?>>Αρχική</a>
    <a href="about.php" <?= $currentPage === 'about.php' ? 'style="color:var(--primary);font-weight:700;"' : '' ?>>Σχετικά με εμάς</a>
    <a href="services.php" <?= $currentPage === 'services.php' ? 'style="color:var(--primary);font-weight:700;"' : '' ?>>Οι Υπηρεσίες μας</a>
    <a href="appointment.php" <?= $currentPage === 'appointment.php' ? 'style="color:var(--primary);font-weight:700;"' : '' ?>>Κλείστε Ραντεβού</a>
  </div>
  <div class="navbar-right">
    <a href="login.php" class="btn-nav-login">Σύνδεση Ιατρού</a>
  </div>
</nav>
