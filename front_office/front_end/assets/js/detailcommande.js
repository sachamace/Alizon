window.onload = function() {
    const popup = document.getElementById("monPopup");
    const imgDansPopup = document.getElementById("imgDansPopup");
    const boutonFermer = document.querySelector(".fermer-popup");

    // On utilise document.body.addEventListener pour que ça marche 
    // même si le lien est généré dynamiquement
    document.body.addEventListener('click', function(e) {
        if (e.target.classList.contains('btn-voir-image')) {
            e.preventDefault();
            const urlImage = e.target.getAttribute('data-image');
            console.log("Clic détecté, image : " + urlImage); // Pour vérifier dans la console (F12)
            
            imgDansPopup.src = urlImage;
            popup.style.display = "flex";
        }
    });

    // Fermer le popup
    if(boutonFermer) {
        boutonFermer.onclick = function() {
            popup.style.display = "none";
            imgDansPopup.src = "";
        };
    }

    // Fermer si clic sur le fond noir
    popup.onclick = function(e) {
        if (e.target === popup) {
            popup.style.display = "none";
        }
    };
};