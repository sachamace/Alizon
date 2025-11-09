document.addEventListener("DOMContentLoaded", () => { // Code qui permet de bloquer la date du jour de naissance toujours au passé
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayStr = `${yyyy}-${mm}-${dd}`;

    document.getElementById('date_de_naissance').max = todayStr;
});