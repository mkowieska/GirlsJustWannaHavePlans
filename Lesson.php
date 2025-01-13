<?php

// Ustawienia połączenia z bazą danych SQLite
$dsn = 'sqlite:database.db';
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
    
    // Debugowanie - wyświetl całą odpowiedź z API
    echo "Odpowiedź z API: " . $response . PHP_EOL;
    
    return json_decode($response, true); // Dekodujemy JSON odpowiedzi
}

// Funkcja do wyszukiwania ID przedmiotu na podstawie nazwy w tabeli "Subject"
function getSubjectId($subjectName, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM Subject WHERE name = :name");
    $stmt->execute([':name' => $subjectName]);
    $row = $stmt->fetch();
    return $row ? $row['id'] : null; // Zwraca ID przedmiotu lub null, jeśli nie znaleziono
}

// Funkcja do wyszukiwania ID grupy na podstawie nazwy w tabeli "Group"
function getGroupId($groupName, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM `Group` WHERE group_name = :group_name");
    $stmt->execute([':group_name' => $groupName]);
    $row = $stmt->fetch();
    return $row ? $row['id'] : null; // Zwraca ID grupy lub null, jeśli nie znaleziono
}

// Funkcja do wyszukiwania ID sali na podstawie nazwy w tabeli "Room"
function getRoomId($roomName, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM Room WHERE name = :name");
    $stmt->execute([':name' => $roomName]);
    $row = $stmt->fetch();
    return $row ? $row['id'] : null; // Zwraca ID sali lub null, jeśli nie znaleziono
}

// Funkcja do wyszukiwania ID wykładowcy na podstawie imienia i nazwiska
function getLecturerId($firstName, $lastName, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM Lecturer WHERE first_name = :first_name AND last_name = :last_name");
    $stmt->execute([':first_name' => $firstName, ':last_name' => $lastName]);
    $row = $stmt->fetch();
    return $row ? $row['id'] : null; // Zwraca ID wykładowcy lub null, jeśli nie znaleziono
}

// Funkcja do określania semestru na podstawie daty
function getSemester($date) {
    // Konwertujemy datę na obiekt DateTime
    $dateTime = new DateTime($date);
    $month = $dateTime->format('m'); // Miesiąc z daty
    
    // Semestr zimowy: październik (10) - luty (2), semestr letni: marzec (3) - wrzesień (9)
    if ($month >= 10 || $month <= 2) {
        return "Zimowy";
    } else {
        return "Letni";
    }
}

// Funkcja do określania typu studiów na podstawie dnia tygodnia
function getStudyType($date) {
    // Konwertujemy datę na obiekt DateTime
    $dateTime = new DateTime($date);
    $dayOfWeek = $dateTime->format('l'); // Dzień tygodnia w pełnej nazwie (np. 'Monday', 'Tuesday', ...)

    // Typ studiów
    if (in_array($dayOfWeek, ['Monday', 'Tuesday', 'Wednesday', 'Thursday'])) {
        return "Dzienne"; // Studia dzienne od poniedziałku do czwartku
    } else {
        return "Zaoczne"; // Studia zaoczne w piątek, sobotę i niedzielę
    }
}

// Funkcja do wyciągania wydziału z sali (pierwszy człon nazwy sali)
function getDepartment($roomName) {
    // Rozdzielamy nazwę sali po spacji i bierzemy pierwszy człon
    $parts = explode(' ', $roomName);
    return $parts[0]; // Zwracamy pierwszy człon, który jest nazwą wydziału
}

// Przykładowe dane wejściowe
$teacher = "Karczmarczyk Artur";
$start = "2024-09-30T00:00:00+02:00";
$end = "2024-10-07T00:00:00+02:00";

// Pobranie planu zajęć nauczyciela z API
$schedule = fetchScheduleFromAPI($teacher, $start, $end);

// Przetwarzanie odpowiedzi z API i wyszukiwanie ID w bazie
foreach ($schedule as $lesson) {
    $subjectName = $lesson['subject']; // Używamy 'subject' jako nazwy przedmiotu
    $groupName = $lesson['group_name']; // Grupa
    $roomName = $lesson['room']; // Sala
    $lessonStatus = $lesson['lesson_status_short']; // Forma przedmiotu
    $startTime = new DateTime($lesson['start']); // Czas rozpoczęcia zajęć (z API)
    $endTime = new DateTime($lesson['end']); // Czas zakończenia zajęć (z API)

    // Formatowanie daty i godziny
    $date = $startTime->format('Y-m-d'); // Data zajęć
    $startHour = $startTime->format('H:i'); // Godzina rozpoczęcia
    $endHour = $endTime->format('H:i'); // Godzina zakończenia
    
    // Określanie semestru
    $semester = getSemester($date);

    // Określanie typu studiów
    $studyType = getStudyType($date);

    // Określanie wydziału
    $department = getDepartment($roomName);

    // Pobieramy ID przedmiotu, grupy, sali i wykładowcy z bazy danych
    $subjectId = getSubjectId($subjectName, $pdo);
    $groupId = getGroupId($groupName, $pdo);
    $roomId = getRoomId($roomName, $pdo);
    
    // Wyodrębniamy imię i nazwisko wykładowcy
    list($firstName, $lastName) = explode(" ", $teacher); // Zakładając, że imię i nazwisko są oddzielone spacją
    $lecturerId = getLecturerId($lastName, $firstName, $pdo);

    // Wyświetlamy dane w odpowiednim formacie
    echo "Przedmiot: " . $subjectName . " | ID " . ($subjectId ?: "Brak ID") . PHP_EOL;
    echo "Grupa: " . $groupName . " | ID " . ($groupId ?: "Brak ID") . PHP_EOL;
    echo "Sala: " . $roomName . " | ID " . ($roomId ?: "Brak ID") . PHP_EOL;
    echo "Wydział: " . $department . PHP_EOL; // Wyświetlanie wydziału
    echo "Semestr: " . $semester . PHP_EOL; // Wyświetlanie semestru
    echo "Typ studiów: " . $studyType . PHP_EOL; // Wyświetlanie typu studiów
    echo "Forma przedmiotu: " . $lessonStatus . PHP_EOL; // Wyświetlanie formy przedmiotu (L, A, W, P, E, O, Lek)
    echo "Data zajęć: " . $date . PHP_EOL; // Wyświetlanie daty zajęć
    echo "Godzina rozpoczęcia: " . $startHour . PHP_EOL; // Wyświetlanie godziny rozpoczęcia
    echo "Godzina zakończenia: " . $endHour . PHP_EOL; // Wyświetlanie godziny zakończenia
    echo "ID wykładowcy: " . ($lecturerId ?: "Brak ID") . PHP_EOL; // Wyświetlanie ID wykładowcy
    echo "----------------------------------------" . PHP_EOL;
}

?>
