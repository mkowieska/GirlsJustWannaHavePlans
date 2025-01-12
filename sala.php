<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    $roomNumber = $input['room'] ?? '';
    $filters = $input['filters'] ?? [];

    if (empty($roomNumber)) {
        echo json_encode(['error' => 'Room number is required']);
        exit;
    }

    try {
        $db = new PDO('sqlite:database.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Zapytanie SQL z JOINami
        $query = "
            SELECT 
                lesson_date, 
                start_time, 
                end_time, 
                Subject.name AS subject_name, 
                Lecturer.first_name || ' ' || Lecturer.last_name AS lecturer_name, 
                `Group`.group_name
            FROM Lesson
            JOIN Subject ON Lesson.subject_id = Subject.id
            JOIN Lecturer ON Lesson.responsible_lecturer_id = Lecturer.id
            JOIN `Group` ON Lesson.group_id = `Group`.id
            JOIN Room ON Lesson.room_id = Room.id
            WHERE Room.name = :room
        ";

        $stmt = $db->prepare($query);
        $stmt->bindParam(':room', $roomNumber, PDO::PARAM_STR);
        $stmt->execute();

        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($lessons);
    } catch (PDOException $e) {
        echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
