<?php
try {
    // Ustawienia połączenia z bazą danych SQLite
    $pdo = new PDO('sqlite:database1.db');
    
    // Tworzenie nowej tabeli bez kolumny student_id
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS Lesson_new (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            subject_id INTEGER NOT NULL,
            group_id INTEGER NOT NULL,
            room_id INTEGER NOT NULL,
            lesson_date DATE NOT NULL,
            start_time TIME NOT NULL,
            end_time TIME NOT NULL,
            class_type TEXT NOT NULL,
            responsible_lecturer_id INTEGER,
            substitute_lecturer_id INTEGER,
            department TEXT NOT NULL,
            semester TEXT NOT NULL,
            study_type TEXT NOT NULL
        );
    ");
    
    // Kopiowanie danych z oryginalnej tabeli do nowej (bez student_id)
    $pdo->exec("
        INSERT INTO Lesson_new (id, subject_id, group_id, room_id, lesson_date, start_time, end_time, class_type, responsible_lecturer_id, substitute_lecturer_id, department, semester, study_type)
        SELECT id, subject_id, group_id, room_id, lesson_date, start_time, end_time, class_type, responsible_lecturer_id, substitute_lecturer_id, department, semester, study_type FROM Lesson;
    ");
    
    // Usuwanie starej tabeli
    $pdo->exec("DROP TABLE Lesson");
    
    // Zmieniamy nazwę nowej tabeli na Lesson
    $pdo->exec("ALTER TABLE Lesson_new RENAME TO Lesson");
    
    echo "Kolumna student_id została usunięta, a tabela została zaktualizowana." . PHP_EOL;
    
} catch (PDOException $e) {
    echo "Błąd: " . $e->getMessage();
}
