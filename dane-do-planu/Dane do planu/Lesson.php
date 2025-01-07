<?php
// Database Connection Configuration
try {
    $connection = new PDO('sqlite:database.db');
    $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// API Fetch Function
function fetch_student_schedule($albumNumber) {
    $url = "https://plan.zut.edu.pl/schedule_student.php?number=$albumNumber";
    $headers = [
        "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);

    if (curl_getinfo($ch, CURLINFO_HTTP_CODE) !== 200) {
        echo "Error fetching data for album $albumNumber: HTTP " . curl_getinfo($ch, CURLINFO_HTTP_CODE) . "\n";
        curl_close($ch);
        return null;
    }

    curl_close($ch);

    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        echo "Failed to decode JSON response.\n";
        return null;
    }

    return $data;
}

// Database Query Functions
function get_id_from_table($connection, $table, $column, $value) {
    $query = "SELECT id FROM $table WHERE $column = :value";
    $stmt = $connection->prepare($query);
    $stmt->bindParam(':value', $value, PDO::PARAM_STR);
    $stmt->execute();

    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result ? $result['id'] : null;
}

function insert_lesson($connection, $lessonData) {
    try {
        echo "Inserting lesson data: ";
        print_r($lessonData); // Check the data being inserted
        
        $query = "INSERT INTO Lesson (subject_id, group_id, room_id, student_id, lesson_date, start_time, end_time, class_type, responsible_lecturer_id, substitute_lecturer_id) 
                  VALUES (:subject_id, :group_id, :room_id, :student_id, :lesson_date, :start_time, :end_time, :class_type, :responsible_lecturer_id, NULL)";
        $stmt = $connection->prepare($query);
        $stmt->execute($lessonData);
    } catch (PDOException $e) {
        echo "Error inserting lesson: " . $e->getMessage() . "\n";
        print_r($lessonData);
    }
}


function process_album_number($albumNumber) {
    global $connection;

    $data = fetch_student_schedule($albumNumber);
    if (!$data || count($data) < 2) {
        echo "No valid data for album $albumNumber\n";
        return;
    }

    try {
        $studentId = get_id_from_table($connection, 'Student', 'album_number', $albumNumber);
        if (!$studentId) {
            echo "No student found for album $albumNumber\n";
            return;
        }

        foreach ($data as $key => $lesson) {
            if ($key === 0) continue; // Skip the first element if it's metadata.

            // Extract lesson details
            $subjectId = get_id_from_table($connection, 'Subject', 'name', $lesson['subject']);
            $groupId = get_id_from_table($connection, '`Group`', 'group_name', $lesson['group_name']);
            $roomId = get_id_from_table($connection, 'Room', 'name', $lesson['room']);
            $lecturerId = get_id_from_table($connection, 'Lecturer', 'last_name', explode(' ', $lesson['worker'])[1] ?? '');

            $lessonDate = (new DateTime($lesson['start']))->format('Y-m-d');
            $startTime = (new DateTime($lesson['start']))->format('H:i:s');
            $endTime = (new DateTime($lesson['end']))->format('H:i:s');

            if (!$subjectId || !$groupId || !$roomId || !$lecturerId) {
                echo "Missing data for lesson: " . $lesson['title'] . "\n";
                echo "subjectId: $subjectId, groupId: $groupId, roomId: $roomId, lecturerId: $lecturerId\n";
                print_r($lesson);
                continue;
            }

            $lessonData = [
                ':subject_id' => $subjectId,
                ':group_id' => $groupId,
                ':room_id' => $roomId,
                ':student_id' => $studentId,
                ':lesson_date' => $lessonDate,
                ':start_time' => $startTime,
                ':end_time' => $endTime,
                ':class_type' => $lesson['class_type'],
                ':responsible_lecturer_id' => $lecturerId
            ];

            insert_lesson($connection, $lessonData);
        }

        echo "Processed album $albumNumber successfully.\n";

    } catch (PDOException $e) {
        echo "Database error: " . $e->getMessage() . "\n";
    }
}

// Main Execution
for ($album = 53708; $album <= 53710; $album++) {
    process_album_number($album);
}
