/**
 * RGBlind — simulation daltonisme via filtres SVG
 * Adapté pour fonctionner en local sans CDN
 * Librairire Installer 
 */
(function() {
    var SVG_ID = 'rgblind-svg-filters';

    var filters = {
        deuteranopia: 'url(#rgblind-deuteranopia)',
        protanopia:   'url(#rgblind-protanopia)',
        tritanopia:   'url(#rgblind-tritanopia)',
    };

    function injectFilters() {
        if (document.getElementById(SVG_ID)) return;
        var svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('id', SVG_ID);
        svg.setAttribute('style', 'position:absolute;width:0;height:0;overflow:hidden;');
        svg.innerHTML = '<defs>'
            + '<filter id="rgblind-deuteranopia">'
            + '<feColorMatrix type="matrix" values="'
            + '0.625 0.375 0     0 0 '
            + '0.7   0.3   0     0 0 '
            + '0     0.3   0.7   0 0 '
            + '0     0     0     1 0"/>'
            + '</filter>'
            + '<filter id="rgblind-protanopia">'
            + '<feColorMatrix type="matrix" values="'
            + '0.567 0.433 0     0 0 '
            + '0.558 0.442 0     0 0 '
            + '0     0.242 0.758 0 0 '
            + '0     0     0     1 0"/>'
            + '</filter>'
            + '<filter id="rgblind-tritanopia">'
            + '<feColorMatrix type="matrix" values="'
            + '0.95  0.05  0     0 0 '
            + '0     0.433 0.567 0 0 '
            + '0     0.475 0.525 0 0 '
            + '0     0     0     1 0"/>'
            + '</filter>'
            + '</defs>';
        document.body.appendChild(svg);
    }

    window.rgblind = {
        deuteranopia: function() {
            injectFilters();
            document.documentElement.style.filter = filters.deuteranopia;
        },
        protanopia: function() {
            injectFilters();
            document.documentElement.style.filter = filters.protanopia;
        },
        tritanopia: function() {
            injectFilters();
            document.documentElement.style.filter = filters.tritanopia;
        },
        reset: function() {
            document.documentElement.style.filter = '';
        }
    };
})();