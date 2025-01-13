<?php

function fetchGroupData($groupNumber) {
    $url = "https://plan.zut.edu.pl/schedule.php?kind=group&query=" . urlencode($groupNumber);
    
    $options = [
        "http" => [
            "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36\r\n",
            "ignore_errors" => true // zapewnia to, że zawartość zostanie zwrócona nawet jesli kod HTTP nie jest 200
        ],
        "ssl" => [
            "verify_peer" => false, // wylaczona weryfikacja SSL 
            "verify_peer_name" => false, // wylaczona weryfikacja nazwy równorzędnej
        ]
    ];

    $context = stream_context_create($options);
    $response = file_get_contents($url, false, $context);

    if ($response === false) {
        echo "[ERROR] Fetching data for group $groupNumber\n";
        return null;
    }

    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "[ERROR] Decoding JSON for group $groupNumber: " . json_last_error_msg() . "\n";
        return null;
    }

    $groupNames = [];

    foreach ($data as $item) {
        if (is_array($item) && isset($item['item'])) {
            $groupNames[] = [$item['item'], 0];
        }
    }

    return $groupNames;
}

function insertIntoDatabase($groupData) { 
    try {
        $pdo = new PDO('sqlite:database1.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $insertQuery = "INSERT OR IGNORE INTO `Group` (group_name) VALUES (:group_name)";
        $stmt = $pdo->prepare($insertQuery);

        foreach ($groupData as $group) {
            $stmt->execute(['group_name' => $group[0]]);
        }

        #echo "Inserted " . count($groupData) . " groups into the database.\n";
    } catch (PDOException $e) {
        echo "[ERROR] Database error: " . $e->getMessage() . "\n";
    }
}

for ($groupNumber = 'A'; $groupNumber <= 'Z'; $groupNumber++) {
    $groupData = fetchGroupData($groupNumber);
    if ($groupData) {
        insertIntoDatabase($groupData);
    }
}
echo "Process completed - Group.\n";