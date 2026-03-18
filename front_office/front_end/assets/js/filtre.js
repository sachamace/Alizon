document.addEventListener("DOMContentLoaded", function() {
    const filtre = document.getElementById('filtre');
    const openBtn = document.getElementById('openFilter');

    if (localStorage.getItem('filterVisible') === 'true') {
        if (filtre) filtre.style.display = 'block';
        if (openBtn) openBtn.setAttribute('aria-expanded', 'true');
    }

    if (openBtn) {
        openBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const isVisible = filtre.style.display === 'block';
            if (isVisible) {
                filtre.style.display = 'none';
            } else {
                filtre.style.display = 'block';
            }
            
            const nowVisible = filtre.style.display === 'block';
            localStorage.setItem('filterVisible', nowVisible);
            openBtn.setAttribute('aria-expanded', nowVisible);
        });
    }

    document.addEventListener('click', function(e) {
        const isMobile = window.innerWidth <= 768;

        if (isMobile && filtre && !filtre.contains(e.target) && e.target !== openBtn) {
            if (filtre.style.display === 'block') {
                filtre.style.display = 'none';
                localStorage.setItem('filterVisible', 'false');
                openBtn.setAttribute('aria-expanded', 'false');
            }
        }
    });

    if (filtre) {
        filtre.addEventListener('click', function(e) {
            e.stopPropagation();
        });
    }

    const triElements = document.querySelectorAll('#tri, input[type="checkbox"]');
    triElements.forEach(el => {
        el.addEventListener('change', function() {
            document.getElementById('tri-form').submit();
        });
    });

    const prixMin = document.getElementById('prixMinInput');
    const prixMax = document.getElementById('prixMaxInput');
    const noteMin = document.getElementById('noteMinInput');
    const noteMax = document.getElementById('noteMaxInput');

    const validerBornes = () => {
        if (prixMin.value && prixMax.value && parseFloat(prixMin.value) > parseFloat(prixMax.value)) {
            prixMin.value = prixMax.value - 1;
        }
        if (noteMin.value && noteMax.value && parseFloat(noteMin.value) > parseFloat(noteMax.value)) {
            noteMin.value = noteMax.value -1;
        }
    };

    let timeout = null;
    const inputsAuto = document.querySelectorAll('#prixMinInput, #prixMaxInput, #noteMinInput, #noteMaxInput');
    
    inputsAuto.forEach(input => {
        input.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                validerBornes(); 
                document.getElementById('tri-form').submit();
            }, 800);
        });
    });
});