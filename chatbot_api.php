<?php
session_start();
require_once 'config/db.php';

header('Content-Type: application/json');

function getGeminiResponse($prompt) {
    $apiKey = 'AIzaSyBexbh7j9yA08T3xoK3DxrJlHTljgVrxkk';
    $url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-flash-latest:generateContent?key=' . $apiKey;
    
    $systemInstruction = "Είσαι ο ψηφιακός βοηθός ιατρού (παθολόγου) με την ονομασία 'Βοηθός Ιατρείου'. Απαντάς σε απορίες για θεραπείες και προτείνεις ενδεικτική αντιμετώπιση ή φάρμακα, ΑΛΛΑ είναι υποχρεωτικό σε ΚΑΘΕ σου απάντηση που αφορά θέματα υγείας να τονίζεις ξεκάθαρα στην αρχή ή στο τέλος ότι οι συμβουλές σου είναι καθαρά ΕΝΔΕΙΚΤΙΚΕΣ, να προειδοποιείς τον ασθενή να είναι προσεκτικός, να αναφέρεις ρητά ότι ΑΥΤΕΣ ΔΕΝ ΕΙΝΑΙ ΕΠΙΣΗΜΕΣ ΙΑΤΡΙΚΕΣ ΣΥΜΒΟΥΛΕΣ, και να του προτείνεις ΠΑΝΤΑ να συμβουλεύεται έναν ειδικό-εξειδικευμένο αρμόδιο ιατρό για το πρόβλημά του. Η απάντησή σου πρέπει να είναι στα ελληνικά, φιλική, ευγενική και επαγγελματική. Μην χρησιμοποιείς υπερβολικά μεγάλα κείμενα, να είσαι περιεκτικός.";
    
    $data = [
        "contents" => [
            ["role" => "user", "parts" => [["text" => $prompt]]]
        ],
        "systemInstruction" => [
            "role" => "user",
            "parts" => [["text" => $systemInstruction]]
        ],
        "generationConfig" => [
            "temperature" => 0.4
        ]
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200 || !$response) {
        return "Συγγνώμη, υπάρχει κάποιο πρόβλημα με την υπηρεσία AI αυτή τη στιγμή. Παρακαλώ δοκιμάστε αργότερα ή καλέστε στο ιατρείο.";
    }
    
    $json = json_decode($response, true);
    if (isset($json['candidates'][0]['content']['parts'][0]['text'])) {
        return trim($json['candidates'][0]['content']['parts'][0]['text']);
    }
    
    return "Συγγνώμη, δεν μπόρεσα να δημιουργήσω μια απάντηση. Παρακαλώ καλέστε στο ιατρείο.";
}

$message = trim($_POST['message'] ?? '');
if (empty($message)) {
    echo json_encode(['reply' => 'Παρακαλώ γράψτε ένα μήνυμα.']);
    exit;
}

$messageLower = mb_strtolower($message);

// Initialize state if not exists
if (!isset($_SESSION['chat_state'])) {
    $_SESSION['chat_state'] = 'idle';
}

$state = $_SESSION['chat_state'];
$pdo = getDB();

try {
    if ($message === 'reset_chat_session') {
        $_SESSION['chat_state'] = 'idle';
        unset($_SESSION['chat_appt_id'], $_SESSION['chat_action'], $_SESSION['chat_new_date']);
        echo json_encode(['reply' => 'reset_ok']);
        exit;
    }

    if ($messageLower === 'ακυρο' || $messageLower === 'έξοδος' || $messageLower === 'εξοδος') {
        $_SESSION['chat_state'] = 'idle';
        unset($_SESSION['chat_appt_id'], $_SESSION['chat_action'], $_SESSION['chat_new_date']);
        echo json_encode(['reply' => 'Η διαδικασία ακυρώθηκε. Πώς αλλιώς μπορώ να βοηθήσω;']);
        exit;
    }

    if ($state === 'idle') {
        if (str_contains($messageLower, 'αλλαγ') || str_contains($messageLower, 'μεταθεσ')) {
            $_SESSION['chat_state'] = 'wait_id';
            $_SESSION['chat_action'] = 'change';
            echo json_encode(['reply' => 'Για να αλλάξουμε το ραντεβού σας, παρακαλώ γράψτε μου τον αριθμό του ραντεβού (#ID) ή το ΑΜΚΑ σας.']);
            exit;
        } elseif (str_contains($messageLower, 'ακυρωσ')) {
            $_SESSION['chat_state'] = 'wait_id';
            $_SESSION['chat_action'] = 'cancel';
            echo json_encode(['reply' => 'Για να ακυρώσουμε το ραντεβού σας, παρακαλώ γράψτε μου τον αριθμό του ραντεβού (#ID) ή το ΑΜΚΑ σας.']);
            exit;
        } else {
            $aiReply = getGeminiResponse($message);
            $aiReplyHtml = htmlspecialchars($aiReply);
            $aiReplyHtml = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $aiReplyHtml);
            $aiReplyHtml = preg_replace('/__(.*?)__/', '<em>$1</em>', $aiReplyHtml);
            $aiReplyHtml = nl2br($aiReplyHtml);
            
            echo json_encode(['reply' => $aiReplyHtml]);
            exit;
        }
    }

    if ($state === 'wait_id') {
        $idOrAmka = preg_replace('/[^0-9]/', '', $message);
        if (empty($idOrAmka)) {
            echo json_encode(['reply' => 'Δεν αναγνώρισα κάποιον αριθμό. Παρακαλώ δώστε το ΑΜΚΑ (11 ψηφία) ή το #ID του ραντεβού σας.']);
            exit;
        }

        if (strlen($idOrAmka) === 11) {
            $stmt = $pdo->prepare("SELECT * FROM appointments WHERE amka = :val AND status != 'cancelled' ORDER BY appointment_date DESC LIMIT 1");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM appointments WHERE id = :val AND status != 'cancelled' ORDER BY appointment_date DESC LIMIT 1");
        }
        $stmt->execute([':val' => $idOrAmka]);
        $appt = $stmt->fetch();

        if (!$appt) {
            echo json_encode(['reply' => 'Δεν βρέθηκε ενεργό ραντεβού με αυτά τα στοιχεία. Προσπαθήστε ξανά ή γράψτε "άκυρο".']);
            exit;
        }

        $_SESSION['chat_appt_id'] = $appt['id'];
        $action = $_SESSION['chat_action'];

        $dateFormatted = date('d/m/Y', strtotime($appt['appointment_date']));
        $timeFormatted = substr($appt['appointment_time'], 0, 5);

        if ($action === 'cancel') {
            $_SESSION['chat_state'] = 'confirm_cancel';
            echo json_encode(['reply' => "Βρήκα το ραντεβού σας στις {$dateFormatted} και ώρα {$timeFormatted}. Είστε σίγουροι ότι θέλετε να το ακυρώσετε; (Απαντήστε με 'Ναι' ή 'Όχι')"]);
            exit;
        } else {
            $_SESSION['chat_state'] = 'wait_date';
            echo json_encode(['reply' => "Βρήκα το ραντεβού σας στις {$dateFormatted} και ώρα {$timeFormatted}. Σε ποια νέα ημερομηνία θέλετε να το μεταφέρετε; (Γράψτε στη μορφή ΗΗ/ΜΜ/ΕΕΕΕ, π.χ. 30/05/2026)"]);
            exit;
        }
    }

    if ($state === 'confirm_cancel') {
        if (str_contains($messageLower, 'ναι')) {
            $stmt = $pdo->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = :id");
            $stmt->execute([':id' => $_SESSION['chat_appt_id']]);
            $_SESSION['chat_state'] = 'idle';
            echo json_encode(['reply' => 'Το ραντεβού σας ακυρώθηκε επιτυχώς. Σας ευχαριστούμε!']);
            exit;
        } else {
            $_SESSION['chat_state'] = 'idle';
            echo json_encode(['reply' => 'Η ακύρωση σταμάτησε. Το ραντεβού σας παραμένει ως έχει.']);
            exit;
        }
    }

    if ($state === 'wait_date') {
        if (!preg_match('/^(\d{2})[\/\-\.](\d{2})[\/\-\.](\d{4})$/', $message, $matches)) {
            echo json_encode(['reply' => 'Η μορφή της ημερομηνίας δεν είναι σωστή. Παρακαλώ γράψτε στη μορφή ΗΗ/ΜΜ/ΕΕΕΕ (π.χ. 30/05/2026) ή γράψτε "άκυρο".']);
            exit;
        }
        
        $day = (int)$matches[1];
        $month = (int)$matches[2];
        $year = (int)$matches[3];
        
        if (!checkdate($month, $day, $year)) {
            echo json_encode(['reply' => 'Η ημερομηνία αυτή δεν είναι έγκυρη στο ημερολόγιο. Δοκιμάστε ξανά.']);
            exit;
        }

        $convertedDate = sprintf('%04d-%02d-%02d', $year, $month, $day);

        if ($convertedDate < date('Y-m-d')) {
            echo json_encode(['reply' => 'Δεν μπορείτε να επιλέξετε παρελθοντική ημερομηνία. Παρακαλώ δώστε νέα ημερομηνία.']);
            exit;
        }
        $_SESSION['chat_new_date'] = $convertedDate;
        $_SESSION['chat_state'] = 'wait_time';
        echo json_encode(['reply' => "Τέλεια. Τι ώρα επιθυμείτε; (Η μορφή πρέπει να είναι ΩΩ:ΛΛ, από 09:00 έως 17:30 ανά μισή ώρα, π.χ. 14:30)"]);
        exit;
    }

    if ($state === 'wait_time') {
        $time = $message;
        $validTimes = [];
        for ($h = 9; $h < 18; $h++) { $validTimes[] = sprintf('%02d:00',$h); $validTimes[] = sprintf('%02d:30',$h); }
        
        if (!in_array($time, $validTimes, true)) {
            echo json_encode(['reply' => 'Μη έγκυρη ώρα. Το ιατρείο λειτουργεί 09:00 - 18:00 ανά 30 λεπτά (π.χ. 10:30, 11:00). Δοκιμάστε ξανά.']);
            exit;
        }

        $date = $_SESSION['chat_new_date'];
        $id = $_SESSION['chat_appt_id'];

        $chk = $pdo->prepare("SELECT COUNT(*) AS cnt FROM appointments WHERE doctor_id = 1 AND appointment_date = :date AND appointment_time = :time AND status != 'cancelled' AND id != :id");
        $chk->execute([':date' => $date, ':time' => $time, ':id' => $id]);
        
        if ((int)$chk->fetch()['cnt'] > 0) {
            echo json_encode(['reply' => 'Δυστυχώς η ώρα αυτή είναι ήδη κατειλημμένη. Παρακαλώ επιλέξτε άλλη ώρα ή γράψτε "άκυρο".']);
            exit;
        }

        $upd = $pdo->prepare("UPDATE appointments SET appointment_date = :date, appointment_time = :time, status = 'pending' WHERE id = :id");
        $upd->execute([':date' => $date, ':time' => $time, ':id' => $id]);

        $_SESSION['chat_state'] = 'idle';
        $formattedDate = date('d/m/Y', strtotime($date));
        echo json_encode(['reply' => "Το ραντεβού σας μεταφέρθηκε επιτυχώς για τις {$formattedDate} στις {$time}. Θα λάβετε σύντομα επιβεβαίωση!"]);
        exit;
    }

} catch (Exception $e) {
    echo json_encode(['reply' => 'Προέκυψε ένα σφάλμα στο σύστημα. Παρακαλώ επικοινωνήστε με το ιατρείο τηλεφωνικά.']);
}
