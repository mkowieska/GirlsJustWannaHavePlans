// Funkcje związane z filtrowaniem
function Choosefilters() {
    const filterOptions = document.querySelector('.filter-options');
    filterOptions.style.display = filterOptions.style.display === 'block' ? 'none' : 'block';
}

function Motywfilters() {
    const filterOptions = document.querySelector('.motywy');
    filterOptions.style.display = filterOptions.style.display === 'block' ? 'none' : 'block';
}

// Obsługa motywu
function changeTheme(theme) {
    const body = document.body;

    body.classList.remove('default', 'white-black', 'yellow-black', 'black-yellow');
    body.classList.add(theme);
}

// Obsługa wielkości czcionki
let fontSize = 16;

document.getElementById('minus').addEventListener('click', () => {
    fontSize = Math.max(fontSize - 1, 10); // Minimalny rozmiar 10px
    document.body.style.fontSize = fontSize + 'px';
});

document.getElementById('plus').addEventListener('click', () => {
    fontSize = Math.min(fontSize + 1, 24); // Maksymalny rozmiar 24px
    document.body.style.fontSize = fontSize + 'px';
});

// Obsługa przycisków filtrowania
// document.getElementById('search').addEventListener('click', function() {
//     const roomInput = document.getElementById('sala').value.trim();
    
//     // Zbieramy wszystkie filtry z formularza
//     const filters = {
//         wydzial: document.getElementById('wydzial').value,
//         typ_studiow: document.getElementById('typ_studiow').value,
//         semestr: document.getElementById('semestr').value,
//         wykladowca: document.getElementById('wykladowca').value,
//         forma_przedmiotu: document.getElementById('forma_przedmiotu').value,
//         przedmiot: document.getElementById('przedmiot').value,
//         sala: roomInput,  // Numer sali
//         grupa: document.getElementById('grupa').value,
//         numer_albumu: document.getElementById('numer_albumu').value // Numer albumu
//     };

//     console.log("Filtry:");
//     Object.keys(filters).forEach(id => {
//         console.log(id, ":", filters[id]);
//     });

//     // Wywołanie funkcji do pobrania danych z bazy
//     fetchRoomScheduleFromDatabase(roomInput, filters);
// });
// Obsługa przycisków filtrowania
// Obsługa przycisków filtrowania
document.getElementById('search').addEventListener('click', function() {
    // Odczytanie wartości z formularza
    const alnumInput = document.getElementById('numer_albumu').value.trim();  // Numer albumu
    const lecturerInput = document.getElementById('wykladowca').value.trim();  // Wykładowca
    const groupInput = document.getElementById('grupa').value.trim();  // Grupa

    // Tworzenie obiektu filtrów
    const filterValues = {
        alnumInput,
        lecturerInput,
        groupInput,
        wydzial: document.getElementById('wydzial').value,
        typ_studiow: document.getElementById('typ_studiow').value,
        semestr: document.getElementById('semestr').value,
        forma_przedmiotu: document.getElementById('forma_przedmiotu').value,
        przedmiot: document.getElementById('przedmiot').value,
        sala: document.getElementById('sala').value
    };

    // Dodanie logu, aby sprawdzić, co zawiera filterValues przed wysłaniem
    console.log("Filtry przed wysłaniem:", filterValues);

    // Wywołanie funkcji do pobrania danych z bazy
    fetchRoomScheduleFromDatabase(filterValues);
});

// Funkcja do wysyłania danych do serwera
function fetchRoomScheduleFromDatabase(filterValues) {
    fetch('http://127.0.0.1:5000/sala.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ filters: filterValues })  // Wysyłamy filtry w obiekcie 'filters'
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('Błąd odpowiedzi serwera: ' + response.statusText);
        }
        return response.json();  // Zmieniamy na response.json(), żeby otrzymać odpowiedź jako JSON
    })
    .then(data => {
        console.log('Odpowiedź serwera:', data);  
        // Obsługuje dane harmonogramu
        if (data.error) {
            console.error('Błąd:', data.error);
        } else {
            data.forEach(lesson => {
                console.log(`Data: ${lesson.lesson_date}`);
                console.log(`Godzina rozpoczęcia: ${lesson.start_time}`);
                console.log(`Godzina zakończenia: ${lesson.end_time}`);
                console.log(`Przedmiot: ${lesson.subject_name}`);
                console.log(`Wykładowca: ${lesson.lecturer_name}`);
                console.log(`Grupa: ${lesson.group_name}`);
                console.log('----------------------------');
            });
        }
    })
    .catch(error => {
        console.error('Błąd:', error);
    });
}

document.getElementById('clear-filters').addEventListener('click', function() {
    ['wydzial', 'typ_studiow', 'semestr', 'wykladowca', 'forma_przedmiotu', 'przedmiot', 'sala', 'grupa', 'numer_albumu']
        .forEach(id => document.getElementById(id).value = '');
});

// Inicjalizacja i przyciski nawigacyjne
document.getElementById('today').addEventListener('click', () => {
    currentDate = new Date();
    updateTodayButton();
    renderCalendar();
});

document.getElementById('prev').addEventListener('click', () => navigateCalendar(-1));
document.getElementById('next').addEventListener('click', () => navigateCalendar(1));

document.getElementById('day').addEventListener('click', () => changeView('day'));
document.getElementById('week').addEventListener('click', () => changeView('week'));
document.getElementById('month').addEventListener('click', () => changeView('month'));
document.getElementById('semester').addEventListener('click', () => changeView('semester'));

// Inicjalizacja
updateTodayButton();
renderCalendar();

//autouzupełnianie dla wykładowcy
const lecturerInput = document.getElementById('lecturer-filter');
const suggestionsContainer = document.getElementById('lecturer-suggestions');

lecturerInput.addEventListener('input', () => {
    const query = lecturerInput.value.trim();

    if (query.length < 2) {
        suggestionsContainer.innerHTML = '';
        return;
    }

    fetch('http://localhost/autocomplete.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ autocompleteQuery: query })
    })
        .then(response => response.json())
        .then(data => {
            displaySuggestions(data);
        })
        .catch(error => {
            console.error('Błąd podczas autouzupełniania:', error);
        });
});

function displaySuggestions(lecturers) {
    suggestionsContainer.innerHTML = '';

    if (lecturers.length === 0) {
        suggestionsContainer.innerHTML = '<div>Brak wyników</div>';
        return;
    }

    lecturers.forEach(lecturer => {
        const suggestion = document.createElement('div');
        suggestion.textContent = lecturer.lecturer_name;
        suggestion.addEventListener('click', () => {
            lecturerInput.value = lecturer.lecturer_name;
            suggestionsContainer.innerHTML = ''; 
        });
        suggestionsContainer.appendChild(suggestion);
    });
}
