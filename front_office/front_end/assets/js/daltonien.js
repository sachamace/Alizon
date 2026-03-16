// daltonien.js — via RGBlind

const rgblindMap = {
    deuteranopie: 'deuteranopia',
    protanopie:   'protanopia',
    tritanopie:   'tritanopia',
};

function appliquerMode(mode) {
    if (typeof rgblind !== 'undefined') rgblind.reset();

    document.querySelectorAll('.dal-option').forEach(btn => {
        btn.style.fontWeight = btn.dataset.mode === mode ? '700' : '400';
        btn.style.background = btn.dataset.mode === mode ? '#f0f0f0' : 'none';
    });

    const trigger = document.getElementById('dal-trigger');

    if (mode && rgblindMap[mode]) {
        if (typeof rgblind !== 'undefined') rgblind[rgblindMap[mode]]();
        localStorage.setItem('daltonien', mode);
        if (trigger) trigger.style.borderColor = '#666';
    } else {
        localStorage.removeItem('daltonien');
        if (trigger) trigger.style.borderColor = '#ccc';
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
            dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
        });
        document.addEventListener('click', () => { if (dropdown) dropdown.style.display = 'none'; });
        dropdown.addEventListener('click', e => e.stopPropagation());
    }

    document.querySelectorAll('.dal-option').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            const actif = btn.style.fontWeight === '700';
            appliquerMode(actif ? null : mode);
            if (dropdown) dropdown.style.display = 'none';
        });
    });

    const reset = document.getElementById('dal-reset');
    if (reset) reset.addEventListener('click', () => {
        appliquerMode(null);
        if (dropdown) dropdown.style.display = 'none';
    });
});