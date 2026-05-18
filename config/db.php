<?php
/**
 * config/db.php
 * Σύνδεση με MySQL μέσω PDO (MAMP)
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '8889');       // MAMP default MySQL port
define('DB_NAME', 'medical_db');
define('DB_USER', 'root');
define('DB_PASS', 'root');       // MAMP default password
define('DB_CHARSET', 'utf8mb4');

/**
 * Επιστρέφει PDO connection singleton
 */
function getDB(): PDO {
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST
             . ";port=" . DB_PORT
             . ";dbname=" . DB_NAME
             . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Πραγματικά prepared statements
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Στη production ΔΕΝ εμφανίζουμε τεχνικά σφάλματα
            error_log("DB Connection Error: " . $e->getMessage());
            die(json_encode(['error' => 'Σφάλμα σύνδεσης με τη βάση δεδομένων. Παρακαλώ επικοινωνήστε με τον διαχειριστή.']));
        }
    }

    return $pdo;
}
