<?php
/**
 * config/db.php
 * Σύνδεση με MySQL μέσω PDO (MAMP)
 * Αυτό το αρχείο περιέχει τις ρυθμίσεις για τη σύνδεση με τη βάση δεδομένων
 * και διασφαλίζει ότι δημιουργείται μόνο μία σύνδεση (Singleton pattern)
 * για εξοικονόμηση πόρων.
 */

// Ορισμός σταθερών (constants) με τα στοιχεία σύνδεσης (Credentials)
define('DB_HOST', '127.0.0.1'); // Ο server (συνήθως localhost)
define('DB_PORT', '8889');       // MAMP default MySQL port
define('DB_NAME', 'medical_db'); // Το όνομα της βάσης δεδομένων
define('DB_USER', 'root');       // Το username της MySQL
define('DB_PASS', 'root');       // Το password της MySQL (προεπιλογή MAMP)
define('DB_CHARSET', 'utf8mb4'); // Κωδικοποίηση χαρακτήρων για υποστήριξη Ελληνικών & Emojis

/**
 * Επιστρέφει PDO connection singleton
 * Δηλαδή, αν η σύνδεση έχει ήδη ανοίξει, επιστρέφει την ίδια, 
 * αλλιώς δημιουργεί μια νέα.
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        // Δημιουργία του Data Source Name (DSN) string
        $dsn = "mysql:host=" . DB_HOST
             . ";port=" . DB_PORT
             . ";dbname=" . DB_NAME
             . ";charset=" . DB_CHARSET;

        // Ρυθμίσεις ασφαλείας και λειτουργίας του PDO
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Πέταξε Exception αν υπάρξει σφάλμα SQL
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Επέστρεψε τα αποτελέσματα ως associative array
            PDO::ATTR_EMULATE_PREPARES   => false, // Πραγματικά prepared statements (ύψιστη προστασία από SQL Injection)
        ];

        try {
            // Προσπάθεια σύνδεσης με τη βάση
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Αν αποτύχει, καταγράφουμε το λάθος στο log του server και σταματάμε την εκτέλεση (die)
            // Στη production ΔΕΝ εμφανίζουμε τεχνικά σφάλματα στον χρήστη για λόγους ασφαλείας
            error_log("DB Connection Error: " . $e->getMessage());
            die(json_encode(['error' => 'Σφάλμα σύνδεσης με τη βάση δεδομένων. Παρακαλώ επικοινωνήστε με τον διαχειριστή.']));
        }
    }

    return $pdo;
}
