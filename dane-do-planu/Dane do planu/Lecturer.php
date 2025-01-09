<?php

function fetchLecturerDataByLetter($letter) {
    $url = "https://plan.zut.edu.pl/schedule.php?kind=teacher&query=" . $letter;
    
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36\r\n",
            "ignore_errors" => true 
        ],
        "ssl" => [
            "verify_peer" => false, 
            "verify_peer_name" => false, 
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        echo "[ERROR] Fetching data for letter $letter\n";
        return null;
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "[ERROR] Decoding JSON for letter $letter: " . json_last_error_msg() . "\n";
        return null;
    }

    $lecturers = [];
    foreach ($data as $item) {
        if (is_array($item) && array_key_exists('item', $item)) {
            $fullName = $item['item'];
            $nameParts = explode(' ', $fullName);
            if (count($nameParts) >= 2) {
                $firstName = $nameParts[0];
                $lastName = implode(' ', array_slice($nameParts, 1)); // obsługa nazwisk składających się z wielu części
                $lecturers[] = [$firstName, $lastName];
            }
        }
    }

    return $lecturers;
}

function insertIntoDatabase($lecturerData) {
    try {
        $pdo = new PDO('sqlite:database.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $insertQuery = "INSERT OR IGNORE INTO Lecturer (first_name, last_name) VALUES (:first_name, :last_name)";
        $stmt = $pdo->prepare($insertQuery);

        foreach ($lecturerData as $lecturer) {
            $stmt->execute([
                ':first_name' => $lecturer[0],
                ':last_name' => $lecturer[1]
            ]);
        }

        echo "Inserted " . count($lecturerData) . " lecturers into the database.\n"; #ignoring duplicates
    } catch (PDOException $e) {
        echo "[ERROR] Database error: " . $e->getMessage() . "\n";
    }
}

$alphabet = range('A', 'Z');
foreach ($alphabet as $letter) {
    $lecturerData = fetchLecturerDataByLetter($letter);
    if ($lecturerData) {
        insertIntoDatabase($lecturerData);
    }
}

echo "Process completed - Lecturer.\n";
?>
