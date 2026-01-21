var popup = document.getElementById("monPopup");
var imagePopup = document.getElementById("imgDansPopup");
var lien = document.querySelector(".lien-image");
var boutonFermer = document.getElementsByClassName("fermer")[0];

// 2. Quand on clique sur le lien <a>
lien.addEventListener('click', function(event) {
    event.preventDefault(); // Empêche le lien d'ouvrir une nouvelle page
    popup.style.display = "block"; // Affiche le popup
    imagePopup.src = this.href; // Récupère l'URL du lien pour la mettre dans le popup
});

// 3. Quand on clique sur la croix (X)
boutonFermer.onclick = function() {
    popup.style.display = "none";
}

// (Optionnel) Fermer si on clique en dehors de l'image (sur le fond noir)
window.onclick = function(event) {
    if (event.target == popup) {
        popup.style.display = "none";
    }
}