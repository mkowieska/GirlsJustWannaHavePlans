import requests
import json
import mysql.connector
import string

def fetch_lecturer_data_by_letter(letter):
    print(f"[INFO] Fetching data for lecturers with last names starting with '{letter}'...")
    url = f"https://plan.zut.edu.pl/schedule.php?kind=teacher&query={letter}"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    }
    response = requests.get(url, headers=headers)

    if response.status_code != 200:
        print(f"[ERROR] Error fetching data for letter '{letter}': HTTP {response.status_code}")
        return None

    try:
        # Parse JSON data
        data = json.loads(response.text)
        print(f"[INFO] Successfully fetched data for letter '{letter}'. Number of items: {len(data)}")

        # Debug structure of the JSON response
        if len(data) > 0:
            print(f"[DEBUG] First item for letter '{letter}': {data[0]}")

        # Extract lecturer details from 'item' field
        lecturers = []
        for item in data:
            if isinstance(item, dict) and "item" in item:  # Check if item is a dictionary and contains "item"
                full_name = item["item"]
                # Split full name into first name and last name
                name_parts = full_name.split()
                if len(name_parts) >= 2:
                    first_name = name_parts[0]
                    last_name = " ".join(name_parts[1:])  # Handles multi-part last names
                    lecturers.append((first_name, last_name, ""))  # Title is empty as it's not in the data

        print(f"[INFO] Extracted {len(lecturers)} lecturers for letter '{letter}'")
        return lecturers
    except json.JSONDecodeError as e:
        print(f"[ERROR] Error decoding JSON for letter '{letter}': {e}")
        return None


def insert_into_database(lecturer_data):
    print(f"[INFO] Inserting {len(lecturer_data)} lecturers into the database...")
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='new_schema_proj_ai1'
        )
        cursor = connection.cursor()

        # Query to check if lecturer already exists
        check_query = """
        SELECT COUNT(*) FROM Lecturer
        WHERE first_name = %s AND last_name = %s
        """

        # Insert query
        insert_query = """
        INSERT INTO Lecturer (first_name, last_name, title)
        VALUES (%s, %s, %s)
        """

        new_inserts = 0  # Counter for new inserts

        for lecturer in lecturer_data:
            first_name, last_name, title = lecturer

            # Check if the lecturer is already in the database
            cursor.execute(check_query, (first_name, last_name))
            result = cursor.fetchone()
            if result[0] == 0:  # If lecturer does not exist
                cursor.execute(insert_query, (first_name, last_name, title))
                new_inserts += 1
            else:
                print(f"[INFO] Lecturer {first_name} {last_name} already exists. Skipping.")

        connection.commit()
        print(f"[INFO] Inserted {new_inserts} new lecturers into the database.")
    except mysql.connector.Error as err:
        print(f"[ERROR] Database error: {err}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    alphabet = string.ascii_uppercase  # Generates A-Z
    print("[INFO] Starting to fetch data for all letters A-Z.")
    for letter in alphabet:
        lecturer_data = fetch_lecturer_data_by_letter(letter)
        if lecturer_data:
            insert_into_database(lecturer_data)
    print("[INFO] Process completed.")
