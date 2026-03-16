/* Utilisation IA pour pas avoir de CDN */

(function() {
    var SVG_ID = 'rgblind-svg-filters';

    // Injection immédiate au chargement du script
    function injectFilters() {
        if (document.getElementById(SVG_ID)) return;

        var svg = document.createElement('div');
        svg.innerHTML = '<svg id="' + SVG_ID + '" xmlns="http://www.w3.org/2000/svg" style="position:absolute;width:0;height:0;">'
            + '<defs>'
            + '<filter id="rgblind-deuteranopia"><feColorMatrix type="matrix" values="0.625 0.375 0 0 0 0.7 0.3 0 0 0 0 0.3 0.7 0 0 0 0 0 1 0"/></filter>'
            + '<filter id="rgblind-protanopia"><feColorMatrix type="matrix" values="0.567 0.433 0 0 0 0.558 0.442 0 0 0 0 0.242 0.758 0 0 0 0 0 1 0"/></filter>'
            + '<filter id="rgblind-tritanopia"><feColorMatrix type="matrix" values="0.95 0.05 0 0 0 0 0.433 0.567 0 0 0 0.475 0.525 0 0 0 0 0 1 0"/></filter>'
            + '</defs>'
            + '</svg>';

        document.body.appendChild(svg.firstChild);
    }

    // Injecter dès que le body est disponible
    if (document.body) {
        injectFilters();
    } else {
        document.addEventListener('DOMContentLoaded', injectFilters);
    }

    window.rgblind = {
        deuteranopia: function() {
            injectFilters();
            document.documentElement.style.filter = 'url(#rgblind-deuteranopia)';
        },
        protanopia: function() {
            injectFilters();
            document.documentElement.style.filter = 'url(#rgblind-protanopia)';
        },
        tritanopia: function() {
            injectFilters();
            document.documentElement.style.filter = 'url(#rgblind-tritanopia)';
        },
        reset: function() {
            document.documentElement.style.filter = '';
        }
    };
})();