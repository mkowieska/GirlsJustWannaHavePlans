<?php
function clearSubjectTable() {
    try {
        $pdo = new PDO('sqlite:database1.db');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $query = "DELETE FROM Subject";
        $pdo->exec($query);

        echo "Table 'Subject' has been cleared.\n";
    } catch (PDOException $e) {
        echo "Error clearing table: " . $e->getMessage() . "\n";
    }
}

// Wywołaj funkcję przed wstawieniem nowych danych
clearSubjectTable();
