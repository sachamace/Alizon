document.addEventListener('DOMContentLoaded', () => {
    // 1. On sélectionne tous les boutons "Répondre"
    const boutonsrepondre = document.querySelectorAll('.toggle-reponse');
    const boutonsviewreponse = document.querySelectorAll('.toggle-view-reponse');

    boutonsrepondre.forEach(btn => {
        btn.addEventListener('click', function() {

            const conteneurAvis = btn.closest('.avis-item');
            const formulaire = conteneurAvis.querySelector('.reponse-vendeur');

            if (formulaire) {
                // On bascule la classe
                formulaire.classList.toggle('is-visible');

                // On change le texte du bouton
                if (formulaire.classList.contains('is-visible')) {
                    btn.textContent = "Annuler la réponse";
                } else {
                    btn.textContent = "Répondre au client";
                }
            }
        });
    });

    boutonsviewreponse.forEach(btn => {
        btn.addEventListener('click', function() {

            const conteneurAvis = btn.closest('.avis-item');
            
            const reponse = conteneurAvis.querySelector('.view-reponse');

            if (reponse) {
                reponse.classList.toggle('is-visible');
            }
        });
    });
});