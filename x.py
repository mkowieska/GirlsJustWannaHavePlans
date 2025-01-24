import sqlite3

def show_tables_and_columns(db_path):
    # Połączenie z bazą danych
    conn = sqlite3.connect(db_path)
    cursor = conn.cursor()

    # Zapytanie, aby pobrać listę tabel
    cursor.execute("SELECT name FROM sqlite_master WHERE type='table';")
    tables = cursor.fetchall()

    # Wyświetlanie tabel i kolumn
    for table in tables:
        table_name = table[0]
        
        # Cytowanie nazw tabel (na wszelki wypadek)
        quoted_table_name = f'"{table_name}"'
        
        print(f"Tabela: {table_name}")
        
        # Zmienione zapytanie, aby uzyskać kolumny
        cursor.execute(f"PRAGMA table_info({quoted_table_name});")
        columns = cursor.fetchall()
        
        if columns:
            for column in columns:
                column_name = column[1]
                column_type = column[2]  # Typ kolumny
                is_nullable = "NOT NULL" if column[3] == 1 else "NULL"  # Jeśli kolumna jest nullable
                default_value = column[4]  # Wartość domyślna kolumny
                print(f"  Kolumna: {column_name} | Typ: {column_type} | Nullable: {is_nullable} | Domyślna wartość: {default_value}")
        else:
            print("  Brak kolumn w tabeli.")
        
        print("-" * 50)
    
    # Zamknięcie połączenia z bazą danych
    conn.close()

# Ścieżka do pliku bazy danych (zakładając, że jest w tym samym folderze co skrypt)
db_path = 'database.db'
show_tables_and_columns(db_path)
