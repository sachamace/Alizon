// daltonien.js — simulation daltonisme via filtres SVG (même technique que Chrome DevTools)
// Aucune dépendance externe, fonctionne sur toutes les couleurs ET les images

const MODES = {
    deuteranopie: 'url(#filter-deuteranopie)',
    protanopie:   'url(#filter-protanopie)',
    tritanopie:   'url(#filter-tritanopie)',
};

// Injection des filtres SVG dans le DOM
function injecterFiltresSVG() {
    if (document.getElementById('dal-svg-filters')) return;

    const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
    svg.setAttribute('id', 'dal-svg-filters');
    svg.setAttribute('style', 'position:absolute;width:0;height:0;overflow:hidden;');
    svg.innerHTML = `
        <defs>
            <!-- Deuteranopie : insensibilite au vert -->
            <filter id="filter-deuteranopie">
                <feColorMatrix type="matrix" values="
                    0.625 0.375 0     0 0
                    0.7   0.3   0     0 0
                    0     0.3   0.7   0 0
                    0     0     0     1 0"/>
            </filter>
            <!-- Protanopie : insensibilite au rouge -->
            <filter id="filter-protanopie">
                <feColorMatrix type="matrix" values="
                    0.567 0.433 0     0 0
                    0.558 0.442 0     0 0
                    0     0.242 0.758 0 0
                    0     0     0     1 0"/>
            </filter>
            <!-- Tritanopie : insensibilite au bleu -->
            <filter id="filter-tritanopie">
                <feColorMatrix type="matrix" values="
                    0.95  0.05  0     0 0
                    0     0.433 0.567 0 0
                    0     0.475 0.525 0 0
                    0     0     0     1 0"/>
            </filter>
        </defs>
    `;
    document.body.appendChild(svg);
}

function appliquerMode(mode) {
    document.body.style.filter = '';

    document.querySelectorAll('.dal-btn, .dal-option').forEach(btn => {
        btn.classList.toggle('actif', btn.dataset.mode === mode);
    });

    const trigger = document.getElementById('dal-trigger');

    if (mode && MODES[mode]) {
        document.body.style.filter = MODES[mode];
        localStorage.setItem('daltonien', mode);
        if (trigger) trigger.classList.add('dal-actif');
    } else {
        localStorage.removeItem('daltonien');
        if (trigger) trigger.classList.remove('dal-actif');
    }
}

document.addEventListener('DOMContentLoaded', () => {
    injecterFiltresSVG();

    // Appliquer le mode stocke au chargement
    const modeStocke = localStorage.getItem('daltonien');
    if (modeStocke) appliquerMode(modeStocke);

    // 3 boutons cercles
    document.querySelectorAll('.dal-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const mode = btn.dataset.mode;
            const actif = document.body.style.filter.includes('filter-' + mode);
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
            const actif = document.body.style.filter.includes('filter-' + mode);
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