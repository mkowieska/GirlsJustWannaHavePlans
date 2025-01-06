<?php
function fetch_room_data($room_number) {
    $url = "https://plan.zut.edu.pl/schedule.php?kind=room&query=" . $room_number;

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

    $room_names = [];
    foreach ($data as $item) {
        if (is_array($item) && array_key_exists("item", $item)) {
            $room_names[] = $item["item"];
        }
    }

    return $room_names;
}

function insert_into_database($room_data) {
    try {
        $db = new PDO('sqlite:database.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $create_table_query = "
        CREATE TABLE IF NOT EXISTS Room (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        )";
        $db->exec($create_table_query);

        $insert_query = $db->prepare("INSERT OR IGNORE INTO Room (name) VALUES (:name)");

        $added_count = 0;

        foreach ($room_data as $room) {
            $insert_query->execute([':name' => $room]);
            if ($insert_query->rowCount() > 0) {
                $added_count++;
            }
        }

        return $added_count;
    } catch (PDOException $e) {
        echo "[ERROR] Database error: " . $e->getMessage() . "\n";
        return 0;
    }
}

$total_added = 0;

for ($room_number = 0; $room_number < 600; $room_number++) {
    $room_data = fetch_room_data($room_number);
    if ($room_data) {
        $total_added += insert_into_database($room_data);
    }
}
echo "Process completed -Room.\n";
?>
