document.addEventListener("DOMContentLoaded", () => {
    // On cible la div préparée en PHP
    const qrcodeElement = document.getElementById("qrcode");
    
    if (qrcodeElement) {
        // On récupère l'URI stockée dans l'attribut HTML "data-uri"
        const otpUri = qrcodeElement.getAttribute("data-uri");

        // Si l'URI existe, on génère le QR Code
        if (otpUri) {
            new QRCode(qrcodeElement, {
                text: otpUri,
                width: 250,
                height: 250,
                colorDark : "#000000",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.M
                
            });
        }
    }
});

// Exemple d'envoi de code 2FA vers ton script PHP
async function valider() {
    const codeSaisi = document.getElementById('code_2fa').value; // Le code tapé par l'utilisateur
    try {
        const response = await fetch('activerA2f.php',
        {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `code=${encodeURIComponent(codeSaisi)}` // On envoie le code au serveur
        });
        console.log("Requête envoyée !");
        
        const result = await response.json();

        if (result.success === true) {                                                                                                    
            window.location.href = "/back_office/index.php?page=profil&type=consulter";
        } else {
            document.getElementById('erreur-msg-js').innerText = result.message;
        }
    } catch (error) {
        console.error("Erreur lors de l'envoi :", error);
    }

}