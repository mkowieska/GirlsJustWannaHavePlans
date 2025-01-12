from flask import Flask, request, jsonify
import sqlite3
from flask_cors import CORS 

app = Flask(__name__)
CORS(app) 
def check_room_existence(room_number):
    try:
        connection = sqlite3.connect('database.db')
        cursor = connection.cursor()

        cursor.execute("SELECT id FROM Room WHERE name = ?", (room_number,))
        room = cursor.fetchone()
        
        connection.close()

        if room:
            return room[0] 
        else:
            return None  
    except sqlite3.Error as e:
        print(f"SQLite error: {e}")
        return None

@app.route('/get-room-schedule', methods=['POST'])
def get_room_schedule():
    data = request.get_json()
    room_number = data.get('room')
    filters = data.get('filters', {})

    try:
        connection = sqlite3.connect('database.db')
        cursor = connection.cursor()

        query = '''
            SELECT
                lesson_date,
                start_time,
                end_time,
                Subject.name AS subject_name,
                Lecturer.first_name || ' ' || Lecturer.last_name AS lecturer_name,
                `Group`.group_name
            FROM Lesson
            JOIN Subject ON Lesson.subject_id = Subject.id
            JOIN Lecturer ON Lesson.responsible_lecturer_id = Lecturer.id
            JOIN `Group` ON Lesson.group_id = `Group`.id
            JOIN Room ON Lesson.room_id = Room.id
            WHERE Room.name = ?
        '''
        cursor.execute(query, (room_number,))
        lessons = cursor.fetchall()

        schedule = []
        for lesson in lessons:
            schedule.append({
                'lesson_date': lesson[0],
                'start_time': lesson[1],
                'end_time': lesson[2],
                'subject': lesson[3],
                'lecturer': lesson[4],
                'group': lesson[5],
            })

        connection.close()
        return jsonify(schedule)
    except sqlite3.Error as e:
        return jsonify({'error': f"SQLite error: {e}"}), 500


if __name__ == '__main__':
    app.run(debug=True)