let nombreEssais = 0;
const MAX_ESSAIS = 5;
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
    const btnValider = document.querySelector('.btn-valider');
    const inputCode = document.getElementById('code_2fa');
    const divErreur = document.getElementById('erreur-msg-js');
    if (nombreEssais >= MAX_ESSAIS) {
        return;
    }

    nombreEssais++;
    const essaisRestants = MAX_ESSAIS - nombreEssais;
    const codeSaisi = inputCode.value;
    //Vérification avec la regex
    const regexA2F = /^\d{6}$/;
    
    if (!regexA2F.test(codeSaisi)) {
        // Si le test échoue, on affiche une erreur et on arrête tout (return)
        divErreur.innerText = "Veuillez entrer un code valide de 6 chiffres. Pas de lettre ou de caractères spéciaux";
    }

    try {
        const response = await fetch('/back_office/index.php?page=activerA2f', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `code=${encodeURIComponent(codeSaisi)}`
        });
        
        const result = await response.json();

        if (result.success === true) {                                                                                                    
            window.location.href = "/back_office/index.php?page=profil&type=consulter";
        } else {
            // Affichage de l'erreur renvoyée par PHP
            gererErreur(divErreur, inputCode, btnValider, `${result.message} Il vous reste ${essaisRestants} essai(s).`);
        }
    } catch (error) {
        console.error("Erreur lors de l'envoi :", error);
    }
}
function gererErreur(divErreur, inputCode, btnValider, message) {
    if (nombreEssais >= MAX_ESSAIS) {
        divErreur.innerText = "Trop de tentatives échouées. Veuillez recharger la page ou réessayer plus tard.";
        inputCode.disabled = true; 
        btnValider.disabled = true; 
    } else {
        divErreur.innerText = message;
        inputCode.value = ""; 
        inputCode.focus(); 
    }
}