import mysql.connector
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


def insert_student_and_groups(album_number, groups_data):
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='B7uWyeGcjqRX3bv!',
            database='lepszy_plan'
        )
        cursor = connection.cursor()

        cursor.execute("SELECT id FROM `Student` WHERE album_number = %s", (album_number,))
        student_id_result = cursor.fetchone()

        if not student_id_result:
            print(f"Student with album number {album_number} not found. Adding student.")
            cursor.execute("INSERT INTO `Student` (album_number) VALUES (%s)", (album_number,))
            connection.commit()
            student_id = cursor.lastrowid
        else:
            student_id = student_id_result[0]

        for group_name in groups_data:
            cursor.execute("SELECT id FROM `Group` WHERE group_name = %s", (group_name,))
            group_id_result = cursor.fetchone()

            if not group_id_result:
                print(f"Group '{group_name}' not found. Adding group.")
                cursor.execute("INSERT INTO `Group` (group_name) VALUES (%s)", (group_name,))
                connection.commit()
                group_id = cursor.lastrowid
            else:
                group_id = group_id_result[0]

            cursor.execute("SELECT 1 FROM `StudentGroup` WHERE student_id = %s AND group_id = %s", (student_id, group_id))
            if not cursor.fetchone():
                print(f"Adding student {album_number} to group '{group_name}'.")
                cursor.execute("INSERT INTO `StudentGroup` (student_id, group_id) VALUES (%s, %s)", (student_id, group_id))
                connection.commit()

    except mysql.connector.Error as err:
        print(f"Database error: {err}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()


if __name__ == "__main__":
    for album_number in range(53000, 53050):
        groups_data = fetch_student_groups(album_number)
        if groups_data:
            insert_student_and_groups(album_number, groups_data)
