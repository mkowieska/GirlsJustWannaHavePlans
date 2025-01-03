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
        print(f"❌ Błąd pobierania danych dla grupy {group_number}: HTTP {response.status_code}")
        return None

    try:
        data = json.loads(response.text)
        group_names = [item["item"] for item in data if isinstance(item, dict) and "item" in item]
        return [(name, 0) for name in group_names]  
    except json.JSONDecodeError as e:
        print(f"Błąd dekodowania JSON dla grupy {group_number}: {e}")
        return None

def group_exists_in_database(connection, group_name):
    try:
        cursor = connection.cursor()
        check_query = "SELECT COUNT(*) FROM `Group` WHERE name = %s"
        cursor.execute(check_query, (group_name,))
        result = cursor.fetchone()
        return result[0] > 0
    except mysql.connector.Error as err:
        print(f"[ERROR] Database error while checking group existence: {err}")
        return False
    finally:
        cursor.close() 

def insert_into_database(group_data, group_letter):
    try:
        connection = mysql.connector.connect(
            host='localhost',
            user='root',
            password='',
            database='new_schema_proj_ai1'
        )
        cursor = connection.cursor()

        insert_query = """
        INSERT INTO `Group` (name)
        VALUES (%s)
        """

        for group_name, _ in group_data:
            if not group_exists_in_database(connection, group_name):
                cursor.execute(insert_query, (group_name,))

        connection.commit()
        print(f"Wprowadzono {cursor.rowcount} nowe grupy do bazy danych dla litery {group_letter}.")
    except mysql.connector.Error as err:
        print(f"❌ Błąd bazy danych: {err}")
    finally:
        if connection.is_connected():
            cursor.close()
            connection.close()

if __name__ == "__main__":
    for group_number in string.ascii_uppercase:
        group_data = fetch_group_data(group_number)
        if group_data:
            insert_into_database(group_data, group_number)
    print("✅ Proces zakończony.")