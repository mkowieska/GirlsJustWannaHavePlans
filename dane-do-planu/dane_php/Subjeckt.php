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

    // Disable SSL verification (be cautious with this)
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

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

    echo "Successfully fetched data for query '$query'. Number of items: " . count($data) . "\n";

    if (!empty($data)) {
        echo "First item for query '$query': " . print_r($data[0], true) . "\n";
    }

    $subjects = [];
    foreach ($data as $item) {
        if (is_array($item) && isset($item['item'])) {
            $subjectName = $item['item'];
            $subjects[] = $subjectName;
        }
    }

    echo "Extracted " . count($subjects) . " subjects for query '$query'\n";
    return $subjects;
}

function ensureUniqueIndexOnSubject() {
    try {
        // MySQL connection
        $pdo = new PDO('mysql:host=localhost;dbname=lepszy_plan', 'root', 'B7uWyeGcjqRX3bv!');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        // Remove 'IF NOT EXISTS' as it is not supported in older versions of MySQL
        $query = "CREATE UNIQUE INDEX idx_subject_name ON Subject(name)";
        $pdo->exec($query);
        echo "Added UNIQUE index on 'name' column in 'Subject' table.\n";
    } catch (PDOException $e) {
        echo "Database error while adding UNIQUE index: " . $e->getMessage() . "\n";
    }
}

function insertSubjectsIntoDatabase($subjectData) {
    echo "Inserting " . count($subjectData) . " subjects into the database...\n";

    try {
        // MySQL connection
        $pdo = new PDO('mysql:host=localhost;dbname=lepszy_plan', 'root', 'B7uWyeGcjqRX3bv!');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "INSERT IGNORE INTO Subject (name) VALUES (:name)";
        $stmt = $pdo->prepare($query);

        foreach ($subjectData as $subjectName) {
            $stmt->execute([':name' => $subjectName]);
        }

        echo "Inserted subjects into the database, duplicates ignored.\n";
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage() . "\n";
    }
}

// Main Script
ensureUniqueIndexOnSubject();

$alphabet = range('A', 'Z');
echo "Starting to fetch data for all subjects A-Z.\n";

foreach ($alphabet as $letter) {
    $query = $letter;
    $subjectData = fetchSubjectDataByQuery($query);
    if (!empty($subjectData)) {
        insertSubjectsIntoDatabase($subjectData);
    }
}

echo "Process completed - Subject.\n";
