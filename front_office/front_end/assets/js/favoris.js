// --- GESTION DES FAVORIS (LISTE DE SOUHAITS) ---
const btnFavoris = document.querySelector('.btn-favoris');

if (btnFavoris) {
    btnFavoris.addEventListener('click', function(e) {
        e.preventDefault(); // Empêche un comportement par défaut si jamais c'était dans un formulaire
        
        const idProduit = this.getAttribute('data-id-produit');
        const svgPath = this.querySelector('path');
        const svg = this.querySelector('svg');

        // Préparation des données à envoyer en POST
        const formData = new FormData();
        formData.append('action', 'toggle_favori');
        formData.append('id_produit', idProduit);

        // Appel AJAX vers la page courante
        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // On change la couleur en fonction du retour du serveur
                if (data.etat === 'ajoute') {
                    svg.setAttribute('fill', 'red');
                    svg.setAttribute('stroke', 'red');
                } else if (data.etat === 'retire') {
                    svg.setAttribute('fill', '#e8e8e8'); // Couleur de fond vide
                    svg.setAttribute('stroke', 'black'); // Bordure noire
                }
            } else if (data.redirect) {
                // Si l'utilisateur n'est pas connecté, on le redirige
                window.location.href = data.redirect;
            } else {
                console.error("Erreur:", data.error);
                alert("Une erreur est survenue lors de l'ajout aux favoris.");
            }
        })
        .catch(error => {
            console.error('Erreur Fetch:', error);
        });
    });
}