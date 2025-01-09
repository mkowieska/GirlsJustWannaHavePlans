import sqlite3

def add_to_database():
    try:
        connection = sqlite3.connect('database.db')
        cursor = connection.cursor()
        
        print("Connected to the database.")

        #!!!!!!!
        # dane na sztywno do Lesson, by zrobic filtrowanie
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
            (1, 1, 1, 1, '2025-01-07', '12:15:00', '14:00:00', 'L', 1, NULL), #SI - 337 - . - ja - ... - AS
            (2, 1, 2, 2, '2025-01-07', '14:15:00', '16:00:00', 'L', 2, NULL), #SI - 337 - . - ja - ... - AS
            (3, 3, 3, 3, '2025-01-07', '16:15:00', '18:00:00', 'W', 3, NULL), #SI - 337 - . - ja - ... - AS
            (4, 4, 4, 4, '2025-01-08', '10:15:00', '12:00:00', 'W', 4, NULL), #SI - 337 - . - ja - ... - AS
            (5, 5, 5, 5, '2025-01-08', '14:00:00', '15:30:00', 'A', 5, NULL), #SI - 337 - . - ja - ... - AS
            (6, 6, 6, 6, '2025-01-08', '18:00:00', '21:00:00', 'Projekt', 6, NULL), #SI - 337 - . - ja - ... - AS
            (7, 7, 7, 7, '2025-01-09', '10:15:00', '12:00:00', 'W', 7, NULL), #SI - 337 - . - ja - ... - AS
            (8, 8, 8, 8, '2025-01-09', '12:15:00', '14:00:00', 'W', 8, NULL), #SI - 337 - . - ja - ... - AS
            (9, 9, 9, 9, '2025-01-09', '14:15:00', '16:00:00', 'Lek', 9, NULL), #SI - 337 - . - ja - ... - AS
            (10, 10, 10, 10, '2025-01-09', '16:00:00', '19:00:00', 'Projekt', 10, NULL), #SI - 337 - . - ja - ... - AS
            (11, 11, 11, 11, '2025-01-10', '08:15:00', '10:00:00', 'W', 11, NULL), #SI - 337 - . - ja - ... - AS
            (12, 12, 12, 12, '2025-01-10', '10:15:00', '12:00:00', 'W', 12, NULL), #SI - 337 - . - ja - ... - AS
            (13, 13, 13, 13, '2025-01-10', '12:15:00', '14:00:00', 'W', 13, NULL), #SI - 337 - . - ja - ... - AS
            (14, 14, 14, 14, '2025-01-10', '14:15:00', '16:00:00', 'L', 14, NULL) #SI - 337 - . - ja - ... - AS
        ''')
        #dodac nastepny tyg
        #dac kom ze skrotami co sa po prawej, skopiowac je i tu wkleic
        connection.commit()
        print("Added to the table successfully.")
    except sqlite3.Error as e:
        print(f"SQLite error: {e}") 
    finally:
        connection.close()
        print("Database connection closed.")

add_to_database()
