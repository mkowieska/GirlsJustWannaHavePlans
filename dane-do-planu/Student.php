<?php
// Zakres numerów albumów
$start_album = 53700;
$end_album = 54000;

// Włączenie raportowania błędów
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Połączenie z bazą danych SQLite
$dsn = 'sqlite:database1.db';
try {
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Utworzenie tabeli `Student`, jeśli nie istnieje
    $pdo->exec("CREATE TABLE IF NOT EXISTS `Student` (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        album_number INTEGER UNIQUE
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
        return false;
    }

    // Dekodowanie JSON z odpowiedzi API
    $data = json_decode($response, true);
    if ($data === null) {
        echo "Nieprawidłowy format danych JSON dla numeru albumu: $album_number\n";
        return false;
    }

    // Wyodrębnienie zajęć z bieżącego miesiąca
    $current_month = date("m");

    foreach ($data as $lesson) {
        // Sprawdzanie, czy klucz 'start' istnieje, zanim użyjemy go w strtotime
        if (isset($lesson['start'])) {
            $lesson_month = date("m", strtotime($lesson['start']));
            if ($lesson_month === $current_month) {
                return true; // Jeśli student ma zajęcia w bieżącym miesiącu, zwróć true
            }
        }
    }

    return false; // Jeśli nie ma zajęć w bieżącym miesiącu
}

// Funkcja sprawdzająca, czy student już istnieje w bazie danych
function isStudentInDatabase($pdo, $album_number) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM `Student` WHERE album_number = :album_number");
    $stmt->execute([':album_number' => $album_number]);
    return $stmt->fetchColumn() > 0;
}

// Funkcja dodająca studenta do tabeli `Student`
function addStudentToDatabase($pdo, $album_number) {
    try {
        $stmt = $pdo->prepare("INSERT INTO `Student` (album_number) VALUES (:album_number)");
        $stmt->execute([':album_number' => $album_number]);
    } catch (PDOException $e) {
        echo "Błąd podczas dodawania studenta $album_number do bazy danych: " . $e->getMessage() . "\n";
    }
}

// Iteracja po numerach albumów i dodawanie studentów, którzy mają zajęcia w bieżącym miesiącu
for ($album_number = $start_album; $album_number <= $end_album; $album_number++) {
    // Sprawdzamy, czy student ma zajęcia w tym miesiącu i czy nie ma go jeszcze w bazie
    if (checkSchedule($album_number) && !isStudentInDatabase($pdo, $album_number)) {
        echo "Numer albumu: $album_number ma zajęcia w tym miesiącu i nie ma go jeszcze w bazie\n";

        // Dodanie studenta do bazy danych
        addStudentToDatabase($pdo, $album_number);
    } else {
        // Jeśli student ma zajęcia, ale jest już w bazie, lub nie ma zajęć w tym miesiącu
        echo "Numer albumu: $album_number nie zostanie dodany (lub już jest w bazie)\n";
    }
}
?>
