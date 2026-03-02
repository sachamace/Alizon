// Exemple d'envoi de code 2FA vers ton script PHP
async function verifierCode(code) {
    const response = await fetch('../html/seconnecter.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },      
        body: `code=${code}`
    });

    const resultat = await response.json();
    if (resultat.success) {
        console.log("Authentification réussie !");
    }
}