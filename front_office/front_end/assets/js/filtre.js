$(function() {
    const $filtre = $('#filtre');
    const $openBtn = $('#openFilter');

    if (localStorage.getItem('filterVisible') === 'true') {
        $filtre.show();
        $openBtn.attr('aria-expanded', 'true');
    }

    $openBtn.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation(); // Empêche le clic de se propager au document
        $filtre.toggle();
        
        const isVisible = $filtre.is(':visible');
        localStorage.setItem('filterVisible', isVisible);
        $openBtn.attr('aria-expanded', isVisible);
    });

    $(document).on('click', function(e) {
        // Si le clic n'est pas sur le menu (#filtre) et pas sur le bouton (#openFilter)
        if (!$filtre.is(e.target) && $filtre.has(e.target).length === 0 && !$openBtn.is(e.target)) {
            if ($filtre.is(':visible')) {
                $filtre.hide();
                localStorage.setItem('filterVisible', 'false');
                $openBtn.attr('aria-expanded', 'false');
            }
        }
    });

    // Empêcher la fermeture si on clique à l'intérieur du formulaire de filtre
    $filtre.on('click', function(e) {
        e.stopPropagation();
    });

    $('#tri, input[type="checkbox"]').on('change', function() {
        $('#tri-form').submit();
    });

    let timeout = null;
    $('#prixMinInput, #prixMaxInput, #noteMinInput, #noteMaxInput').on('keyup input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            $('#tri-form').submit();
        }, 800);
    });
});
$(function(){
    $('#tri, input[type="checkbox"]').on('change', function() {
        $('#tri-form').submit();
    });
    let timeout = null;
    $('#prixMinInput, #prixMaxInput, #noteMinInput, #noteMaxInput').on('keyup input', function() {
        clearTimeout(timeout);
        timeout = setTimeout(function() {
            $('#tri-form').submit();
        }, 800);
    });
});