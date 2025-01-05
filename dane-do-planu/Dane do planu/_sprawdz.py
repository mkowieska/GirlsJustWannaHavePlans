import sqlite3

# Połączenie z bazą danych
connection = sqlite3.connect('database.db')
cursor = connection.cursor()

# Wykonanie zapytania
#ursor.execute("SELECT * FROM Room")
#ursor.execute("SELECT * FROM Student")
#ursor.execute("SELECT * FROM Subject")
cursor.execute("SELECT * FROM `Group`")
# cursor.execute("SELECT * FROM Lecturer")
# cursor.execute("SELECT * FROM Lesson")
# Pobranie wyników
rows = cursor.fetchall()

# Wyświetlenie wyników
for row in rows:
    print(row)

# Zamknięcie połączenia
cursor.close()
connection.close()
