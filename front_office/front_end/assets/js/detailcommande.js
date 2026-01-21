
document.addEventListener('click', function (event) {
    // 1. Vérifie si on a cliqué sur le bouton "Voir la boite"
    if (event.target.classList.contains('btn-boite')) {
        const popup = document.getElementById('monPopup');
        const img = document.getElementById('imgDansPopup');
        const source = event.target.getAttribute('data-image');

        if (source) {
            img.src = source;
            popup.style.display = 'flex';
        }
    }

    // 2. Vérifie si on a cliqué sur la croix pour fermer
    if (event.target.classList.contains('fermer-popup')) {
        document.getElementById('monPopup').style.display = 'none';
    }

    // 3. Vérifie si on a cliqué sur le fond noir pour fermer
    if (event.target.classList.contains('popup-overlay')) {
        event.target.style.display = 'none';
    }
});
