document.addEventListener('DOMContentLoaded', function() {
    const popup = document.getElementById("monPopup");
    const imgPopup = document.getElementById("imgDansPopup");
    const lien = document.querySelector(".lien-image");
    const croix = document.querySelector(".fermer");

    if (lien) {
        lien.addEventListener('click', function(e) {
            e.preventDefault(); // Empêche de remonter en haut de page
            const sourceImage = this.getAttribute('data-image');
            imgPopup.src = sourceImage;
            popup.style.display = "flex"; // Affiche le popup
        });
    }

    // Fermer avec la croix
    if (croix) {
        croix.onclick = function() {
            popup.style.display = "none";
        };
    }

    // Fermer en cliquant sur le fond noir
    window.onclick = function(e) {
        if (e.target == popup) {
            popup.style.display = "none";
        }
    };
});