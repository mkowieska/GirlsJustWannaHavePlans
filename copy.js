document.getElementById("copy_table").addEventListener("click", function () {
    const calendarTableHTML = document.getElementById("calendar-table").innerHTML;
    let copiedTables = JSON.parse(localStorage.getItem("calendarTableCopies")) || [];
    copiedTables.push(calendarTableHTML);
    localStorage.setItem("calendarTableCopies", JSON.stringify(copiedTables));
    renderCopiedTables(copiedTables);
});

function renderCopiedTables(tables) {
    const copyCalendarContainer = document.getElementById("copy_calendar_table");
    copyCalendarContainer.innerHTML = "";
    tables.forEach((tableHTML, index) => {
        const tableWrapper = document.createElement("div");
        tableWrapper.classList.add("copied-table-wrapper");
        tableWrapper.innerHTML = `
            <h3>Plan ${index + 1}</h3>
            <div class="copied-table">${tableHTML}</div>
        `;
        copyCalendarContainer.appendChild(tableWrapper);
    });
}

document.addEventListener("DOMContentLoaded", function () {
    localStorage.removeItem("calendarTableCopies");
    renderCopiedTables([]);
});
