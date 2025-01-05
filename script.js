// Funkcje związane z filtrowaniem
function Filters() {
    const filterOptions = document.querySelector('.filter-options');
    filterOptions.style.display = filterOptions.style.display === 'block' ? 'none' : 'block';
}

function Filters2() {
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
document.getElementById('search').addEventListener('click', function() {
    const filters = ['wydzial', 'typ_studiow', 'semestr', 'wykladowca', 'forma_przedmiotu', 'przedmiot', 'sala', 'grupa', 'numer_albumu'];
    console.log("Filtry:");
    filters.forEach(id => {
        const value = document.getElementById(id).value;
        console.log(id, ":", value);
    });
});

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
