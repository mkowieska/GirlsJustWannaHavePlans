// Funkcje formatujące daty
function formatDate(date) {
    const options = { year: 'numeric', month: '2-digit', day: '2-digit' };
    return date.toLocaleDateString('pl-PL', options);
}

let currentDate = new Date();
let currentView = 'week';

function updateTodayButton() {
    const todayButton = document.getElementById("today");
    todayButton.textContent = "Dzisiaj: " + formatDate(currentDate);
}

function navigateCalendar(step) {
    if (currentView === 'day') currentDate.setDate(currentDate.getDate() + step);
    else if (currentView === 'week') currentDate.setDate(currentDate.getDate() + step * 7);
    else if (currentView === 'month') currentDate.setMonth(currentDate.getMonth() + step);
    else if (currentView === 'semester') currentDate.setFullYear(currentDate.getFullYear() + step);
    renderCalendar();
}

function changeView(view) {
    currentView = view;
    renderCalendar();
}

// Renderowanie tabel i zakresów
function renderCalendar() {
    const calendarTable = document.getElementById("calendar-table");
    let tableHTML = '';
    let dateRangeText = '';

    if (currentView === 'day') {
        tableHTML = renderDayTable();
        dateRangeText = getDayRange();
    } else if (currentView === 'week') {
        tableHTML = renderWeekTable();
        dateRangeText = getWeekRange();
    } else if (currentView === 'month') {
        tableHTML = renderMonthTable();
        dateRangeText = getMonthRange();
    } else if (currentView === 'semester') {
        tableHTML = renderSemesterTable();
        dateRangeText = getSemesterRange();
    }

    calendarTable.innerHTML = tableHTML;
    document.getElementById("date-range").textContent = "Zakres dat: " + dateRangeText;
}

// Funkcje pomocnicze
function getDayRange() {
    return formatDate(currentDate);
}

function getWeekRange() {
    const startOfWeek = getStartOfWeek(currentDate);
    const endOfWeek = new Date(startOfWeek);
    endOfWeek.setDate(startOfWeek.getDate() + 6);
    return `${formatDate(startOfWeek)} - ${formatDate(endOfWeek)}`;
}

function getMonthRange() {
    const startOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
    const endOfMonth = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
    return `${formatDate(startOfMonth)} - ${formatDate(endOfMonth)}`;
}

function getSemesterRange() {
    const semesterStart = new Date(currentDate.getFullYear(), 9, 1);
    const semesterEnd = new Date(currentDate.getFullYear() + 1, 1, 31);
    return `${formatDate(semesterStart)} - ${formatDate(semesterEnd)}`;
}

function renderDayTable() {
    const date = currentDate;
    const dayName = date.toLocaleDateString('pl-PL', { weekday: 'long' });
    return `
        <table class="schedule">
            <tr>
                <th>Godzina</th>
                <th>${dayName}</th>
            </tr>
            ${generateTimeRows()}
        </table>
    `;
}

function renderWeekTable() {
    const startOfWeek = getStartOfWeek(currentDate);
    const daysOfWeek = getDaysOfWeek(startOfWeek);
    let tableRows = '';
    for (let i = 7; i <= 20; i++) {
        tableRows += `<tr><td>${i}</td>${daysOfWeek.map(() => `<td></td>`).join('')}</tr>`;
    }
    return `
        <table class="schedule">
            <tr>
                <th></th>
                ${daysOfWeek.map(day => `<th>${day}</th>`).join('')}
            </tr>
            ${tableRows}
        </table>
    `;
}

function renderMonthTable() {
    const month = currentDate.getMonth();
    const year = currentDate.getFullYear();
    let rows = '';
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();

    let currentDay = 1;
    let weekRow = '';
    let dayOfWeek = firstDay === 0 ? 6 : firstDay - 1;

    while (currentDay <= daysInMonth) {
        if (dayOfWeek === 0) weekRow = '';
        if (currentDay === 1 && dayOfWeek !== 0) {
            weekRow += '<td></td>'.repeat(dayOfWeek);
        }

        weekRow += `<td>${currentDay}</td>`;
        currentDay++;
        dayOfWeek++;

        if (dayOfWeek === 7) {
            rows += `<tr>${weekRow}</tr>`;
            dayOfWeek = 0;
        }
    }

    if (dayOfWeek !== 0) {
        weekRow += '<td></td>'.repeat(7 - dayOfWeek);
        rows += `<tr>${weekRow}</tr>`;
    }

    return `
        <table class="schedule">
            <tr>
                <th>Pon</th><th>Wt</th><th>Śr</th><th>Czw</th><th>Pt</th><th>Sob</th><th>Nie</th>
            </tr>
            ${rows}
        </table>
    `;
}

function renderSemesterTable() {
    const semesterStart = new Date(currentDate.getFullYear(), 9, 1);
    const semesterEnd = new Date(currentDate.getFullYear() + 1, 1, 31);
    const semesterDates = [];
    let currentDay = semesterStart;

    while (currentDay <= semesterEnd) {
        semesterDates.push(new Date(currentDay));
        currentDay.setDate(currentDay.getDate() + 1);
    }

    let rows = '';
    semesterDates.forEach(date => {
        rows += `<tr><td>${date.toLocaleDateString('pl-PL')}</td><td>Wydarzenia</td></tr>`;
    });

    return `
        <table class="schedule">
            <tr>
                <th>Data</th>
                <th>Wydarzenie</th>
            </tr>
            ${rows}
        </table>
    `;
}

function generateTimeRows() {
    let rows = '';
    for (let i = 7; i <= 20; i++) {
        rows += `<tr><td>${i}</td><td></td></tr>`;
    }
    return rows;
}

function getStartOfWeek(date) {
    const day = date.getDay();
    const diff = date.getDate() - day + (day === 0 ? -6 : 1);
    return new Date(date.setDate(diff));
}

function getDaysOfWeek(startOfWeek) {
    const days = ['poniedziałek', 'wtorek', 'środa', 'czwartek', 'piątek', 'sobota', 'niedziela'];
    return days.map((day, index) => {
        const date = new Date(startOfWeek);
        date.setDate(startOfWeek.getDate() + index);
        return date.toLocaleDateString('pl-PL', { weekday: 'long' });
    });
}

//obsługa filtrów

function fetchRoomScheduleWithFilters(roomNumber) {
    const filters = ['wydzial', 'typ_studiow', 'semestr', 'wykladowca', 'forma_przedmiotu', 'przedmiot', 'sala', 'grupa', 'numer_albumu'];
    const params = new URLSearchParams();
    
    params.append('room', roomNumber);

    filters.forEach(id => {
        const value = document.getElementById(id).value.trim();
        if (value) {
            params.append(id, value);
        }
    });

    const url = `https://plan.zut.edu.pl/schedule.php?kind=room&query=${params.toString()}`;

    fetch(url)
        .then(response => {
            if (!response.ok) {
                throw new Error(`Błąd podczas pobierania danych: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            displayRoomSchedule(data);
        })
        .catch(error => {
            console.error("Błąd: ", error);
            alert("Wystąpił błąd podczas pobierania danych dla sali.");
        });
}

//wyświetlanie sali
function displayRoomSchedule(data) {
    const calendarTable = document.getElementById('calendar-table');
    calendarTable.innerHTML = ""; 

    if (data.length === 0) {
        calendarTable.innerHTML = "<p>Brak danych dla podanej sali.</p>";
        return;
    }

    const table = document.createElement('table');
    table.innerHTML = `
        <tr>
            <th>Data</th>
            <th>Godzina rozpoczęcia</th>
            <th>Godzina zakończenia</th>
            <th>Przedmiot</th>
            <th>Wykładowca</th>
            <th>Grupa</th>
        </tr>
    `;

    data.forEach(lesson => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${lesson.date}</td>
            <td>${lesson.start_time}</td>
            <td>${lesson.end_time}</td>
            <td>${lesson.subject}</td>
            <td>${lesson.lecturer}</td>
            <td>${lesson.group}</td>
        `;
        table.appendChild(row);
    });

    calendarTable.appendChild(table);
}

