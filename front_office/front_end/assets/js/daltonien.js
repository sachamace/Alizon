// daltonien.js
const modes = ['deuteranopie', 'protanopie', 'tritanopie'];

function appliquerMode(mode) {
    modes.forEach(m => document.body.classList.remove(m));
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
    const modeStocke = localStorage.getItem('daltonien');
    if (modeStocke) appliquerMode(modeStocke);

    const trigger  = document.getElementById('dal-trigger');
    const dropdown = document.getElementById('dal-dropdown');

    if (trigger && dropdown) {
        trigger.addEventListener('click', e => {
            e.stopPropagation();
            dropdown.classList.toggle('ouvert');
        });
        document.addEventListener('click', () => dropdown.classList.remove('ouvert'));
        dropdown.addEventListener('click', e => e.stopPropagation());
    }

    document.querySelectorAll('.dal-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            appliquerMode(document.body.classList.contains(mode) ? null : mode);
            if (dropdown) dropdown.classList.remove('ouvert');
        });
    });

    const reset = document.getElementById('dal-reset');
    if (reset) reset.addEventListener('click', () => {
        appliquerMode(null);
        if (dropdown) dropdown.classList.remove('ouvert');
    });
});