const radioCarte = document.getElementById("radio-carte");
const formCarte = document.getElementById("form-carte");

const radioPaypal = document.getElementById("radio-paypal");
const formPaypal = document.getElementById("form-paypal");

const carteInput = document.getElementById('carte');

let carteVisible = false;
let paypalVisible = false;

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
});

// Formatage du numéro de carte
carteInput.addEventListener('input', function () {
    let valeur = this.value.replace(/\s+/g, '').replace(/\D/g, '');
    this.value = valeur.match(/.{1,4}/g)?.join(' ') || '';
});