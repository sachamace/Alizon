// daltonien.js — gestion du mode daltonien (3 cercles)
const modes = ['deuteranopie', 'protanopie', 'tritanopie'];

function appliquerMode(mode) {
    modes.forEach(m => document.body.classList.remove(m));

    document.querySelectorAll('.dal-btn').forEach(btn => {
        btn.classList.toggle('actif', btn.dataset.mode === mode);
    });

    if (mode && modes.includes(mode)) {
        document.body.classList.add(mode);
        localStorage.setItem('daltonien', mode);
    } else {
        localStorage.removeItem('daltonien');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    const modeStocke = localStorage.getItem('daltonien');
    if (modeStocke) appliquerMode(modeStocke);

    document.querySelectorAll('.dal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            if (document.body.classList.contains(mode)) {
                appliquerMode(null);
            } else {
                appliquerMode(mode);
            }
        });
    });
});