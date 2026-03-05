'use strict';

const inputAdresse = document.getElementById('adresse');
const inputLat = document.getElementById('latitude');
const inputLng = document.getElementById('longitude');

const suggestionsList = document.createElement('ul');
suggestionsList.className = 'suggestions-list';
inputAdresse.parentNode.style.position = 'relative';
inputAdresse.parentNode.appendChild(suggestionsList);

let mapValidation = null;
let markerValidation = null;

function geocodageInverse(lat, lng) {
    fetch(`https://nominatim.openstreetmap.org/reverse?lat=${lat}&lon=${lng}&format=json`, {
        headers: { 'Accept-Language': 'fr' }
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.address) {
            const a = data.address;
            const rue = [a.house_number, a.road].filter(Boolean).join(' ');
            const ville = a.city || a.town || a.village || a.municipality || a.hamlet || '';
            const cp = a.postcode || '';
            const lieu = a.hamlet || a.suburb || a.neighbourhood || '';
            const ligneUn = rue || lieu || data.display_name.split(',')[0].trim();
            const parties = [ligneUn, [cp, ville].filter(Boolean).join(' ')].filter(Boolean);
            inputAdresse.value = parties.join(', ');
        }
    })
    .catch(err => console.error('Erreur géocodage inverse :', err));
}

function afficherCarteValidation(lat, lng) {
    const mapDiv = document.getElementById('map-validation');
    const coordsDiv = document.getElementById('coords-display');
    mapDiv.style.display = 'block';
    coordsDiv.style.display = 'block';

    if (!mapValidation) {
        mapValidation = L.map('map-validation').setView([lat, lng], 15);
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© OpenStreetMap'
        }).addTo(mapValidation);

        markerValidation = L.marker([lat, lng], { draggable: true }).addTo(mapValidation);

        markerValidation.on('dragend', function() {
            const pos = markerValidation.getLatLng();
            inputLat.value = pos.lat;
            inputLng.value = pos.lng;
            document.getElementById('lat-display').textContent = pos.lat.toFixed(6);
            document.getElementById('lng-display').textContent = pos.lng.toFixed(6);
            geocodageInverse(pos.lat, pos.lng);
        });

        mapValidation.on('click', function(e) {
            markerValidation.setLatLng(e.latlng);
            inputLat.value = e.latlng.lat;
            inputLng.value = e.latlng.lng;
            document.getElementById('lat-display').textContent = e.latlng.lat.toFixed(6);
            document.getElementById('lng-display').textContent = e.latlng.lng.toFixed(6);
            geocodageInverse(e.latlng.lat, e.latlng.lng);
        });

    } else {
        markerValidation.setLatLng([lat, lng]);
        mapValidation.setView([lat, lng], 15);
        setTimeout(() => mapValidation.invalidateSize(), 100);
    }

    document.getElementById('lat-display').textContent = parseFloat(lat).toFixed(6);
    document.getElementById('lng-display').textContent = parseFloat(lng).toFixed(6);
}

let timer;
inputAdresse.addEventListener('input', () => {
    clearTimeout(timer);
    inputLat.value = '';
    inputLng.value = '';

    if (inputAdresse.value.trim().length < 3) {
        suggestionsList.innerHTML = '';
        return;
    }

    timer = setTimeout(() => {
        const query = encodeURIComponent(inputAdresse.value.trim() + ', France');
        const url = `https://nominatim.openstreetmap.org/search?q=${query}&format=json&limit=5&addressdetails=1&countrycodes=fr`;

        fetch(url, { headers: { 'Accept-Language': 'fr' } })
        .then(r => r.json())
        .then(results => {
            suggestionsList.innerHTML = '';
            results.forEach(r => {
                const a = r.address;
                const rue = [a.house_number, a.road].filter(Boolean).join(' ');
                const ville = a.city || a.town || a.village || a.municipality || a.hamlet || '';
                const cp = a.postcode || '';
                const lieu = a.hamlet || a.suburb || a.neighbourhood || '';
                const ligneUn = rue || lieu || r.display_name.split(',')[0].trim();
                const parties = [ligneUn, [cp, ville].filter(Boolean).join(' ')].filter(s => s && s.trim() !== '');
                const adresseFormatee = parties.join(', ');

                const li = document.createElement('li');
                li.innerHTML = `
                    <div>
                        <div style="font-weight:600; color:#222;">${adresseFormatee || ville}</div>
                        <div style="font-size:11px; color:#888; margin-top:2px;">${cp} ${ville} — France</div>
                    </div>
                `;

                li.addEventListener('mousedown', (e) => {
                    e.preventDefault();
                    inputAdresse.value = adresseFormatee || r.display_name;
                    inputLat.value = r.lat;
                    inputLng.value = r.lon;
                    suggestionsList.innerHTML = '';
                    afficherCarteValidation(r.lat, r.lon);
                });

                suggestionsList.appendChild(li);
            });
        })
        .catch(err => console.error('Erreur Nominatim :', err));
    }, 400);
});

document.addEventListener('click', e => {
    if (!inputAdresse.parentNode.contains(e.target)) {
        suggestionsList.innerHTML = '';
    }
});