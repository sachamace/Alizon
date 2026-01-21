var popup = document.getElementById("monPopup");
var imagePopup = document.getElementById("imgDansPopup");
var lien = document.querySelector(".lien-image");
var boutonFermer = document.querySelector(".fermer");

// On vérifie si le lien existe bien sur la page avant d'ajouter l'écouteur
if (lien) {
    lien.addEventListener('click', function(event) {
        event.preventDefault(); 
        
        // On récupère le lien de l'image stocké dans le href
        var cheminImage = this.getAttribute("href"); 
        
        if (cheminImage) {
            imagePopup.src = cheminImage;
            popup.style.display = "flex"; // Utilise flex pour le centrage
        }
    });
}

// Fermer avec la croix
if (boutonFermer) {
    boutonFermer.onclick = function() {
        popup.style.display = "none";
    }
}

// Fermer en cliquant sur le fond noir
window.onclick = function(event) {
    if (event.target == popup) {
        popup.style.display = "none";
    }
}