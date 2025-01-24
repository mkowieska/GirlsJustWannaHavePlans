<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Flush the output buffer to ensure immediate display
ob_implicit_flush(true); // Automatically flush after each echo

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
        echo "Failed to fetch data from the API. HTTP Code: $http_code\n";
        return null;
    }

    // Debugging: Display the raw API response
    echo "Raw API Response: " . $response . "\n";

    // Save the response to a file for future analysis
    file_put_contents('api_response.json', $response);

    $data = json_decode($response, true);

    // Debugging: Display the decoded data structure
    echo "Decoded Data: ";
    print_r($data);

    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Error decoding JSON: " . json_last_error_msg() . "\n";
        return null;
    }

    return $data;
}

function validate_student_groups($data) {
    $valid_groups = [];
    $start_date = strtotime('2025-01-01'); // Start of 2025
    $end_date = strtotime('2025-12-31');  // End of 2025

    echo "Validating groups...\n"; // Start validation process

    foreach ($data as $item) {
        if (is_array($item) && isset($item["group_name"], $item["start"])) {
            // Debugging: Display group name and start date
            echo "Processing group: " . $item["group_name"] . " with start date: " . $item["start"] . "\n";

            $class_timestamp = strtotime($item["start"]);

            // Check if the class date is within 2025
            if ($class_timestamp >= $start_date && $class_timestamp <= $end_date) {
                $valid_groups[] = $item["group_name"];
            }
        }
    }

    // Debugging: Display the valid groups
    echo "Valid Groups after validation: ";
    print_r($valid_groups);

    return !empty($valid_groups) ? $valid_groups : null;
}

function get_group_id_by_name($group_name, $pdo) {
    // Check for group ID in the database
    echo "Checking for group ID for: $group_name\n"; // Debugging group name
    $query = $pdo->prepare("SELECT id FROM `Group` WHERE group_name = :group_name");
    $query->execute([':group_name' => $group_name]);

    // Debugging: Check what the query returned
    $result = $query->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        echo "Group ID found for $group_name: " . $result['id'] . "\n";
        return $result['id'];
    } else {
        echo "No group found for $group_name\n";
    }
    return null;
}

function display_student_group($album_number, $group_name) {
    echo "Student with album number $album_number is in group: $group_name\n";
}

function process_students() {
    try {
        // Connect to the database (for group checking only)
        $pdo = new PDO('sqlite:database1.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $valid_students = [];
        for ($album_number = 53708; $album_number <= 52710; $album_number++) {
            echo "Processing student with album number: $album_number\n";

            $data = fetch_student_groups($album_number);
            if ($data) {
                $groups = validate_student_groups($data);
                if ($groups) {
                    // Assuming the first valid group is assigned
                    $group_name = $groups[0];

                    // Instead of updating the database, we will display the student and group info
                    display_student_group($album_number, $group_name);
                } else {
                    echo "No valid groups found for student $album_number.\n";
                }
            }
        }

        echo "Process completed - Student.\n";

    } catch (PDOException $e) {
        echo "[ERROR] Database error during process: " . $e->getMessage() . "\n";
    }
}

// Run the process
process_students();
?>
