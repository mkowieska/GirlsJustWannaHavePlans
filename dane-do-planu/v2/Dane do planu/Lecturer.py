import requests
import json
import sqlite3
import string

def fetch_lecturer_data_by_letter(letter):
    #print(f"Fetching data for lecturers with last names starting with '{letter}'...")
    url = f"https://plan.zut.edu.pl/schedule.php?kind=teacher&query={letter}"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    }
    response = requests.get(url, headers=headers)

    if response.status_code != 200:
        print(f"Error fetching data for letter '{letter}': HTTP {response.status_code}")
        return None

    try:
        data = json.loads(response.text)
        #print(f"Successfully fetched data for letter '{letter}'. Number of items: {len(data)}")
        
        lecturers = []
        for item in data:
            if isinstance(item, dict) and "item" in item:
                full_name = item["item"]
                name_parts = full_name.split()
                if len(name_parts) >= 2:
                    first_name = name_parts[0]
                    last_name = " ".join(name_parts[1:])  # Obsługuje nazwiska wieloczłonowe
                    lecturers.append((first_name, last_name))

        #print(f"Extracted {len(lecturers)} lecturers for letter '{letter}'")
        return lecturers
    except json.JSONDecodeError as e:
        print(f"Error decoding JSON for letter '{letter}': {e}")
        return None


def insert_into_database(lecturer_data):
    #print(f"Inserting {len(lecturer_data)} lecturers into the database...")
    try:
        connection = sqlite3.connect("database.db")
        cursor = connection.cursor()

        insert_query = """
        INSERT OR IGNORE INTO Lecturer (first_name, last_name)
        VALUES (?, ?)
        """

        cursor.executemany(insert_query, lecturer_data)
        connection.commit()

        #print(f"Inserted {len(lecturer_data)} lecturers into the database (ignoring duplicates).")
    except sqlite3.Error as err:
        print(f"[ERROR] Database error: {err}")
    finally:
        if connection:
            cursor.close()
            connection.close()


if __name__ == "__main__":
    alphabet = string.ascii_uppercase 
    #print("Starting to fetch data for all letters A-Z.")
    for letter in alphabet:
        lecturer_data = fetch_lecturer_data_by_letter(letter)
        if lecturer_data:
            insert_into_database(lecturer_data)
    print("Process completed - Lecturer.")
