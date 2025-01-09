<?php

function fetchSubjectDataByQuery($query) {
    echo "Fetching data for subjects with query '$query'...\n";
    $url = "https://plan.zut.edu.pl/schedule.php?kind=subject&query=" . urlencode($query);

    $headers = [
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Wyłączona weryfikacja SSL (do testów)

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        echo "Error fetching data for query '$query': " . curl_error($ch) . "\n";
        curl_close($ch);
        return null;
    }

    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200) {
        echo "Error fetching data for query '$query': HTTP $httpCode\n";
        return null;
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error decoding JSON for query '$query': " . json_last_error_msg() . "\n";
        return null;
    }

    $subjects = [];
    $uniqueSubjects = [];

    foreach ($data as $item) {
        if (is_array($item) && isset($item['item'])) {
            $subjectName = $item['item'];
            if (stripos($subjectName, $query) === 0 && !isset($uniqueSubjects[$subjectName])) { // Dodano sprawdzanie pierwszej litery
            // unikniecie sytuacji, gdzie w konsoli wyswietla sie "Unique index ensured on 'name' column in 'Subject' table. 
            // Starting to fetch data for all subjects A-Z... Fetching data for subjects with query 'A'...
            // Extracted 10227 subjects for query 'A'., a pobiera dane, ktore zaczynaja sie na inna litere niz A
            //if (!isset($uniqueSubjects[$subjectName])) {
                $uniqueSubjects[$subjectName] = true;
                $subjects[] = $subjectName;
            }
        }
    }

    echo "Extracted " . count($subjects) . " subjects for query '$query'.\n";
    return $subjects;
}

function ensureUniqueIndexOnSubject() {
    try {
        $pdo = new PDO('sqlite:database.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "CREATE UNIQUE INDEX IF NOT EXISTS idx_subject_name ON Subject(name)";
        $pdo->exec($query);

        echo "Unique index ensured on 'name' column in 'Subject' table.\n";
    } catch (PDOException $e) {
        echo "Error adding unique index: " . $e->getMessage() . "\n";
    }
}

function insertSubjectsIntoDatabase($subjectData) {
    try {
        $db = new PDO('sqlite:database.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "INSERT INTO Subject (name) VALUES (:name)";
        $stmt = $db->prepare($query);

        foreach ($subjectData as $subjectName) {
            $stmt->execute([':name' => $subjectName]);
        }

        echo "Inserted " . count($subjectData) . " subjects into the database.\n";
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage() . "\n";
    }
}

// Main Script
ensureUniqueIndexOnSubject();

$alphabet = range('A', 'Z');
echo "Starting to fetch data for all subjects A-Z...\n";

foreach ($alphabet as $letter) {
    $query = $letter;
    $subjectData = fetchSubjectDataByQuery($query);
    if (!empty($subjectData)) {
        insertSubjectsIntoDatabase($subjectData);
    }
}

echo "Process completed - Subject.\n";
