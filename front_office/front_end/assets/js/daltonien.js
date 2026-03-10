// daltonien.js — gestion du mode daltonien
// Stocke le mode choisi dans localStorage et applique la classe sur le body

const modes = ['deuteranopie', 'protanopie', 'tritanopie'];

function appliquerMode(mode) {
    // on retire tous les modes existants
    modes.forEach(m => document.body.classList.remove(m));

    // on met à jour l'état actif des boutons
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

// au chargement de la page, on relit le localStorage
document.addEventListener('DOMContentLoaded', () => {
    const modeStocke = localStorage.getItem('daltonien');
    if (modeStocke) appliquerMode(modeStocke);

    // on branche les boutons
    document.querySelectorAll('.dal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            // clic sur le mode déjà actif → on désactive
            if (document.body.classList.contains(mode)) {
                appliquerMode(null);
            } else {
                appliquerMode(mode);
            }
        });
    });
});