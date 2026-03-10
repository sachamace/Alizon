// daltonien.js — gestion du mode daltonien avec dropdown
const modes = ['deuteranopie', 'protanopie', 'tritanopie'];

function appliquerMode(mode) {
    modes.forEach(m => document.body.classList.remove(m));

    // maj état actif des options
    document.querySelectorAll('.dal-option').forEach(btn => {
        btn.classList.toggle('actif', btn.dataset.mode === mode);
    });

    const trigger = document.getElementById('dal-trigger');
    if (mode && modes.includes(mode)) {
        document.body.classList.add(mode);
        localStorage.setItem('daltonien', mode);
        if (trigger) trigger.classList.add('dal-actif');
    } else {
        localStorage.removeItem('daltonien');
        if (trigger) trigger.classList.remove('dal-actif');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // appliquer le mode stocké
    const modeStocke = localStorage.getItem('daltonien');
    if (modeStocke) appliquerMode(modeStocke);

    const trigger  = document.getElementById('dal-trigger');
    const dropdown = document.getElementById('dal-dropdown');

    // ouvrir/fermer le dropdown
    if (trigger && dropdown) {
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            dropdown.classList.toggle('ouvert');
        });

        // fermer en cliquant ailleurs
        document.addEventListener('click', () => {
            dropdown.classList.remove('ouvert');
        });

        dropdown.addEventListener('click', (e) => e.stopPropagation());
    }

    // boutons de mode
    document.querySelectorAll('.dal-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            if (document.body.classList.contains(mode)) {
                appliquerMode(null);
            } else {
                appliquerMode(mode);
            }
            if (dropdown) dropdown.classList.remove('ouvert');
        });
    });

    // reset
    const reset = document.getElementById('dal-reset');
    if (reset) {
        reset.addEventListener('click', () => {
            appliquerMode(null);
            if (dropdown) dropdown.classList.remove('ouvert');
        });
    }
});