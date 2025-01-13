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

function validate_student_groups($data) {
    $valid_groups = [];
    $start_date = strtotime('2025-01-01'); // Początek 2025
    $end_date = strtotime('2025-12-31');  // Koniec 2025

    foreach ($data as $item) {
        if (is_array($item) && isset($item["group_name"], $item["start"])) {
            // Przekształcenie daty startowej na timestamp
            $class_timestamp = strtotime($item["start"]);

            // Sprawdzenie, czy data zajęć mieści się w 2025 roku
            if ($class_timestamp >= $start_date && $class_timestamp <= $end_date) {
                $valid_groups[] = $item["group_name"];
            }
        }
    }

    return !empty($valid_groups) ? $valid_groups : null;
}

function get_group_id_by_name($group_name, $pdo) {
    // Sprawdzenie id grupy w bazie danych
    $query = $pdo->prepare("SELECT id FROM `Group` WHERE group_name = :group_name");
    $query->execute([':group_name' => $group_name]);
    $result = $query->fetch(PDO::FETCH_ASSOC);

    if ($result) {
        return $result['id'];
    }
    return null;
}

function update_student_group($album_number, $group_id, $pdo) {
    try {
        // Zaktualizowanie grupy studenta
        $query = $pdo->prepare('UPDATE Student SET group_id = :group_id WHERE album_number = :album_number');
        $query->execute([':group_id' => $group_id, ':album_number' => $album_number]);
        echo "Student $album_number updated with group_id $group_id\n";
    } catch (PDOException $e) {
        echo "[ERROR] Database error: " . $e->getMessage() . "\n";
    }
}

function insert_student($album_number, $pdo) {
    try {
        // Sprawdzenie, czy student już istnieje w bazie
        $query = $pdo->prepare("SELECT id FROM `Student` WHERE album_number = :album_number");
        $query->execute([':album_number' => $album_number]);
        $result = $query->fetch(PDO::FETCH_ASSOC);

        if (!$result) {
            // Wstawienie studenta
            $insert_query = $pdo->prepare("INSERT INTO `Student` (album_number) VALUES (:album_number)");
            $insert_query->execute([':album_number' => $album_number]);
            echo "Inserted student with album number $album_number\n";
        }
    } catch (PDOException $e) {
        echo "[ERROR] Database error: " . $e->getMessage() . "\n";
    }
}

function process_students() {
    try {
        // Połączenie z bazą danych
        $pdo = new PDO('sqlite:database1.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $valid_students = [];
        for ($album_number = 49000; $album_number <= 60000; $album_number++) {
            $data = fetch_student_groups($album_number);
            if ($data) {
                $groups = validate_student_groups($data);
                if ($groups) {
                    // Zakładając, że przypisujemy pierwszą znalezioną grupę
                    $group_name = $groups[0];
                    $group_id = get_group_id_by_name($group_name, $pdo);

                    if ($group_id) {
                        insert_student($album_number, $pdo);
                        update_student_group($album_number, $group_id, $pdo);
                    } else {
                        echo "Group $group_name not found in the database.\n";
                    }
                }
            }
        }

        echo "Process completed - Student.\n";

    } catch (PDOException $e) {
        echo "[ERROR] Database error: " . $e->getMessage() . "\n";
    }
}

// Uruchomienie procesu
process_students();
?>
