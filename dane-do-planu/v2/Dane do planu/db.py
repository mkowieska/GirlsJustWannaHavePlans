import sqlite3

def create_database():
    try:
        # Connect to the SQLite database
        connection = sqlite3.connect('database.db')
        cursor = connection.cursor()
        
        print("Connected to the database.")

        # Creating tables
        cursor.execute('''
            CREATE TABLE IF NOT EXISTS Lecturer (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                first_name VARCHAR(100) NOT NULL,
                last_name VARCHAR(100) NOT NULL
            )
        ''')

        cursor.execute('''
            CREATE TABLE IF NOT EXISTS Subject (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL
            )
        ''')

        cursor.execute('''
            CREATE TABLE IF NOT EXISTS Room (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name VARCHAR(100) NOT NULL
            )
        ''')

        cursor.execute('''
            CREATE TABLE IF NOT EXISTS `Group` (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                group_name VARCHAR(100) UNIQUE NOT NULL
            )
        ''')

        cursor.execute('''
            CREATE TABLE IF NOT EXISTS Student (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                album_number INTEGER UNIQUE NOT NULL
            )
        ''')

        cursor.execute('''
            CREATE TABLE IF NOT EXISTS Lesson (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                subject_id INTEGER NOT NULL,
                group_id INTEGER NOT NULL,
                room_id INTEGER NOT NULL,
                student_id INTEGER NOT NULL,
                lesson_date DATE NOT NULL,
                start_time TIME NOT NULL,
                end_time TIME NOT NULL,
                class_type VARCHAR(20) CHECK(class_type IN ('L', 'A', 'W', 'Projekt', 'Egzamin', 'Odwołane')),
                responsible_lecturer_id INTEGER NOT NULL,
                substitute_lecturer_id INTEGER,
                FOREIGN KEY(subject_id) REFERENCES Subject(id),
                FOREIGN KEY(group_id) REFERENCES `Group`(id),
                FOREIGN KEY(room_id) REFERENCES Room(id),
                FOREIGN KEY(student_id) REFERENCES Student(id),
                FOREIGN KEY(responsible_lecturer_id) REFERENCES Lecturer(id),
                FOREIGN KEY(substitute_lecturer_id) REFERENCES Lecturer(id)
            )
        ''')

        # Commit changes
        connection.commit()
        print("Tables created successfully.")
    except sqlite3.Error as e:
        print(f"SQLite error: {e}")
    finally:
        # Close the connection
        connection.close()
        print("Database connection closed.")

# Call the function to create the database and tables
create_database()
