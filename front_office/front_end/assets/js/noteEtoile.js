const starsContainer = document.querySelector('.stars-rating');
const noteInput = document.getElementById('noteInput');
const ratingText = document.getElementById('ratingText');
const form = document.getElementById('avisForm');

let selectedRating = 0;

const ratingLabels = {
    0.5: "Très mauvais",
    1: "Très décevant",
    1.5: "Très décevant",
    2: "Décevant",
    2.5: "Passable",
    3: "Moyen",
    3.5: "Correct",
    4: "Bien",
    4.5: "Très bien",
    5: "Excellent"
};

// Créer 5 étoiles avec deux zones (gauche/droite) chacune
function createStars() {
    // Garder le texte rating
    const existingText = starsContainer.querySelector('.rating-text');
    starsContainer.innerHTML = '';
    
    for (let i = 1; i <= 5; i++) {
        const starWrapper = document.createElement('div');
        starWrapper.className = 'star-wrapper';
        starWrapper.dataset.value = i;
        
        starWrapper.innerHTML = `
            <span class="star-half star-left" data-value="${i - 0.5}">★</span>
            <span class="star-half star-right" data-value="${i}">★</span>
        `;
        
        starsContainer.appendChild(starWrapper);
    }
    
    // Réajouter le texte
    if (existingText) {
        starsContainer.appendChild(existingText);
    }
}

createStars();

const starHalves = document.querySelectorAll('.star-half');

function updateStars(rating, isHover = false) {
    starHalves.forEach(half => {
        const value = parseFloat(half.dataset.value);
        half.classList.remove('active', 'hover');
        
        if (value <= rating) {
            half.classList.add(isHover ? 'hover' : 'active');
        }
    });
}

starHalves.forEach(half => {
    half.addEventListener('mouseenter', function() {
        const rating = parseFloat(this.dataset.value);
        updateStars(rating, true);
        ratingText.textContent = ratingLabels[rating];
    });
    
    half.addEventListener('click', function() {
        selectedRating = parseFloat(this.dataset.value);
        noteInput.value = selectedRating;
        updateStars(selectedRating);
        ratingText.textContent = ratingLabels[selectedRating];
    });
});

starsContainer.addEventListener('mouseleave', function() {
    if (selectedRating > 0) {
        updateStars(selectedRating);
        ratingText.textContent = ratingLabels[selectedRating];
    } else {
        updateStars(0);
        ratingText.textContent = "Sélectionnez une note";
    }
});

form.addEventListener('submit', function(e) {
    if (selectedRating === 0) {
        e.preventDefault();
        alert('Veuillez sélectionner une note avant d\'envoyer votre avis.');
    }
});