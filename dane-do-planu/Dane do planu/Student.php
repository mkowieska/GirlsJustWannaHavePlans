<?php
function fetch_student_groups($album_number) {
    $url = "https://plan.zut.edu.pl/schedule_student.php?number=" . $album_number;
    $headers = [
        "User-Agent: Mozilla/5.0"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code !== 200 || !$response) {
        return null;
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return null;
    }

    return $data;
}

function validate_student_groups($data, $album_number) {
    $valid_groups = [];
    $start_date = strtotime('2025-01-01'); // Początek 2025
    $end_date = strtotime('2025-12-31');  // Koniec 2025

    foreach ($data as $item) {
        if (is_array($item) && isset($item["group_name"], $item["start"])) {
            // Przekształcenie daty startowej na timestamp
            $class_timestamp = strtotime($item["start"]);

            // Sprawdzenie, czy data zajęć mieści się w 2025 roku
            if ($class_timestamp >= $start_date && $class_timestamp <= $end_date) {
                $valid_groups[] = $album_number;
            }
        }
    }

    return !empty($valid_groups) ? $valid_groups : null;
}

function insert_student($album_number) {
    try {
        $db = new PDO('sqlite:database.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $query = $db->prepare("SELECT id FROM `Student` WHERE album_number = :album_number");
        $query->execute([':album_number' => $album_number]);
        $result = $query->fetch(PDO::FETCH_ASSOC);

        $added_count = 0;
    
        if (!$result) {
            $insert_query = $db->prepare("INSERT INTO `Student` (album_number) VALUES (:album_number)");
            $insert_query->execute([':album_number' => $album_number]);
            if ($insert_query->rowCount() > 0) { 
                $added_count++;
            }
        }
        echo "Inserted " . $added_count . " student into the database.\n"; // 1 wstawia, 0 nie wstawia
    } catch (PDOException $e) {
        // W przypadku błędu bazy danych można dodać logowanie, jeśli jest potrzebne
        echo "[ERROR] Database error: " . $e->getMessage() . "\n";
    }
}

$valid_students = [];
for ($album_number = 49000; $album_number <= 60000; $album_number++) {

    $data = fetch_student_groups($album_number);
    if ($data) {
        $groups = validate_student_groups($data, $album_number);
        if ($groups) {
            $valid_students[] = $album_number;
            insert_student($album_number);
        }
    }
}

echo "Process completed - Student.\n";
