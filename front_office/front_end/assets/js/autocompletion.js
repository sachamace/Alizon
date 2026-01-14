$(document).ready(function(){
    
    // Quand l'utilisateur tape dans l'input #site-search
    $('#site-search').on('keyup', function(){
        
        let recherche = $(this).val();
        console.log("Je cherche : " + recherche);

        // On lance la recherche seulement s'il y a au moins 2 caractères
        if (recherche.length > 1) {
            
            $.ajax({
                url: '/front_office/front_end/html/autocompletion.php',
                type: 'POST',
                data: { search: recherche }, // On envoie ce qui est tapé
                dataType: 'json',
                success: function(data){
                    
                    let html = '';
                    console.log("Je suis pas dans if");
                    // Si on a des résultats
                    if (data.length > 0) {
                        console.log("Je suis dans if");
                        $.each(data, function(index, produit){
                            // On construit le lien vers la page produit
                            
                            html += '<a href="front_office/front_end/html/produitdetail.php?article=' + produit.id_produit + '" class="suggestion-item">' + produit.nom_produit + '</a>';
                        });
                        
                        $('#resultats-recherche').html(html).show();
                    } else {
                        $('#resultats-recherche').hide();
                    }
                }
            });
        } else {
            // Si moins de 2 caractères, on cache la boîte
            $('#resultats-recherche').hide();
        }
    });

    // Bonus : Cacher la liste si on clique ailleurs sur la page
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#site-search, #resultats-recherche').length) {
            $('#resultats-recherche').hide();
        }
    });
});