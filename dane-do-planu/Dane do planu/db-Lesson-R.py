import sqlite3

def add_to_database():
    try:
        connection = sqlite3.connect('database.db')
        cursor = connection.cursor()
        print("Connected to the database.")
        #!!!!!!!
        # dane na sztywno do Lesson, by zrobic filtrowanie
        cursor.execute('''
            INSERT INTO LessonR (
            subject_id, 
                group_id, 
                    room_id, 
                        student_id, 
                            lesson_date, 
                                start_time, 
                                    end_time, 
                                        class_type, 
                                            responsible_lecturer_id, 
                                                substitute_lecturer_id,
                                                    faculty,
                                                        semester,
                                                            study_type 
            ) VALUES 
            (1, 1, 1, 1, '2025-01-07', '12:15:00', '14:00:00', 'L', 1, NULL, 'WI', 'Z', 'S1'), 
            (2, 1, 2, 1, '2025-01-07', '14:15:00', '16:00:00', 'L', 2, NULL, 'WI', 'Z', 'S1'),
            (3, 2, 3, 1, '2025-01-07', '16:15:00', '18:00:00', 'W', 3, NULL, 'WI', 'Z', 'S1'), 
            (7, 6, 9, 1, '2025-01-08', '10:15:00', '12:00:00', 'W', 8, NULL, 'WI', 'Z', 'S1'), 
            (8, 7, 10, 1, '2025-01-08', '14:00:00', '15:30:00', 'A', 9, NULL, 'WI', 'Z', 'S1'), 
            (9, 6, 3, 1, '2025-01-09', '10:15:00', '12:00:00', 'W', 10, NULL, 'WI', 'Z', 'S1'), 
            (5, 4, 6, 1, '2025-01-09', '12:15:00', '14:00:00', 'W', 5, NULL, 'WI', 'Z', 'S1'), 
            (4, 3, 11, 1, '2025-01-09', '14:15:00', '16:00:00', 'Lek', 4, NULL, 'WI', 'Z', 'S1'), 
            (10, 8, 12, 1, '2025-01-09', '16:00:00', '19:00:00', 'P', 11, NULL, 'WI', 'Z', 'S1'), 
            (6, 2, 13, 1, '2025-01-10', '08:15:00', '10:00:00', 'W', 12, NULL, 'WI', 'Z', 'S1'), 
            (1, 2, 9, 1, '2025-01-10', '10:15:00', '12:00:00', 'W', 8, NULL, 'WI', 'Z', 'S1'),
            (11, 9, 6, 1, '2025-01-10', '12:15:00', '14:00:00', 'W', 13, NULL, 'WI', 'Z', 'S1'), 
            (11, 10, 1, 1, '2025-01-10', '14:15:00', '16:00:00', 'L', 13, NULL, 'WI', 'Z', 'S1'),
            (1, 1, 1, 1, '2025-01-13', '12:15:00', '14:00:00', 'L', 1, NULL, 'WI', 'Z', 'S1'), 
            (2, 1, 2, 1, '2025-01-13', '14:15:00', '16:00:00', 'L', 2, NULL, 'WI', 'Z', 'S1'),
            (3, 2, 3, 1, '2025-01-13', '16:15:00', '18:00:00', 'W', 3, NULL, 'WI', 'Z', 'S1'), 
            (4, 3, 4, 1, '2025-01-14', '08:15:00', '10:00:00', 'Lek', 4, NULL, 'WI', 'Z', 'S1'), 
            (5, 1, 5, 1, '2025-01-14', '10:15:00', '12:00:00', 'L', 5, 1, 'WI', 'Z', 'S1'), 
            (2, 4, 6, 1, '2025-01-14', '12:15:00', '14:00:00', 'W', 6, NULL, 'WI', 'Z', 'S1'), 
            (3, 1, 7, 1, '2025-01-14', '14:15:00', '16:00:00', 'L', 3, NULL, 'WI', 'Z', 'S1'),
            (6, 5, 8, 1, '2025-01-14', '16:15:00', '18:00:00', 'L', 7, NULL, 'WI', 'Z', 'S1'),
            (7, 6, 9, 1, '2025-01-15', '10:15:00', '12:00:00', 'W', 8, NULL, 'WI', 'Z', 'S1'), 
            (8, 7, 10, 1, '2025-01-15', '14:00:00', '15:30:00', 'A', 9, NULL, 'WI', 'Z', 'S1'), 
            (9, 6, 3, 1, '2025-01-16', '10:15:00', '12:00:00', 'W', 10, NULL, 'WI', 'Z', 'S1'), 
            (5, 4, 6, 1, '2025-01-16', '12:15:00', '14:00:00', 'W', 5, NULL, 'WI', 'Z', 'S1'), 
            (4, 3, 11, 1, '2025-01-16', '14:15:00', '16:00:00', 'Lek', 4, NULL, 'WI', 'Z', 'S1'), 
            (10, 8, 12, 1, '2025-01-16', '16:00:00', '19:00:00', 'P', 11, NULL, 'WI', 'Z', 'S1'), 
            (6, 2, 13, 1, '2025-01-17', '08:15:00', '10:00:00', 'W', 12, NULL, 'WI', 'Z', 'S1'), 
            (1, 2, 9, 1, '2025-01-17', '10:15:00', '12:00:00', 'W', 8, NULL, 'WI', 'Z', 'S1')
        ''')
        
        # na stronie planu zutu jest np. Algorytmy 2, ktore sa traktowane jako przedmiot polaczony z labami i wykladem, "Algorytmy 2 (WI, informatyka, SN, SPS)"
        # dlatego np. w przykladzie mojego planu Sieci sa jako numer 3 obojetnie czy to sa L czy W, bo to jest jeden przedmiot
        connection.commit()
        print("Added to the table successfully.")
    except sqlite3.Error as e:
        print(f"SQLite error: {e}") 
    finally:
        connection.close()
        print("Database connection closed.")

add_to_database()
