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

async function valider() {
    const inputCode = document.getElementById('code_2fa');
    const codeSaisi = inputCode.value;

    try {
        const response = await fetch('activerA2f.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `code=${encodeURIComponent(codeSaisi)}`
        });
        
        const result = await response.json();

        if (result.success === true) {                                                                                                    
            window.location.href = "/front_office/front_end/html/consulterProfilClient.php";
        } else {
            // Affichage de l'erreur renvoyée par PHP
            inputCode.value = ""; 
            inputCode.focus(); 
        }
    } catch (error) {
        console.error("Erreur lors de l'envoi :", error);
    }
}