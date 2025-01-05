import requests
import sqlite3
import json
from datetime import datetime

# Database Connection Configuration
connection = sqlite3.connect('database.db')
cursor = connection.cursor()

# API Fetch Function
def fetch_student_schedule(album_number):
    url = f"https://plan.zut.edu.pl/schedule_student.php?number={album_number}"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    }
    response = requests.get(url, headers=headers)
    if response.status_code != 200:
        print(f"Error fetching data for album {album_number}: HTTP {response.status_code}")
        return None
    try:
        return json.loads(response.text)
    except json.JSONDecodeError:
        print("Failed to decode JSON response.")
        return None

# Database Query Functions
def get_id_from_table(cursor, table, column, value):
    query = f"SELECT id FROM \"{table}\" WHERE {column} = ?"
    cursor.execute(query, (value,))
    result = cursor.fetchone()
    return result[0] if result else None

def insert_lesson(cursor, lesson_data):
    query = '''INSERT INTO Lesson (subject_id, group_id, room_id, student_id, lesson_date, start_time, end_time, class_type, responsible_lecturer_id, substitute_lecturer_id)
               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NULL)'''
    cursor.execute(query, lesson_data)

def process_album_number(album_number):
    data = fetch_student_schedule(album_number)
    if not data or len(data) < 2:
        print(f"No valid data for album {album_number}")
        return

    try:
        # SQLite connection
        connection = sqlite3.connect('database.db')
        cursor = connection.cursor()

        student_id = get_id_from_table(cursor, 'Student', 'album_number', album_number)
        if not student_id:
            print(f"No student found for album {album_number}")
            return
        
        for lesson in data[1:]:
            subject_id = get_id_from_table(cursor, 'Subject', 'name', lesson['subject'])
            group_id = get_id_from_table(cursor, 'Group', 'group_name', lesson['group_name'])
            room_id = get_id_from_table(cursor, 'Room', 'name', lesson['room'])
            lecturer_id = get_id_from_table(cursor, 'Lecturer', 'last_name', lesson['worker'].split(' ')[-1])

            lesson_date = datetime.fromisoformat(lesson['start']).date()
            start_time = datetime.fromisoformat(lesson['start']).time()
            end_time = datetime.fromisoformat(lesson['end']).time()

            if not all([subject_id, group_id, room_id, lecturer_id]):
                print(f"Missing data for lesson: {lesson['title']}")
                continue

            lesson_data = (
                subject_id, group_id, room_id, student_id,
                lesson_date, start_time, end_time
            )

            insert_lesson(cursor, lesson_data)

        connection.commit()
        print(f"Processed album {album_number} successfully.")

    except sqlite3.Error as err:
        print(f"Database error: {err}")
    finally:
        if connection:
            cursor.close()
            connection.close()

if __name__ == '__main__':
    for album in range(53000, 53008):
        process_album_number(album)
