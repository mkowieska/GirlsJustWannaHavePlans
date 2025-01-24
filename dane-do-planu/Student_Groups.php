<?php
// Zakres numerów albumów
$start_album = 53707;
$end_album = 53710;

// Włączenie raportowania błędów
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Połączenie z bazą danych SQLite
$dsn = 'sqlite:database1.db';
try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Utworzenie tabeli Group, jeśli nie istnieje
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Group` (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        group_name TEXT UNIQUE
    )");

    // Utworzenie tabeli Student, jeśli nie istnieje
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Student` (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        album_number INTEGER UNIQUE,
        student_name TEXT
    )");

    // Utworzenie tabeli Student_Groups, jeśli nie istnieje
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Student_Groups` (
        student_id INTEGER,
        group_id INTEGER,
        FOREIGN KEY(student_id) REFERENCES Student(id),
        FOREIGN KEY(group_id) REFERENCES 'Group'(id),
        PRIMARY KEY(student_id, group_id)
    )");

} catch (PDOException $e) {
    die("Błąd połączenia z bazą danych: " . $e->getMessage());
}

// Funkcja sprawdzająca, czy dany student ma zajęcia w tym miesiącu
function checkSchedule($album_number) {
    $url = "https://plan.zut.edu.pl/schedule_student.php?number=" . $album_number;

    // Pobranie danych z API
    $response = file_get_contents($url);
    if ($response === FALSE) {
        echo "Błąd połączenia z API dla numeru albumu: $album_number\n";
        return null;
    }

    // Dekodowanie JSON z odpowiedzi API
    $data = json_decode($response, true);
    if ($data === null) {
        echo "Nieprawidłowy format danych JSON dla numeru albumu: $album_number\n";
        return null;
    }

    // Wyodrębnienie zajęć z bieżącego miesiąca
    $current_month = date("m");
    $group_names = [];

    foreach ($data as $lesson) {
        $lesson_month = date("m", strtotime($lesson['start']));
        if ($lesson_month === $current_month) {
            $group_names[] = $lesson['group_name'] ?? "Nieznana grupa";
        }
    }

    return array_unique($group_names); // Zwrócenie unikalnych nazw grup
}

// Funkcja do pobierania ID studenta z bazy danych
function getStudentId($album_number, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM `Student` WHERE album_number = :album_number");
    $stmt->bindParam(':album_number', $album_number, PDO::PARAM_INT);
    $stmt->execute();

    $student = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($student) {
        return $student['id']; // Zwraca ID, jeśli student istnieje
    }

    // Jeśli studenta nie ma, dodajemy go do bazy danych
    // Można tu dodać nazwisko lub inne dane studenta, jeśli są dostępne
    $student_name = "Student_$album_number"; // Można dodać prawdziwe imię/nazwisko studenta
    $stmt = $pdo->prepare("INSERT INTO `Student` (album_number, student_name) VALUES (:album_number, :student_name)");
    $stmt->bindParam(':album_number', $album_number, PDO::PARAM_INT);
    $stmt->bindParam(':student_name', $student_name, PDO::PARAM_STR);
    $stmt->execute();

    return $pdo->lastInsertId(); // Zwraca ID ostatnio dodanego studenta
}

// Funkcja do pobierania ID grupy z bazy danych
function getGroupId($group_name, $pdo) {
    $stmt = $pdo->prepare("SELECT id FROM `Group` WHERE group_name = :group_name");
    $stmt->bindParam(':group_name', $group_name, PDO::PARAM_STR);
    $stmt->execute();

    $group = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($group) {
        return $group['id']; // Zwraca ID, jeśli grupa istnieje
    }

    // Jeśli grupa nie istnieje, dodajemy ją do bazy danych
    $stmt = $pdo->prepare("INSERT INTO `Group` (group_name) VALUES (:group_name)");
    $stmt->bindParam(':group_name', $group_name, PDO::PARAM_STR);
    $stmt->execute();

    return $pdo->lastInsertId(); // Zwraca ID ostatnio dodanej grupy
}

// Funkcja do zapisywania relacji student-grupa w tabeli Student_Groups
function saveStudentGroupRelation($student_id, $group_id, $pdo) {
    $stmt = $pdo->prepare("INSERT OR IGNORE INTO `Student_Groups` (student_id, group_id) VALUES (:student_id, :group_id)");
    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
    $stmt->bindParam(':group_id', $group_id, PDO::PARAM_INT);
    $stmt->execute();
}

// Iteracja po numerach albumów i wyświetlenie wyników
for ($album_number = $start_album; $album_number <= $end_album; $album_number++) {
    $group_names = checkSchedule($album_number);

    if (!empty($group_names)) {
        $student_id = getStudentId($album_number, $pdo); // Pobieramy ID studenta
        echo "Numer albumu: $album_number (ID studenta: $student_id)\n";
        echo "Grupy zajęć w tym miesiącu:\n";
        foreach ($group_names as $group_name) {
            $group_id = getGroupId($group_name, $pdo); // Pobieramy ID grupy
            saveStudentGroupRelation($student_id, $group_id, $pdo); // Zapisujemy relację w tabeli Student_Groups
            echo "- Grupa: $group_name (ID grupy: $group_id) - Relacja zapisana\n";
        }
    }
}
?>
