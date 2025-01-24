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
            $subjectName = preg_replace('/\s*\(.*$/', '', $subjectName); // Usuwanie części przed pierwszym nawiasem

            // Sprawdzenie, czy nazwa nie kończy się spacją
            if (substr($subjectName, -1) !== ' ' && stripos($subjectName, $query) === 0 && !isset($uniqueSubjects[$subjectName])) {
                $uniqueSubjects[$subjectName] = true;
                $subjects[] = $subjectName;
            }
        }
    }

    return $subjects;
}

function ensureUniqueIndexOnSubject() {
    try {
        $pdo = new PDO('sqlite:database1.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "CREATE UNIQUE INDEX IF NOT EXISTS idx_subject_name ON Subject(name)";
        $pdo->exec($query);
    } catch (PDOException $e) {
        echo "Error adding unique index: " . $e->getMessage() . "\n";
    }
}

function insertSubjectsIntoDatabase($subjectData) {
    try {
        $db = new PDO('sqlite:database1.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "INSERT INTO Subject (name) VALUES (:name)";
        $stmt = $db->prepare($query);

        foreach ($subjectData as $subjectName) {
            $stmt->execute([':name' => $subjectName]);
        }
    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage() . "\n";
    }
}

// Main Script
ensureUniqueIndexOnSubject();

$alphabet_etc = array_merge(
    range('A', 'Z'), 
    range('0', '9'), 
    [' ', '(', '[', '{', '!'], 
    ['А', 'Б', 'В', 'Г', 'Д', 'Е', 'Ё', 'Ж', 'З', 'И', 'Й', 'К', 'Л', 'М', 'Н', 'О', 'П', 'Р', 'С', 'Т', 'У', 'Ф', 'Х', 'Ц', 'Ч', 'Ш', 'Щ', 'Ъ', 'Ы', 'Ь', 'Э', 'Ю', 'Я']
);

foreach ($alphabet_etc as $letter_number) {
    $query = rtrim($letter_number); // Usunięcie końcowej spacji
    $subjectData = fetchSubjectDataByQuery($query);
    if (!empty($subjectData)) {
        insertSubjectsIntoDatabase($subjectData);
    }
}

echo "Process completed - Subject.\n";
