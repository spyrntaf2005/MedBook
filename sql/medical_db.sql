-- ============================================================
-- Σύστημα Διαχείρισης Ιατρικών Ραντεβού
-- Βάση Δεδομένων: medical_db
-- ============================================================

CREATE DATABASE IF NOT EXISTS medical_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE medical_db;

-- -------------------------------------------------------
-- Πίνακας: doctors (Ιατροί / Χρήστες Dashboard)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS doctors (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(50)  NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,
    full_name   VARCHAR(100) NOT NULL,
    specialty   VARCHAR(100) NOT NULL DEFAULT 'Γενικός Ιατρός',
    created_at  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Πίνακας: appointments (Ραντεβού Ασθενών)
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS appointments (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id         INT          NOT NULL,
    `patient_name` varchar(100) NOT NULL,
  `amka` varchar(11) NOT NULL,
  `patient_phone` varchar(20) NOT NULL,
    patient_email     VARCHAR(100) NOT NULL,
    visit_reason      TEXT         NOT NULL,
    appointment_date  DATE         NOT NULL,
    appointment_time  TIME         NOT NULL,
    status            ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
    notes             TEXT         NULL,
    created_at        TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    -- Αποτροπή double-booking: μοναδικός συνδυασμός ημερομηνίας/ώρας/ιατρού
    UNIQUE KEY unique_appointment (doctor_id, appointment_date, appointment_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------------------------------------
-- Εισαγωγή test ιατρού
-- Username: admin | Password: doctor123
-- (bcrypt hash του "doctor123")
-- -------------------------------------------------------
INSERT INTO doctors (username, password, full_name, specialty) VALUES
(
    'admin',
    '$2y$10$q2sFIicG0RuOfzV9TorAXeMuJ4Bi7GQucATdAFPzchq8hS/vhmD4y',
    'Δρ. Γεώργιος Παπαδόπουλος',
    'Παθολόγος'
);

-- -------------------------------------------------------
-- Εισαγωγή demo ραντεβού για testing
-- -------------------------------------------------------
INSERT INTO appointments (doctor_id, amka, patient_name, patient_phone, patient_email, visit_reason, appointment_date, appointment_time, status) VALUES
(1, '12345678901', 'Μαρία Αντωνίου', '6901234567', 'maria@example.com', 'Γενική εξέταση αίματος', '2026-05-10', '09:00:00', 'confirmed'),
(1, '23456789012', 'Κώστας Παπαδάκης', '6977654321', 'kostas@example.com', 'Πόνος στο στήθος', '2026-05-10', '10:00:00', 'pending'),
(1, '34567890123', 'Ελένη Σταύρου', '6955512345', 'eleni@example.com', 'Πονοκέφαλος και ζαλάδες', '2026-05-11', '11:00:00', 'pending'),
(1, '45678901234', 'Νίκος Θεοδώρου', '6933398765', 'nikos@example.com', 'Έλεγχος πίεσης', '2026-05-12', '14:00:00', 'confirmed'),
(1, '56789012345', 'Σοφία Μιχαήλ', '6912312312', 'sofia@example.com', 'Αλλεργική αντίδραση', '2026-05-13', '16:00:00', 'cancelled');
