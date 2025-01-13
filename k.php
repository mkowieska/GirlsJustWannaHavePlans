<?php
// Połączenie z bazą danych SQLite
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

// Funkcja do sprawdzania kolumn w tabeli
function checkColumns($tableName, $pdo) {
    // Wykonujemy zapytanie PRAGMA table_info, aby uzyskać informacje o kolumnach
    $stmt = $pdo->prepare("PRAGMA table_info($tableName)");
    $stmt->execute();
    $columns = $stmt->fetchAll();

    if (empty($columns)) {
        echo "Tabela '$tableName' nie istnieje lub nie ma kolumn.\n";
    } else {
        echo "Kolumny w tabeli '$tableName':\n";
        foreach ($columns as $column) {
            echo "- " . $column['name'] . " (Typ: " . $column['type'] . ")\n";
        }
    }
}

// Sprawdzanie kolumn w tabeli 'Lesson'
checkColumns('Lesson', $pdo);
?>
