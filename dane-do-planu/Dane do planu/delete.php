<?php
$current_year = date('Y');  // Pobiera bieżący rok
$cutoff_date = "$current_year-10-01";  // Ustawia datę na 1 października bieżącego roku

try {
    $db = new PDO('sqlite:database.db');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $check_table_query = $db->prepare("PRAGMA table_info(Lessons);");
    $check_table_query->execute();
    $columns = $check_table_query->fetchAll(PDO::FETCH_ASSOC);
    
    $column_names = array_column($columns, 'name');
    
    if (!in_array('class_date', $column_names)) {
        echo "Brak kolumny 'class_date' w tabeli 'Lessons'.\n";
        exit;
    }
    $delete_query = $db->prepare("DELETE FROM Lessons WHERE class_date < :cutoff_date");
    $delete_query->execute([':cutoff_date' => $cutoff_date]);
    $deleted_rows = $delete_query->rowCount();
    echo "Usunięto $deleted_rows lekcji przed datą $cutoff_date.\n";

} catch (PDOException $e) {
    echo "[ERROR] Błąd bazy danych: " . $e->getMessage() . "\n";
}
?>
