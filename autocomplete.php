<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json");

// Funkcja autouzupełniania dla wykładowców
function autocompleteLecturer($query) {
    try {
        $db = new PDO('sqlite:database.db');
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Zapytanie do bazy danych, szuka w imieniu i nazwisku
        $stmt = $db->prepare("
            SELECT DISTINCT first_name || ' ' || last_name AS lecturer_name
            FROM Lecturer
            WHERE first_name LIKE :query OR last_name LIKE :query
            ORDER BY last_name ASC
            LIMIT 10
        ");

        $searchQuery = '%' . $query . '%';
        $stmt->bindParam(':query', $searchQuery, PDO::PARAM_STR);
        $stmt->execute();

        // Pobierz wyniki
        $lecturers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $lecturers;

    } catch (PDOException $e) {
        error_log("SQLite error: " . $e->getMessage());
        return ["error" => "Database error: " . $e->getMessage()];
    }
}

// Obsługa żądań POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);

    if (!empty($input['autocompleteQuery'])) {
        $query = $input['autocompleteQuery'];
        $result = autocompleteLecturer($query);
        echo json_encode($result);
    } else {
        echo json_encode(["error" => "Query is required for autocomplete."]);
    }
} else {
    echo json_encode(["error" => "Invalid request method."]);
}
