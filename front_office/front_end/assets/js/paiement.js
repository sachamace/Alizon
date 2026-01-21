const radioCarte = document.getElementById("radio-carte");
const formCarte = document.getElementById("form-carte");

const carteInput = document.getElementById('carte');

const btnPayer = document.querySelector(".payer-btn");

let carteVisible = false;

btnPayer.disabled = true; //on désactive le bouton test 

radioCarte.addEventListener("click", () => {
    if (carteVisible) {
        radioCarte.checked = false;
        formCarte.classList.add("hidden");
        carteVisible = false;
    } else {
        formCarte.classList.remove("hidden");
        carteVisible = true;
    }

    //Si le formulaire de carte est visible
    if (carteVisible) {
        btnPayer.removeAttribute('disabled');  //On enleve l'attribut disabled et on active le bouton payer
    } else {
        btnPayer.disabled = true;   //Sinon on laisse l'attribut
    }
});

// Formatage du numéro de carte
carteInput.addEventListener('input', function () {
    let valeur = this.value.replace(/\s+/g, '').replace(/\D/g, '');
    this.value = valeur.match(/.{1,4}/g)?.join(' ') || '';
});