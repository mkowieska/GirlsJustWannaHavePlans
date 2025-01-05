import schedule
import time
import subprocess

def run_scripts():
    scripts = [
        "group.py",
        "Student.py",
        "Lecturer.py",
        "Room.py",
        "Subject.py"
    ]

    for script in scripts:
        subprocess.run(["python3", script])

schedule.every(1).hour.do(run_scripts)

while True:
    schedule.run_pending()
    time.sleep(60) 


#w terminalu/ na serwerze wpisujemy: python3 scheduler.py &   
#jak ktoś ma lepszy pomysł to dawać znać :D