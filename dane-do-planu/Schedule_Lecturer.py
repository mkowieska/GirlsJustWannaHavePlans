import requests
import json
import mysql.connector

def fetch_schedule_data(teacher_name, start_date, end_date):
    url = f"https://plan.zut.edu.pl/schedule_student.php?teacher={teacher_name}&start={start_date}&end={end_date}"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    }
    response = requests.get(url, headers=headers)

    if response.status_code != 200:
        print(f"❌ Błąd pobierania danych dla wykładowcy {teacher_name}: HTTP {response.status_code}")
        return None

    try:
        data = json.loads(response.text)
        print(f"🔍 Przykładowa odpowiedź API: {data[:1]}") if data else print("❌ Pusta odpowiedź API.")
        return data
    except json.JSONDecodeError as e:
        print(f"❌ Błąd dekodowania JSON: {e}")
        return None

def process_data(raw_data):
    processed = []
    for item in raw_data:
        if isinstance(item, dict):
            print(f"🔍 Przetwarzanie elementu: {item}")
            processed.append({
                'title': item.get('title', 'Brak danych'),
                'description': item.get('description', 'Brak danych'),
                'start': item.get('start', '1970-01-01T00:00'),
                'end': item.get('end', '1970-01-01T00:00'),
                'worker_title': item.get('worker_title', 'Brak danych'),
                'worker': item.get('worker', 'Brak danych'),
                'lesson_form': item.get('lesson_form', 'Brak danych'),
                'lesson_form_short': item.get('lesson_form_short', 'Brak danych'),
                'group_name': item.get('group_name', 'Brak danych'),
                'tok_name': item.get('tok_name', 'Brak danych'),
                'room': item.get('room', 'Brak danych'),
                'lesson_status': item.get('lesson_status', 'Brak danych'),
                'lesson_status_short': item.get('lesson_status_short', 'Brak danych'),
                'status_item': item.get('status_item', 'Brak danych'),
                'subject': item.get('subject', 'Brak danych'),
                'hours': item.get('hours', 0),
                'color': item.get('color', '#FFFFFF'),
                'borderColor': item.get('borderColor', '#000000')
            })
    return processed

def entry_exists_in_database(connection, start, end, room, group_name):
    try:
        cursor = connection.cursor()
        query = """
            SELECT COUNT(*) FROM schedule 
            WHERE start = %s AND end = %s AND room = %s AND group_name = %s
        """
        cursor.execute(query, (start, end, room, group_name))
        result = cursor.fetchone()
        return result[0] > 0
    except mysql.connector.Error as err:
        print(f"❌ Błąd bazy danych przy sprawdzaniu wpisu: {err}")
        return False
    finally:
        cursor.close()

def insert_into_database(processed_data):
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='new_schema_proj_ai1'
        )
        cursor = connection.cursor()

        insert_query = """
        INSERT INTO schedule (
            title, description, start, end, worker_title, worker, lesson_form, 
            lesson_form_short, group_name, tok_name, room, lesson_status, 
            lesson_status_short, status_item, subject, hours, color, borderColor
        )
        VALUES (
            %(title)s, %(description)s, %(start)s, %(end)s, %(worker_title)s, %(worker)s,
            %(lesson_form)s, %(lesson_form_short)s, %(group_name)s, %(tok_name)s, %(room)s,
            %(lesson_status)s, %(lesson_status_short)s, %(status_item)s, %(subject)s, 
            %(hours)s, %(color)s, %(borderColor)s
        )
        """

        for item in processed_data:
            if not entry_exists_in_database(
                connection,
                item['start'],
                item['end'],
                item['room'],
                item['group_name']
            ):
                cursor.execute(insert_query, item)

        connection.commit()
        print(f"✅ Wstawiono {cursor.rowcount} nowych wpisów do tabeli `schedule`.")
    except mysql.connector.Error as err:
        print(f"❌ Błąd bazy danych: {err}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

def main():
    teacher_name = "Karczmarczyk Aleksandra"
    start_date = "2024-09-30T00:00:00+02:00"
    end_date = "2024-10-07T00:00:00+02:00"

    print("🔄 Pobieranie danych z API...")
    raw_data = fetch_schedule_data(teacher_name, start_date, end_date)
    if not raw_data:
        print("❌ Brak danych do zapisania.")
        return

    print("🛠️ Przetwarzanie danych...")
    processed_data = process_data(raw_data)

    print("💾 Zapisywanie danych do bazy...")
    insert_into_database(processed_data)
    print("🎉 Wszystkie dane zostały pomyślnie dodane do bazy danych!")


if __name__ == '__main__':
    main()
