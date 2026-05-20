<?php
// Έναρξη του Session για να μπορούμε να παρακολουθούμε την κατάσταση (state) της συνομιλίας του χρήστη
session_start();

// Εισαγωγή του αρχείου σύνδεσης με τη βάση δεδομένων
require_once 'config/db.php';

// Ορίζουμε ότι ο server θα επιστρέψει δεδομένα σε μορφή JSON
header('Content-Type: application/json');

/**
 * Συνάρτηση για επικοινωνία με το Google Gemini API
 * Παίρνει ως όρισμα το μήνυμα του χρήστη ($prompt) και επιστρέφει την απάντηση της Τεχνητής Νοημοσύνης.
 */
function getGeminiResponse($prompt) {
    // Το κλειδί πρόσβασης για το API της Google φορτώνεται πλέον από ασφαλές αρχείο
    $secrets = require 'config/secrets.php';
    $apiKey = $secrets['gemini_api_key'];
    
    // Το URL του μοντέλου gemini-flash-latest
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $apiKey;
    
    // Οι βασικές "οδηγίες συμπεριφοράς" (System Instructions) που δίνουμε στο AI
    $systemInstruction = "Είσαι ο ψηφιακός βοηθός ιατρού (παθολόγου) με την ονομασία 'Βοηθός Ιατρείου'. Απαντάς σε απορίες για θεραπείες και προτείνεις ενδεικτική αντιμετώπιση ή φάρμακα, ΑΛΛΑ είναι υποχρεωτικό σε ΚΑΘΕ σου απάντηση που αφορά θέματα υγείας να τονίζεις ξεκάθαρα στην αρχή ή στο τέλος ότι οι συμβουλές σου είναι καθαρά ΕΝΔΕΙΚΤΙΚΕΣ, να προειδοποιείς τον ασθενή να είναι προσεκτικός, να αναφέρεις ρητά ότι ΑΥΤΕΣ ΔΕΝ ΕΙΝΑΙ ΕΠΙΣΗΜΕΣ ΙΑΤΡΙΚΕΣ ΣΥΜΒΟΥΛΕΣ, και να του προτείνεις ΠΑΝΤΑ να συμβουλεύεται έναν ειδικό-εξειδικευμένο αρμόδιο ιατρό για το πρόβλημά του. Η απάντησή σου πρέπει να είναι στα ελληνικά, φιλική, ευγενική και επαγγελματική. Μην χρησιμοποιείς υπερβολικά μεγάλα κείμενα, να είσαι περιεκτικός.";
    
    // Δημιουργία του payload (δεδομένων) που θα σταλεί στο API
    $data = [
        "contents" => [
            ["role" => "user", "parts" => [["text" => $prompt]]]
        ],
        "systemInstruction" => [
            "role" => "user",
            "parts" => [["text" => $systemInstruction]]
        ],
        "generationConfig" => [
            "temperature" => 0.4 // Ρύθμιση της δημιουργικότητας του μοντέλου (0.4 σημαίνει αρκετά στοχευμένες απαντήσεις)
        ]
    ];
    
    // Αρχικοποίηση του cURL για την αποστολή HTTP POST αιτήματος
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    // Εκτέλεση του αιτήματος και λήψη της απάντησης
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    // Έλεγχος αν η κλήση απέτυχε
    if ($httpCode !== 200 || !$response) {
        return "Συγγνώμη, υπάρχει κάποιο πρόβλημα με την υπηρεσία AI αυτή τη στιγμή. Παρακαλώ δοκιμάστε αργότερα ή καλέστε στο ιατρείο.";
    }
    
    // Αποκωδικοποίηση της JSON απάντησης της Google
    $json = json_decode($response, true);
    
    // Επιστροφή του κειμένου της απάντησης, αν υπάρχει
    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($json['candidates'][0]['content']['parts'][0]['text']);
    }
    
    return "Συγγνώμη, δεν μπόρεσα να δημιουργήσω μια απάντηση. Παρακαλώ καλέστε στο ιατρείο.";
}

// Λήψη του μηνύματος που έστειλε ο χρήστης μέσω POST
$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    echo json_encode(['reply' => 'Παρακαλώ γράψτε ένα μήνυμα.']);
    exit;
}

// Μετατροπή σε πεζά για ευκολότερη σύγκριση λέξεων (π.χ. "ΑΚΥΡΟ" -> "άκυρο")
$messageLower = mb_strtolower($message);

// Αρχικοποίηση της Κατάστασης Συνομιλίας (State Machine) αν δεν υπάρχει
if (!isset($_SESSION['chat_state'])) {
    $_SESSION['chat_state'] = 'idle'; // idle = Το chatbot περιμένει γενικές ερωτήσεις
}

$state = $_SESSION['chat_state'];
$pdo = getDB(); // Σύνδεση με τη βάση δεδομένων

try {
    // Κρυφή εντολή για επαναφορά ολόκληρου του session συνομιλίας (χρήσιμο για debugging)
    if ($message === 'reset_chat_session') {
        $_SESSION['chat_state'] = 'idle';
        unset($_SESSION['chat_appt_id'], $_SESSION['chat_action'], $_SESSION['chat_new_date']);
        echo json_encode(['reply' => 'reset_ok']);
        exit;
    }

    // Αν ο χρήστης θέλει να ακυρώσει τη διαδικασία αλλαγής/ακύρωσης που ξεκίνησε
    if ($messageLower === 'ακυρο' || $messageLower === 'έξοδος' || $messageLower === 'εξοδος') {
        $_SESSION['chat_state'] = 'idle';
        unset($_SESSION['chat_appt_id'], $_SESSION['chat_action'], $_SESSION['chat_new_date']);
        echo json_encode(['reply' => 'Η διαδικασία ακυρώθηκε. Πώς αλλιώς μπορώ να βοηθήσω;']);
        exit;
    }

    // STATE: IDLE (Γενική Κατάσταση)
    if ($state === 'idle') {
        // Αν ο χρήστης ζητήσει αλλαγή/μετάθεση του ραντεβού του
        if (str_contains($messageLower, 'αλλαγ') || str_contains($messageLower, 'μεταθεσ')) {
            $_SESSION['chat_state'] = 'wait_id'; // Αλλάζουμε την κατάσταση σε 'Αναμονή ID'
            $_SESSION['chat_action'] = 'change'; // Καταγράφουμε ότι θέλει να κάνει "αλλαγή"
            echo json_encode(['reply' => 'Για να αλλάξουμε το ραντεβού σας, παρακαλώ γράψτε μου τον αριθμό του ραντεβού (#ID) ή το ΑΜΚΑ σας.']);
            exit;
        } 
        // Αν ο χρήστης ζητήσει ακύρωση του ραντεβού του
        elseif (str_contains($messageLower, 'ακυρωσ')) {
            $_SESSION['chat_state'] = 'wait_id'; // Αλλάζουμε την κατάσταση σε 'Αναμονή ID'
            $_SESSION['chat_action'] = 'cancel'; // Καταγράφουμε ότι θέλει να κάνει "ακύρωση"
            echo json_encode(['reply' => 'Για να ακυρώσουμε το ραντεβού σας, παρακαλώ γράψτε μου τον αριθμό του ραντεβού (#ID) ή το ΑΜΚΑ σας.']);
            exit;
        } 
        // Αν δεν ζητάει ούτε αλλαγή ούτε ακύρωση, η ερώτηση πηγαίνει στο AI (Gemini API)
        else {
            $aiReply = getGeminiResponse($message);
            
            // Μορφοποίηση της απάντησης του AI σε HTML (π.χ. μετατροπή **bold** σε <strong>)
            $aiReplyHtml = htmlspecialchars($aiReply);
            $aiReplyHtml = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $aiReplyHtml);
            $aiReplyHtml = preg_replace('/__(.*?)__/', '<em>$1</em>', $aiReplyHtml);
            $aiReplyHtml = nl2br($aiReplyHtml); // Αλλαγές γραμμής (Enter)
            
            echo json_encode(['reply' => $aiReplyHtml]);
            exit;
        }
    }

    // STATE: WAIT_ID (Αναμονή για να δώσει ο χρήστης ΑΜΚΑ ή ID)
    if ($state === 'wait_id') {
        // Κρατάμε μόνο τους αριθμούς από το μήνυμα
        $idOrAmka = preg_replace('/[^0-9]/', '', $message);
        if (empty($idOrAmka)) {
            echo json_encode(['reply' => 'Δεν αναγνώρισα κάποιον αριθμό. Παρακαλώ δώστε το ΑΜΚΑ (11 ψηφία) ή το #ID του ραντεβού σας.']);
            exit;
        }

        // Αν είναι 11 ψηφία, σημαίνει ότι είναι ΑΜΚΑ, αλλιώς υποθέτουμε ότι είναι ID ραντεβού
        if (strlen($idOrAmka) === 11) {
            $stmt = $pdo->prepare("SELECT * FROM appointments WHERE amka = :val AND status != 'cancelled' ORDER BY appointment_date DESC LIMIT 1");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = :val AND status != 'cancelled' ORDER BY appointment_date DESC LIMIT 1");
        }
        $stmt->execute([':val' => $idOrAmka]);
        $appt = $stmt->fetch();

        // Έλεγχος αν βρέθηκε ραντεβού στη βάση
        if (!$appt) {
            echo json_encode(['reply' => 'Δεν βρέθηκε ενεργό ραντεβού με αυτά τα στοιχεία. Προσπαθήστε ξανά ή γράψτε "άκυρο".']);
            exit;
        }

        // Αποθήκευση του ID του ραντεβού στο Session
        $_SESSION['chat_appt_id'] = $appt['id'];
        $action = $_SESSION['chat_action'];

        // Μορφοποίηση ημερομηνίας/ώρας για να είναι πιο φιλική στην οθόνη
        $dateFormatted = date('d/m/Y', strtotime($appt['appointment_date']));
        $timeFormatted = substr($appt['appointment_time'], 0, 5);

        // Αν ήθελε ακύρωση, ζητάμε επιβεβαίωση
        if ($action === 'cancel') {
            $_SESSION['chat_state'] = 'confirm_cancel';
            echo json_encode(['reply' => "Βρήκα το ραντεβού σας στις {$dateFormatted} και ώρα {$timeFormatted}. Είστε σίγουροι ότι θέλετε να το ακυρώσετε; (Απαντήστε με 'Ναι' ή 'Όχι')"]);
            exit;
        } 
        // Αν ήθελε αλλαγή, ζητάμε τη νέα ημερομηνία
        else {
            $_SESSION['chat_state'] = 'wait_date';
            echo json_encode(['reply' => "Βρήκα το ραντεβού σας στις {$dateFormatted} και ώρα {$timeFormatted}. Σε ποια νέα ημερομηνία θέλετε να το μεταφέρετε; (Γράψτε στη μορφή ΗΗ/ΜΜ/ΕΕΕΕ, π.χ. 30/05/2026)"]);
            exit;
        }
    }

    // STATE: CONFIRM_CANCEL (Επιβεβαίωση Ακύρωσης)
    if ($state === 'confirm_cancel') {
        if (str_contains($messageLower, 'ναι')) {
            // Ενημέρωση της βάσης: Το status γίνεται 'cancelled'
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['chat_appt_id']]);
            
            $_SESSION['chat_state'] = 'idle'; // Επιστροφή στην αρχική κατάσταση
            echo json_encode(['reply' => 'Το ραντεβού σας ακυρώθηκε επιτυχώς. Σας ευχαριστούμε!']);
            exit;
        } else {
            $_SESSION['chat_state'] = 'idle';
            echo json_encode(['reply' => 'Η ακύρωση σταμάτησε. Το ραντεβού σας παραμένει ως έχει.']);
            exit;
        }
    }

    // STATE: WAIT_DATE (Αναμονή Νέας Ημερομηνίας για την Αλλαγή Ραντεβού)
    if ($state === 'wait_date') {
        // Έλεγχος με Regular Expression (RegEx) αν η ημερομηνία είναι στη σωστή μορφή ΗΗ/ΜΜ/ΕΕΕΕ
        if (!preg_match('/^(\d{2})[\/\-\.](\d{2})[\/\-\.](\d{4})$/', $message, $matches)) {
            echo json_encode(['reply' => 'Η μορφή της ημερομηνίας δεν είναι σωστή. Παρακαλώ γράψτε στη μορφή ΗΗ/ΜΜ/ΕΕΕΕ (π.χ. 30/05/2026) ή γράψτε "άκυρο".']);
            exit;
        }
        
        $day = (int)$matches[1];
        $month = (int)$matches[2];
        $year = (int)$matches[3];
        
        // Έλεγχος αν η ημερομηνία είναι λογική/υπαρκτή (π.χ. όχι 32/13/2026)
        if (!checkdate($month, $day, $year)) {
            echo json_encode(['reply' => 'Η ημερομηνία αυτή δεν είναι έγκυρη στο ημερολόγιο. Δοκιμάστε ξανά.']);
            exit;
        }

        // Μετατροπή στο format της βάσης MySQL (ΕΕΕΕ-ΜΜ-ΗΗ)
        $convertedDate = sprintf('%04d-%02d-%02d', $year, $month, $day);

        // Απαγόρευση επιλογής παλιάς ημερομηνίας
        if ($convertedDate < date('Y-m-d')) {
            echo json_encode(['reply' => 'Δεν μπορείτε να επιλέξετε παρελθοντική ημερομηνία. Παρακαλώ δώστε νέα ημερομηνία.']);
            exit;
        }
        
        // Αποθήκευση της νέας ημερομηνίας και προχώρημα στην επιλογή ώρας
        $_SESSION['chat_new_date'] = $convertedDate;
        $_SESSION['chat_state'] = 'wait_time';
        echo json_encode(['reply' => "Τέλεια. Τι ώρα επιθυμείτε; (Η μορφή πρέπει να είναι ΩΩ:ΛΛ, από 09:00 έως 17:30 ανά μισή ώρα, π.χ. 14:30)"]);
        exit;
    }

    // STATE: WAIT_TIME (Αναμονή Νέας Ώρας για την Αλλαγή Ραντεβού)
    if ($state === 'wait_time') {
        $time = $message;
        $validTimes = [];
        
        // Δημιουργία λίστας με τις αποδεκτές ώρες (09:00 - 17:30)
        for ($h = 9; $h < 18; $h++) { 
            $validTimes[] = sprintf('%02d:00',$h); 
            $validTimes[] = sprintf('%02d:30',$h); 
        }
        
        if (!in_array($time, $validTimes, true)) {
            echo json_encode(['reply' => 'Μη έγκυρη ώρα. Το ιατρείο λειτουργεί 09:00 - 18:00 ανά 30 λεπτά (π.χ. 10:30, 11:00). Δοκιμάστε ξανά.']);
            exit;
        }

        $date = $_SESSION['chat_new_date'];
        $id = $_SESSION['chat_appt_id'];

        // Έλεγχος αν η συγκεκριμένη μέρα/ώρα είναι ήδη πιασμένη από άλλο ραντεβού
        $chk = $pdo->prepare("SELECT COUNT(*) AS cnt FROM appointments WHERE doctor_id = 1 AND appointment_date = :date AND appointment_time = :time AND status != 'cancelled' AND id != :id");
        $chk->execute([':date' => $date, ':time' => $time, ':id' => $id]);
        
        if ((int)$chk->fetch()['cnt'] > 0) {
            echo json_encode(['reply' => 'Δυστυχώς η ώρα αυτή είναι ήδη κατειλημμένη. Παρακαλώ επιλέξτε άλλη ώρα ή γράψτε "άκυρο".']);
            exit;
        }

        // Οριστική Ενημέρωση (Update) του ραντεβού στη Βάση Δεδομένων
        $upd = $pdo->prepare("UPDATE appointments SET appointment_date = :date, appointment_time = :time, status = 'pending' WHERE id = :id");
        $upd->execute([':date' => $date, ':time' => $time, ':id' => $id]);

        // Ολοκλήρωση διαδικασίας και επιστροφή σε κατάσταση idle
        $_SESSION['chat_state'] = 'idle';
        $formattedDate = date('d/m/Y', strtotime($date));
        echo json_encode(['reply' => "Το ραντεβού σας μεταφέρθηκε επιτυχώς για τις {$formattedDate} στις {$time}. Θα λάβετε σύντομα επιβεβαίωση!"]);
        exit;
    }

} catch (Exception $e) {
    // Διαχείριση λαθών σε περίπτωση που πέσει ο server ή η βάση
    echo json_encode(['reply' => 'Προέκυψε ένα σφάλμα στο σύστημα. Παρακαλώ επικοινωνήστε με το ιατρείο τηλεφωνικά.']);
}
