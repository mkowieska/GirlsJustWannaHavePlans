import requests
import json
import mysql.connector
import sqlite3
import string

def fetch_subject_data_by_query(query):
    print(f"Fetching data for subjects with query '{query}'...")
    url = f"https://plan.zut.edu.pl/schedule.php?kind=subject&query={query}"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    }
    response = requests.get(url, headers=headers)

    if response.status_code != 200:
        print(f"Error fetching data for query '{query}': HTTP {response.status_code}")
        return None

    try:
        data = json.loads(response.text)
        print(f"Successfully fetched data for query '{query}'. Number of items: {len(data)}")

        if len(data) > 0:
            print(f"First item for query '{query}': {data[0]}")

        subjects = []
        for item in data:
            if isinstance(item, dict) and "item" in item:
                subject_name = item["item"]
                subjects.append((subject_name,))

        print(f"Extracted {len(subjects)} subjects for query '{query}'")
        return subjects
    except json.JSONDecodeError as e:
        print(f"Error decoding JSON for query '{query}': {e}")
        return None


def ensure_unique_index_on_subject():
    try:
        connection = sqlite3.connect('database.db')
        cursor = connection.cursor()
        unique_index_query = """
        CREATE UNIQUE INDEX IF NOT EXISTS idx_subject_name ON Subject(name)
        """
        cursor.execute(unique_index_query)
        connection.commit()
        print("Added UNIQUE index on 'name' column in 'Subject' table.")
    except sqlite3.Error as err:
        print(f"SQLite error while adding UNIQUE index: {err}")
    finally:
        # No need to check if connection is connected for SQLite
        if connection:
            cursor.close()
            connection.close()

def insert_subjects_into_database(subject_data):
    print(f"Inserting {len(subject_data)} subjects into the database...")
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='B7uWyeGcjqRX3bv!',
            database='lepszy_plan'
        )
        cursor = connection.cursor()

        insert_query = """
        INSERT IGNORE INTO Subject (name)
        VALUES (%s)
        """

        for subject in subject_data:
            subject_name = subject[0]
            cursor.execute(insert_query, (subject_name,))

        connection.commit()
        print(f"Inserted subjects into the database, duplicates ignored.")
    except mysql.connector.Error as err:
        print(f"Database error: {err}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    ensure_unique_index_on_subject()

    alphabet = string.ascii_uppercase 
    print("Starting to fetch data for all subjects A-Z.")
    for letter in alphabet:
        query = letter
        subject_data = fetch_subject_data_by_query(query)
        if subject_data:
            insert_subjects_into_database(subject_data)
    print("Process completed - Room.")

#Nwm czy lepeij kożytsać z SELECT czy unique - ktoś coś podpowie będę wdzięczna :D
#Odpowiedz czemu raz używam tego a raz tamtego