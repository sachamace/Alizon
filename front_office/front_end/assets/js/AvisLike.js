// bouton like dislike avis
const boutonsAvislike = document.querySelectorAll('.btn-vote');

boutonsAvislike.forEach(btn => {
    btn.addEventListener('click', function() {
        const idAuteur = this.getAttribute('data-id-avis'); 
        const urlParams = new URLSearchParams(window.location.search);
        const idProduit = urlParams.get('article');
        
        // On vérifie sur quel bouton on a cliqué
        const isUpvote = this.classList.contains('btn-upvote');
        const valeurVote = isUpvote ? 1 : -1;

        // les données à envoyer au PHP
        const formData = new FormData();
        formData.append('action', 'voter_avis');
        formData.append('id_auteur', idAuteur);
        formData.append('id_produit', idProduit);
        formData.append('valeur_vote', valeurVote);

        fetch(window.location.href, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json()) 
        .then(data => {
            if (data.error === 'non_connecte') {
                window.location.href = 'seconnecter.php';
                return;
            }

            // Si le vote s'est bien passé
            if (data.success) {
                // 4. On met à jour l'affichage visuel
                const conteneurVotes = this.closest('.avis-votes');
                const btnUp = conteneurVotes.querySelector('.btn-upvote');
                const btnDown = conteneurVotes.querySelector('.btn-downvote');

                // Mise à jour des chiffres
                btnUp.querySelector('.vote-count').textContent = data.total_positif;
                btnDown.querySelector('.vote-count').textContent = data.total_negatif;

                // Réinitialisation des couleurs
                btnUp.classList.remove('active');
                btnDown.classList.remove('active');

                // On applique la couleur sur le bouton cliqué (si ce n'était pas une annulation)
                if (data.mon_vote === 1) {
                    btnUp.classList.add('active');
                } else if (data.mon_vote === -1) {
                    btnDown.classList.add('active');
                }
            } else if (data.error) {
                console.error("Erreur côté serveur :", data.error);
            }
        })
        .catch(error => {
            console.error('Erreur réseau ou JS :', error);
        });
    });
});