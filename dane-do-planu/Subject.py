import requests
import json
import mysql.connector
import string

def fetch_subject_data_by_query(query):
    print(f"🔄 Pobieranie danych dla przedmiotów z zapytaniem '{query}'...")
    url = f"https://plan.zut.edu.pl/schedule.php?kind=subject&query={query}"
    headers = {
        "User-Agent": "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/114.0.0.0 Safari/537.36"
    }
    response = requests.get(url, headers=headers)

    if response.status_code != 200:
        print(f"❌ Błąd pobierania danych dla zapytania '{query}': HTTP {response.status_code}")
        return None

    try:
        data = json.loads(response.text)
        print(f"✅ Pomyślnie pobrano dane dla zapytania '{query}'. Liczba elementów: {len(data)}")

        if len(data) > 0:
            print(f"Pierwszy element dla zapytania '{query}': {data[0]}")

        subjects = []
        for item in data:
            if isinstance(item, dict) and "item" in item:  
                subject_name = item["item"]
                subjects.append((subject_name,))

        print(f"Wyodrębniono {len(subjects)} przedmioty dla zapytania '{query}'")
        return subjects
    except json.JSONDecodeError as e:
        print(f"❌ Błąd dekodowania JSON dla zapytania '{query}': {e}")
        return None


def ensure_unique_index_on_subject():
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='new_schema_proj_ai1'
        )
        cursor = connection.cursor()

        unique_index_query = """
        ALTER TABLE Subject ADD UNIQUE(name)
        """
        cursor.execute(unique_index_query)
        connection.commit()
        print("Dodano indeks UNIQUE w kolumnie 'name' w tabeli 'Subject'.")
    except mysql.connector.Error as err:
        if "Duplicate" in str(err) or "already exists" in str(err):
            print("Indeks UNIQUE w kolumnie 'name' już istnieje.")
        else:
            print(f"❌ Błąd bazy danych podczas dodawania indeksu UNIQUE: {err}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()


def insert_subjects_into_database(subject_data):
    print(f"🔄 Wstawianie {len(subject_data)} przedmiotów do bazy danych...")
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='new_schema_proj_ai1'
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
        print(f"Wprowadzono przedmioty do bazy danych, duplikaty zignorowano.")
    except mysql.connector.Error as err:
        print(f"❌ Błąd bazy danych: {err}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    ensure_unique_index_on_subject()

    alphabet = string.ascii_uppercase  
    print("Rozpoczęcie pobierania danych dla wszystkich liter A-Z.")
    for letter in alphabet:
        query = letter  # We can directly use the letter for query
        subject_data = fetch_subject_data_by_query(query)
        if subject_data:
            insert_subjects_into_database(subject_data)
    print("✅ Proces zakończony.")
