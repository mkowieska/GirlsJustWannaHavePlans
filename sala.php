<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Funkcja do pobierania harmonogramu z filtrami
// Funkcja do pobierania harmonogramu z filtrami
function getRoomSchedule($filters) {
    try {
        $db = new PDO('sqlite:database.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Define the basic query structure
        $query = "
        SELECT
            lesson_date,
            start_time,
            end_time,
            COALESCE(Subject.name, 'Brak przedmiotu') AS subject_name,
            COALESCE(Lecturer.first_name || ' ' || Lecturer.last_name, 'Brak wykładowcy') AS lecturer_name,
            COALESCE(`Group`.group_name, 'Brak grupy') AS group_name,
            COALESCE(Room.name, 'Brak sali') AS room_name
        FROM Lesson
        JOIN Subject ON Lesson.subject_id = Subject.id
        JOIN Lecturer ON Lesson.responsible_lecturer_id = Lecturer.id
        JOIN `Group` ON Lesson.group_id = `Group`.id
        JOIN Room ON Lesson.room_id = Room.id
        LEFT JOIN Student ON Student.id = Lesson.student_id  -- Dołączenie tabeli Student
        WHERE 1=1
        ";

        $conditions = [];
        $params = [];

        // Sprawdzamy, czy filtry są przekazane i budujemy warunki
        if (!empty($filters['alnumInput'])) {
            $conditions[] = "Student.album_number = :numer_albumu";  
            $params[':numer_albumu'] = $filters['alnumInput'];
        }

        if (!empty($filters['lecturerInput'])) {
            $conditions[] = "Lecturer.last_name LIKE :wykladowca";
            $params[':wykladowca'] = '%' . $filters['lecturerInput'] . '%';
        }

        if (!empty($filters['groupInput'])) {
            $conditions[] = "`Group`.group_name = :grupa";
            $params[':grupa'] = $filters['groupInput'];
        }

        // Dodajemy filtr na przedmiot
        if (!empty($filters['przedmiot'])) {
            $conditions[] = "Subject.name LIKE :przedmiot";
            $params[':przedmiot'] = '%' . $filters['przedmiot'] . '%';
        }

        // Dodajemy kolejne filtry, jeśli istnieją
        if (!empty($filters['wydzial'])) {
            $conditions[] = "Room.department = :wydzial";
            $params[':wydzial'] = $filters['wydzial'];
        }

        if (!empty($filters['typ_studiow'])) {
            $conditions[] = "Lesson.study_type = :typ_studiow";
            $params[':typ_studiow'] = $filters['typ_studiow'];
        }

        if (!empty($filters['semestr'])) {
            $conditions[] = "Lesson.semester = :semestr";
            $params[':semestr'] = $filters['semestr'];
        }

        if (!empty($filters['forma_przedmiotu'])) {
            $conditions[] = "Subject.form_type = :forma_przedmiotu";
            $params[':forma_przedmiotu'] = $filters['forma_przedmiotu'];
        }

        if (!empty($filters['sala'])) {
            $conditions[] = "Room.name LIKE :sala";
            $params[':sala'] = '%' . $filters['sala'] . '%';
        }

        // Jeśli jakiekolwiek warunki zostały dodane, dołączamy je do zapytania
        if (!empty($conditions)) {
            $query .= " AND " . implode(" AND ", $conditions);
        }

        // Przygotowanie i wykonanie zapytania
        $stmt = $db->prepare($query);
        $stmt->execute($params);

        // Pobieranie wyników
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $lessons;

    } catch (PDOException $e) {
        // Logowanie błędów i wysyłanie odpowiedzi z błędem
        error_log("SQLite error: " . $e->getMessage());
        return ["error" => "Database error: " . $e->getMessage()];
    }
}

// Obsługa żądań POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Odczytanie danych z ciała żądania
    $input = json_decode(file_get_contents('php://input'), true);
    $filters = $input['filters'] ?? [];

    // Jeśli filtry są puste, zwrócimy błąd
    if (empty($filters)) {
        echo json_encode(["error" => "Filters are required."]);
        exit;
    }

    // Pobieramy harmonogram na podstawie filtrów
    $schedule = getRoomSchedule($filters);
    echo json_encode($schedule);
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
