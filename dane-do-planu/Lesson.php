<?php

// Ustawienia połączenia z bazą danych SQLite
$dsn = 'sqlite:database1.db';
$username = '';
$password = '';
$options = array(
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
);

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    echo "Połączenie nieudane: " . $e->getMessage();
    exit;
}

// Funkcja do pobrania planu z API
function fetchScheduleFromAPI($teacher, $start, $end) {
    $url = "https://plan.zut.edu.pl/schedule_student.php?teacher=" . urlencode($teacher) . "&start=" . urlencode($start) . "&end=" . urlencode($end);
    $response = file_get_contents($url);
    return json_decode($response, true); // Dekodujemy JSON odpowiedzi
}

// Funkcje pomocnicze do pobierania ID z tabel
function getSubjectId($subjectName, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM Subject WHERE name LIKE :name");
    $stmt->execute([':name' => '%' . trim($subjectName) . '%']);
    $row = $stmt->fetch();
    return $row ? $row['id'] : 'BRAK';
}

function getGroupId($groupName, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM `Group` WHERE group_name LIKE :group_name");
    $stmt->execute([':group_name' => '%' . trim($groupName) . '%']);
    $row = $stmt->fetch();
    return $row ? $row['id'] : 'BRAK';
}

function getRoomId($roomName, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM Room WHERE name LIKE :name");
    $stmt->execute([':name' => '%' . trim($roomName) . '%']);
    $row = $stmt->fetch();
    return $row ? $row['id'] : 'BRAK';
}

function getLecturerId($firstName, $lastName, $pdo) {
    // Sprawdzamy, czy nauczyciel już istnieje w bazie
    $stmt = $pdo->prepare("SELECT id FROM Lecturer WHERE first_name = :first_name AND last_name = :last_name");
    $stmt->execute([':first_name' => trim($firstName), ':last_name' => trim($lastName)]);
    $row = $stmt->fetch();

    if ($row) {
        // Jeśli nauczyciel istnieje, zwracamy jego ID
        return $row['id'];
    } else {
        // Jeśli nauczyciel nie istnieje, dodajemy go do bazy
        $stmt = $pdo->prepare("INSERT INTO Lecturer (first_name, last_name) VALUES (:first_name, :last_name)");
        $stmt->execute([':first_name' => trim($firstName), ':last_name' => trim($lastName)]);
        
        // Zwracamy ID nowo dodanego nauczyciela
        return $pdo->lastInsertId();
    }
}

// Funkcje do obliczeń
function getSemester($date) {
    $dateTime = new DateTime($date);
    $month = $dateTime->format('m');
    return ($month >= 10 || $month <= 2) ? "Zimowy" : "Letni";
}

function getStudyType($date) {
    $dateTime = new DateTime($date);
    $dayOfWeek = $dateTime->format('l');
    return in_array($dayOfWeek, ['Monday', 'Tuesday', 'Wednesday', 'Thursday']) ? "Dzienne" : "Zaoczne";
}

function getDepartment($roomName) {
    $parts = explode(' ', $roomName);
    return $parts[0];
}

// Przykładowe dane wejściowe
$teacher = "Karczmarczyk Artur";
$start = "2024-09-30T00:00:00+02:00";
$end = "2024-10-07T00:00:00+02:00";

// Pobranie planu zajęć nauczyciela z API
$schedule = fetchScheduleFromAPI($teacher, $start, $end);

// Debug: Wyświetlenie danych z API
echo "Dane z API: " . json_encode($schedule, JSON_PRETTY_PRINT) . PHP_EOL;

// Przetwarzanie odpowiedzi z API i zapisywanie danych do tabeli Lesson
foreach ($schedule as $lesson) {
    // Walidacja danych
    if (empty($lesson['subject']) || empty($lesson['group_name']) || empty($lesson['room'])) {
        echo "Brakuje danych w lekcji: " . json_encode($lesson) . PHP_EOL;
        continue;
    }

    $subjectName = trim($lesson['subject']);
    $groupName = trim($lesson['group_name']);
    $roomName = trim($lesson['room']);

    $startTime = isset($lesson['start']) ? new DateTime($lesson['start']) : null;
    $endTime = isset($lesson['end']) ? new DateTime($lesson['end']) : null;

    if (!$startTime || !$endTime) {
        echo "Brak czasu rozpoczęcia lub zakończenia." . PHP_EOL;
        continue;
    }

    // Pobieranie ID z bazy danych, wstawiając 'BRAK' w przypadku braku
    $subjectId = getSubjectId($subjectName, $pdo) ?? 'BRAK';
    $groupId = getGroupId($groupName, $pdo) ?? 'BRAK';
    $roomId = getRoomId($roomName, $pdo) ?? 'BRAK';

    // Pobieranie ID wykładowcy
    list($firstName, $lastName) = explode(" ", $teacher);
    $lecturerId = getLecturerId($firstName, $lastName, $pdo);

    // Obliczenia
    $semester = getSemester($startTime->format('Y-m-d'));
    $studyType = getStudyType($startTime->format('Y-m-d'));
    $department = getDepartment($roomName);

    // Pobranie imienia i nazwiska wykładowcy
    $stmt = $pdo->prepare("SELECT first_name, last_name FROM Lecturer WHERE id = :id");
    $stmt->execute([':id' => $lecturerId]);
    $lecturer = $stmt->fetch();

    // Debug: Wyświetlenie przetwarzanych danych
    echo "Przedmiot: $subjectName | ID: $subjectId" . PHP_EOL;
    echo "Grupa: $groupName | ID: $groupId" . PHP_EOL;
    echo "Sala: $roomName | ID: $roomId" . PHP_EOL;
    echo "Wydział: $department, Semestr: $semester, Typ studiów: $studyType" . PHP_EOL;
    echo "Wykładowca: " . $lecturer['first_name'] . " " . $lecturer['last_name'] . " | ID: $lecturerId" . PHP_EOL;

    // Przygotowanie zapytania do zapisu danych do tabeli Lesson (bez student_id)
    $stmt = $pdo->prepare("
        INSERT INTO Lesson (
            subject_id, group_id, room_id, lesson_date,
            start_time, end_time, class_type, responsible_lecturer_id,
            substitute_lecturer_id, department, semester, study_type
        ) VALUES (
            :subject_id, :group_id, :room_id, :lesson_date,
            :start_time, :end_time, :class_type, :responsible_lecturer_id,
            NULL, :department, :semester, :study_type
        )
    ");

    // Wstawianie danych do tabeli Lesson
    $stmt->execute([
        ':subject_id' => $subjectId,
        ':group_id' => $groupId,
        ':room_id' => $roomId,
        ':lesson_date' => $startTime->format('Y-m-d'),
        ':start_time' => $startTime->format('H:i'),
        ':end_time' => $endTime->format('H:i'),
        ':class_type' => $lesson['lesson_status_short'] ?? 'Nieznany',
        ':responsible_lecturer_id' => $lecturerId ?? null,
        ':department' => $department,
        ':semester' => $semester,
        ':study_type' => $studyType,
    ]);

    echo "Dane zostały zapisane do bazy danych!" . PHP_EOL;
    echo "----------------------------------------" . PHP_EOL;
}

?>
