import sqlite3

def add_to_database():
    try:
        connection = sqlite3.connect('database.db')
        cursor = connection.cursor()
        print("Connected to the database.")
        
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
                class_type VARCHAR(20) CHECK(class_type IN ('L', 'A', 'W', 'P', 'e', 'Lek','o','Ez','Zz','kons')),
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

        cursor.execute('''
            INSERT INTO Lesson (
            subject_id, 
                group_id, 
                    room_id, 
                        student_id, 
                            lesson_date, 
                                start_time, 
                                    end_time, 
                                        class_type, 
                                            responsible_lecturer_id, 
                                                substitute_lecturer_id
            ) VALUES 
            (1, 1, 1, 1, '2025-01-07', '12:15:00', '14:00:00', 'L', 1, NULL), 
            (2, 1, 2, 1, '2025-01-07', '14:15:00', '16:00:00', 'L', 2, NULL),
            (3, 2, 3, 1, '2025-01-07', '16:15:00', '18:00:00', 'W', 3, NULL), 
            (7, 6, 9, 1, '2025-01-08', '10:15:00', '12:00:00', 'W', 8, NULL), 
            (8, 7, 10, 1, '2025-01-08', '14:00:00', '15:30:00', 'A', 9, NULL), 
            (9, 6, 3, 1, '2025-01-09', '10:15:00', '12:00:00', 'W', 10, NULL), 
            (5, 4, 6, 1, '2025-01-09', '12:15:00', '14:00:00', 'W', 5, NULL), 
            (4, 3, 11, 1, '2025-01-09', '14:15:00', '16:00:00', 'Lek', 4, NULL), 
            (10, 8, 12, 1, '2025-01-09', '16:00:00', '19:00:00', 'P', 11, NULL), 
            (6, 2, 13, 1, '2025-01-10', '08:15:00', '10:00:00', 'W', 12, NULL), 
            (1, 2, 9, 1, '2025-01-10', '10:15:00', '12:00:00', 'W', 8, NULL),
            (11, 9, 6, 1, '2025-01-10', '12:15:00', '14:00:00', 'W', 13, NULL), 
            (11, 10, 1, 1, '2025-01-10', '14:15:00', '16:00:00', 'L', 13, NULL),
            (1, 1, 1, 1, '2025-01-13', '12:15:00', '14:00:00', 'L', 1, NULL), 
            (2, 1, 2, 1, '2025-01-13', '14:15:00', '16:00:00', 'L', 2, NULL),
            (3, 2, 3, 1, '2025-01-13', '16:15:00', '18:00:00', 'W', 3, NULL), 
            (4, 3, 4, 1, '2025-01-14', '08:15:00', '10:00:00', 'Lek', 4, NULL), 
            (5, 1, 5, 1, '2025-01-14', '10:15:00', '12:00:00', 'L', 5, 1), 
            (2, 4, 6, 1, '2025-01-14', '12:15:00', '14:00:00', 'W', 6, NULL), 
            (3, 1, 7, 1, '2025-01-14', '14:15:00', '16:00:00', 'L', 3, NULL),
            (6, 5, 8, 1, '2025-01-14', '16:15:00', '18:00:00', 'L', 7, NULL),
            (7, 6, 9, 1, '2025-01-15', '10:15:00', '12:00:00', 'W', 8, NULL), 
            (8, 7, 10, 1, '2025-01-15', '14:00:00', '15:30:00', 'A', 9, NULL), 
            (9, 6, 3, 1, '2025-01-16', '10:15:00', '12:00:00', 'W', 10, NULL), 
            (5, 4, 6, 1, '2025-01-16', '12:15:00', '14:00:00', 'W', 5, NULL), 
            (4, 3, 11, 1, '2025-01-16', '14:15:00', '16:00:00', 'Lek', 4, NULL), 
            (10, 8, 12, 1, '2025-01-16', '16:00:00', '19:00:00', 'P', 11, NULL), 
            (6, 2, 13, 1, '2025-01-17', '08:15:00', '10:00:00', 'W', 12, NULL), 
            (1, 2, 9, 1, '2025-01-17', '10:15:00', '12:00:00', 'W', 8, NULL)
        ''')
        
        connection.commit()
        print("Data inserted successfully.")
        
    except sqlite3.Error as error:
        print("Failed to insert data into sqlite table", error)
    finally:
        if connection:
            connection.close()
            print("The SQLite connection is closed.")

add_to_database()