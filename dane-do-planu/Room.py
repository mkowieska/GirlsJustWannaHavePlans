import requests
import json
import mysql.connector

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
        # Parse JSON data
        data = json.loads(response.text)
        
        # Filter and extract room names (skip 'false' entries)
        room_names = [item["item"] for item in data if isinstance(item, dict) and "item" in item]

        # Assume capacity is unknown or fixed (default to 0)
        return [(name, 0) for name in room_names]  # Returning a list of tuples
    except json.JSONDecodeError as e:
        print(f"Error decoding JSON for room {room_number}: {e}")
        return None

def room_exists_in_database(connection, room_name):
    try:
        cursor = connection.cursor()
        check_query = "SELECT COUNT(*) FROM Room WHERE name = %s"
        cursor.execute(check_query, (room_name,))
        result = cursor.fetchone()
        return result[0] > 0
    except mysql.connector.Error as err:
        print(f"Database error while checking room existence: {err}")
        return False
    finally:
        cursor.close()

def insert_into_database(room_data):
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='new_schema_proj_ai1'
        )
        cursor = connection.cursor()

        insert_query = """
        INSERT INTO Room (name, capacity)
        VALUES (%s, %s)
        """

        for room_name, capacity in room_data:
            if not room_exists_in_database(connection, room_name):
                cursor.execute(insert_query, (room_name, capacity))

        connection.commit()
        print(f"Inserted {cursor.rowcount} new rooms into the database.")
    except mysql.connector.Error as err:
        print(f"Database error: {err}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    for room_number in range(0, 600):
        room_data = fetch_room_data(room_number)
        if room_data:
            insert_into_database(room_data)
