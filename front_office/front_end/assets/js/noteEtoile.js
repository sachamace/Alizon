
const stars = document.querySelectorAll('.star');
const noteInput = document.getElementById('noteInput');
const ratingText = document.getElementById('ratingText');
const form = document.getElementById('avisForm');

let selectedRating = 0;

const ratingLabels = {
    1: "⭐ Très décevant",
    2: "⭐⭐ Décevant",
    3: "⭐⭐⭐ Moyen",
    4: "⭐⭐⭐⭐ Bien",
    5: "⭐⭐⭐⭐⭐ Excellent"
};

function updateStars(rating, isHover = false) {
    stars.forEach((star, index) => {
        star.classList.remove('active', 'hover');
        if (index < rating) {
            star.classList.add(isHover ? 'hover' : 'active');
        }
    });
}

stars.forEach(star => {
    star.addEventListener('mouseenter', function() {
        const rating = parseInt(this.dataset.value);
        updateStars(rating, true);
        ratingText.textContent = ratingLabels[rating];
    });
});

document.querySelector('.stars-rating').addEventListener('mouseleave', function() {
    if (selectedRating > 0) {
        updateStars(selectedRating);
        ratingText.textContent = ratingLabels[selectedRating];
    } else {
        updateStars(0);
        ratingText.textContent = "Sélectionnez une note";
    }
});

stars.forEach(star => {
    star.addEventListener('click', function() {
        selectedRating = parseInt(this.dataset.value);
        noteInput.value = selectedRating;
        updateStars(selectedRating);
        ratingText.textContent = ratingLabels[selectedRating];
    });
});

form.addEventListener('submit', function(e) {
    if (selectedRating === 0) {
        e.preventDefault();
        alert('Veuillez sélectionner une note avant d\'envoyer votre avis.');
    }
});
