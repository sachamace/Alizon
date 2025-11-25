const radioCarte = document.getElementById("radio-carte");
const formCarte = document.getElementById("form-carte");

const radioPaypal = document.getElementById("radio-paypal");
const formPaypal = document.getElementById("form-paypal");

const carteInput = document.getElementById('carte');

const btnPayer = document.querySelector(".payer-btn");

let carteVisible = false;
let paypalVisible = false;

btnPayer.disabled = true; //on désactive le bouton

radioCarte.addEventListener("click", () => {
    if (carteVisible) {
        radioCarte.checked = false;
        formCarte.classList.add("hidden");
        carteVisible = false;
    } else {
        formCarte.classList.remove("hidden");
        carteVisible = true;
        radioPaypal.checked = false;
        formPaypal.classList.add("hidden");
        paypalVisible = false;
    }

    //Si un des 2 forms sont visibles
    if (carteVisible || paypalVisible) {
        btnPayer.removeAttribute('disabled');  //On enleve l'attribut disabled et on active le bouton payer
    } else {
        btnPayer.disabled = true;   //Sinon on laisse l'attribut
    }
});

radioPaypal.addEventListener("click", () => {
    if (paypalVisible) {
        radioPaypal.checked = false;
        formPaypal.classList.add("hidden");
        paypalVisible = false;
    } else {
        formPaypal.classList.remove("hidden");
        paypalVisible = true;
        radioCarte.checked = false;
        formCarte.classList.add("hidden");
        carteVisible = false;
    }

    if (carteVisible || paypalVisible) {
        btnPayer.removeAttribute('disabled');
    } else {
        btnPayer.disabled = true;
    }
});

// Formatage du numéro de carte
carteInput.addEventListener('input', function () {
    let valeur = this.value.replace(/\s+/g, '').replace(/\D/g, '');
    this.value = valeur.match(/.{1,4}/g)?.join(' ') || '';
});