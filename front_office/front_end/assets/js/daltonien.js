// daltonien.js — mode daltonien via RGBlind (cdn.jsdelivr.net)

const modes = ['deuteranopie', 'protanopie', 'tritanopie'];

// Map entre nos noms et les méthodes RGBlind
const rgblindMap = {
    deuteranopie: 'deuteranopia',
    protanopie:   'protanopia',
    tritanopie:   'tritanopia',
};

function appliquerMode(mode) {
    // Reset d'abord
    if (typeof rgblind !== 'undefined') {
        rgblind.reset();
    }

    // Mettre à jour les boutons
    document.querySelectorAll('.dal-btn, .dal-option').forEach(btn => {
        btn.classList.toggle('actif', btn.dataset.mode === mode);
    });

    const trigger = document.getElementById('dal-trigger');

    if (mode && rgblindMap[mode]) {
        if (typeof rgblind !== 'undefined') {
            rgblind[rgblindMap[mode]]();
        }
        localStorage.setItem('daltonien', mode);
        if (trigger) trigger.classList.add('dal-actif');
    } else {
        localStorage.removeItem('daltonien');
        if (trigger) trigger.classList.remove('dal-actif');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    // Appliquer le mode stocké
    const modeStocke = localStorage.getItem('daltonien');
    if (modeStocke) appliquerMode(modeStocke);

    // 3 boutons cercles
    document.querySelectorAll('.dal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            const actif = btn.classList.contains('actif');
            appliquerMode(actif ? null : mode);
        });
    });

    // Dropdown
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
            const actif = btn.classList.contains('actif');
            appliquerMode(actif ? null : mode);
            if (dropdown) dropdown.classList.remove('ouvert');
        });
    });

    const reset = document.getElementById('dal-reset');
    if (reset) reset.addEventListener('click', () => {
        appliquerMode(null);
        if (dropdown) dropdown.classList.remove('ouvert');
    });
});