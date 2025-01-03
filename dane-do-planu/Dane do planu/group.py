import requests
import json
import mysql.connector
import string

def fetch_group_data(group_number):
    url = f"https://plan.zut.edu.pl/schedule.php?kind=group&query={group_number}"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    }
    response = requests.get(url, headers=headers)

    if response.status_code != 200:
        print(f"Error fetching data for group {group_number}: HTTP {response.status_code}")
        return None

    try:
        data = json.loads(response.text)
        group_names = [item["item"] for item in data if isinstance(item, dict) and "item" in item]
        return [(name, 0) for name in group_names]
    except json.JSONDecodeError as e:
        print(f"Error decoding JSON for group {group_number}: {e}")
        return None

def insert_into_database(group_data):
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='B7uWyeGcjqRX3bv!',
            database='lepszy_plan'
        )
        cursor = connection.cursor()

        insert_query = """
        INSERT INTO `Group` (group_name)
        VALUES (%s)
        """
        cursor.executemany(insert_query, [(name,) for name, _ in group_data])
        connection.commit()

        print(f"Inserted {len(group_data)} groups into the database.")
    except mysql.connector.Error as err:
        print(f"Database error: {err}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    for group_number in string.ascii_uppercase:
        group_data = fetch_group_data(group_number)
        if group_data:
            insert_into_database(group_data)