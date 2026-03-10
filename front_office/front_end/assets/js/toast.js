// Variable pour gérer le temps et éviter les bugs si on clique très vite
let toastTimeout; 

function afficherToast(message, type = 'succes') {
    const toast = document.getElementById("toast-global");
    
    // 1. On insère ton texte personnalisé
    toast.textContent = message;
    
    // 2. On applique les classes (le type gère la couleur)
    toast.className = `toast show ${type}`;
    
    // 3. On annule le chronomètre précédent si l'utilisateur a cliqué 2 fois de suite
    clearTimeout(toastTimeout);
    
    // 4. On cache le toast après 3 secondes (3000 millisecondes)
    toastTimeout = setTimeout(() => {
        toast.className = toast.className.replace("show", "");
    }, 3000);
}