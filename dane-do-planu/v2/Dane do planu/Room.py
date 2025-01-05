import requests
import json
import sqlite3

def fetch_room_data(room_number):
    url = f"https://plan.zut.edu.pl/schedule.php?kind=room&query={room_number}"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    }
    response = requests.get(url, headers=headers)

    if response.status_code != 200:
        print(f"Error fetching data for room {room_number}: HTTP {response.status_code}")
        return None

    try:
        data = json.loads(response.text)
        room_names = [item["item"] for item in data if isinstance(item, dict) and "item" in item]
        return room_names
    except json.JSONDecodeError as e:
        print(f"Error decoding JSON for room {room_number}: {e}")
        return None

def insert_into_database(room_data):
    print(f"Inserting {len(room_data)} rooms into the database...")
    try:
        connection = sqlite3.connect('database.db')
        cursor = connection.cursor()
        create_table_query = """
        CREATE TABLE IF NOT EXISTS Room (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL UNIQUE
        )
        """
        cursor.execute(create_table_query)

        insert_query = """
        INSERT OR IGNORE INTO Room (name)
        VALUES (?)
        """
        cursor.executemany(insert_query, [(room,) for room in room_data])
        connection.commit()

        print("Room data inserted into the database.")
    except sqlite3.Error as err:
        print(f"[ERROR] Database error: {err}")
    finally:
        if connection:
            connection.close()

if __name__ == "__main__":
    for room_number in range(0, 600):
        room_data = fetch_room_data(room_number)
        if room_data:
            insert_into_database(room_data)
    print("Process completed - Room.")
