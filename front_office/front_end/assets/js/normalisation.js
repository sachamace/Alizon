document.addEventListener("DOMContentLoaded", function() {
    
    // 1. Sélection des éléments (Doit se faire ICI, une fois la page chargée)
    var myInput = document.getElementById("mdp");
    var confirmInput = document.getElementById("confirm");
    
    // Vérification de sécurité : si l'input n'existe pas, on arrête tout pour éviter l'erreur
    if (!myInput || !confirmInput) {
        console.error("Erreur : Les champs avec id='mdp' ou 'confirm' sont introuvables.");
        return; 
    }

    var messageBox = document.getElementById("message-box");
    var matchMessage = document.getElementById("match-message");
    var length = document.getElementById("length");
    var capital = document.getElementById("uppercase");
    var letter = document.getElementById("lowercase");
    var special = document.getElementById("special");

    // 2. Vos événements
    myInput.onfocus = function() {
        messageBox.style.display = "block";
    }

    myInput.onkeyup = function() {
        // Minuscules
        var lowerCaseLetters = /[a-z]/g;
        if(myInput.value.match(lowerCaseLetters)) {  
            letter.classList.remove("invalid");
            letter.classList.add("valid");
        } else {
            letter.classList.remove("valid");
            letter.classList.add("invalid");
        }
        
        // Majuscules
        var upperCaseLetters = /[A-Z]/g;
        if(myInput.value.match(upperCaseLetters)) {  
            capital.classList.remove("invalid");
            capital.classList.add("valid");
        } else {
            capital.classList.remove("valid");
            capital.classList.add("invalid");
        }

        // Caractères spéciaux
        var specialChars = /[\W_]/g;
        if(myInput.value.match(specialChars)) {  
            special.classList.remove("invalid");
            special.classList.add("valid");
        } else {
            special.classList.remove("valid");
            special.classList.add("invalid");
        }

        // Longueur
        if(myInput.value.length >= 12) {
            length.classList.remove("invalid");
            length.classList.add("valid");
        } else {
            length.classList.remove("valid");
            length.classList.add("invalid");
        }
        
        checkMatch();
    }

    confirmInput.onkeyup = checkMatch;

    function checkMatch() {
        if(!matchMessage) return; // Sécurité si l'élément n'existe pas

        if (confirmInput.value.length > 0) {
            matchMessage.style.display = "block";
            if (myInput.value === confirmInput.value) {
                matchMessage.style.color = "#2ecc71";
                matchMessage.innerHTML = "✔ Les mots de passe correspondent";
            } else {
                matchMessage.style.color = "#ff4d4d";
                matchMessage.innerHTML = "✖ Les mots de passe ne correspondent pas";
            }
        } else {
            matchMessage.style.display = "none";
        }
    }
});