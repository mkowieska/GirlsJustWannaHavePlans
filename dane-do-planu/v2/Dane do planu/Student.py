import sqlite3
import requests
import json

def fetch_student_groups(album_number):
    url = f"https://plan.zut.edu.pl/schedule_student.php?number={album_number}"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    }
    response = requests.get(url, headers=headers)

    if response.status_code != 200:
        print(f"Error fetching data for album {album_number}: HTTP {response.status_code}")
        return None

    try:
        data = json.loads(response.text)
        if len(data) < 2 or not isinstance(data[1], dict):
            print(f"No valid group data found for album {album_number}.")
            return None

        groups_data = []
        for item in data:
            if isinstance(item, dict) and "group_name" in item:
                group_name = item["group_name"]
                groups_data.append(group_name)

        return groups_data if groups_data else None

    except json.JSONDecodeError as e:
        print(f"Error decoding JSON for album {album_number}: {e}")
        return None


def insert_student(album_number):
    try:
        # Połączenie z bazą SQLite
        connection = sqlite3.connect('database.db')
        cursor = connection.cursor()

        # Sprawdzanie, czy student już istnieje
        cursor.execute("SELECT id FROM `Student` WHERE album_number = ?", (album_number,))
        student_id_result = cursor.fetchone()

        if not student_id_result:
            # Jeśli studenta nie ma, dodajemy nowego
            print(f"Student z numerem albumu {album_number} nie istnieje. Dodajemy studenta.")
            cursor.execute("INSERT INTO `Student` (album_number) VALUES (?)", (album_number,))
            connection.commit()
            student_id = cursor.lastrowid
        else:
            student_id = student_id_result[0]
            print(f"Student z numerem albumu {album_number} już istnieje w bazie.")

    except sqlite3.Error as err:
        print(f"Błąd bazy danych SQLite: {err}")
    finally:
        # Zamykanie połączenia z bazą SQLite
        cursor.close()
        connection.close()


if __name__ == "__main__":
    for album_number in range(49000, 58999):
        insert_student(album_number)
    print("Process completed - Student.")