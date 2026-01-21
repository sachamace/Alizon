document.addEventListener('DOMContentLoaded', function() {
    var popup = document.getElementById("monPopup");
    var imagePopup = document.getElementById("imgDansPopup");
    var liens = document.querySelectorAll(".lien-image");
    var boutonFermer = document.querySelector(".fermer");

    // Pour chaque lien trouvé (au cas où il y en a plusieurs)
    liens.forEach(function(lien) {
        lien.addEventListener('click', function(event) {
            event.preventDefault(); // EMPECHE DE CHANGER DE PAGE
            
            // On récupère le chemin de l'image dans l'attribut data-image
            var chemin = this.getAttribute("data-image");
            
            // On l'injecte dans l'image du popup
            imagePopup.src = chemin;
            
            // On affiche le popup en mode "flex" pour le centrage
            popup.style.display = "flex";
        });
    });

    // Fermer quand on clique sur la croix
    if(boutonFermer) {
        boutonFermer.onclick = function() {
            popup.style.display = "none";
            imagePopup.src = ""; // On vide la source par sécurité
        };
    }

    // Fermer quand on clique sur le fond noir
    window.onclick = function(event) {
        if (event.target == popup) {
            popup.style.display = "none";
            imagePopup.src = "";
        }
    };
});