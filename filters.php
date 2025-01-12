<?php
// Ustawienie nagłówków dla obsługi CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Funkcja do sprawdzania, czy sala istnieje w bazie danych
function checkRoomExistence($db, $roomNumber) {
    try {
        $stmt = $db->prepare("SELECT id FROM Room WHERE name = :roomName");
        $stmt->bindParam(':roomName', $roomNumber, PDO::PARAM_STR);
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['id'] : null;
    } catch (PDOException $e) {
        error_log("SQLite error: " . $e->getMessage());
        return null;
    }
}

// Funkcja do pobierania harmonogramu dla sali
function getRoomSchedule($roomNumber, $filters) {
    try {
        $db = new PDO('sqlite:database.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Główne zapytanie SQL
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
            WHERE Room.name = :roomName
        ";

        // Budowanie dynamicznych filtrów
        $conditions = [];
        $params = [':roomName' => $roomNumber];

        if (!empty($filters['wydzial'])) {
            $conditions[] = "Subject.department = :wydzial";
            $params[':wydzial'] = $filters['wydzial'];
        }

        if (!empty($filters['typ_studiow'])) {
            $conditions[] = "Subject.study_type = :typ_studiow";
            $params[':typ_studiow'] = $filters['typ_studiow'];
        }

        if (!empty($filters['semestr'])) {
            $conditions[] = "Lesson.semester = :semestr";
            $params[':semestr'] = $filters['semestr'];
        }

        if (!empty($filters['wykladowca'])) {
            $conditions[] = "Lecturer.last_name LIKE :wykladowca";
            $params[':wykladowca'] = '%' . $filters['wykladowca'] . '%';
        }

        if (!empty($filters['forma_przedmiotu'])) {
            $conditions[] = "Subject.form = :forma_przedmiotu";
            $params[':forma_przedmiotu'] = $filters['forma_przedmiotu'];
        }

        if (!empty($filters['przedmiot'])) {
            $conditions[] = "Subject.name LIKE :przedmiot";
            $params[':przedmiot'] = '%' . $filters['przedmiot'] . '%';
        }

        if (!empty($filters['grupa'])) {
            $conditions[] = "`Group`.group_name = :grupa";
            $params[':grupa'] = $filters['grupa'];
        }

        if (!empty($filters['numer_albumu'])) {
            $conditions[] = "Student.album_number = :numer_albumu";
            $params[':numer_albumu'] = $filters['numer_albumu'];
        }

        // Dodawanie warunków do zapytania
        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }

        $stmt = $db->prepare($query);
        $stmt->execute($params);

        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $lessons;

    } catch (PDOException $e) {
        error_log("SQLite error: " . $e->getMessage());
        return ["error" => "Database error: " . $e->getMessage()];
    }
}

// Obsługa żądań POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    $roomNumber = $input['room'] ?? null;
    $filters = $input['filters'] ?? [];

    if (!$roomNumber) {
        echo json_encode(["error" => "Room number is required."]);
        exit;
    }

    $schedule = getRoomSchedule($roomNumber, $filters);
    echo json_encode($schedule);
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
