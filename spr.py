import sqlite3

# Ścieżka do bazy danych SQLite
db_path = 'database.db'

# Połączenie z bazą danych
conn = sqlite3.connect(db_path)

# Tworzenie obiektu cursor
cursor = conn.cursor()

# Zapytanie o strukturę tabeli Student
cursor.execute("PRAGMA table_info(Student);")

# Pobranie wszystkich wyników zapytania
columns = cursor.fetchall()

# Wyświetlenie nazw kolumn
for column in columns:
    print(column[1])  # column[1] to nazwa kolumny

# Zamknięcie połączenia
conn.close()
