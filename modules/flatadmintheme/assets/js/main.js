(function ($) {
    "use strict";

    // Changed some elements with new animation
    var flipInY = 'animated fast flipInY';
    var slowFadeIn = 'animated slow fadeIn';
    $('.screen-options-area').addClass(slowFadeIn);
    $('#mobile-collapse').addClass(flipInY);
    $('.dropdown-menu').removeClass('fadeIn').addClass(flipInY);

    //  Added modal animation effects
    $(window).on('show.bs.modal', function () {
        $('.modal-content').addClass('animated fast zoomInUp');
    });

    // Add butons wave effects
    Waves.init();
    Waves.attach('.btn', ['waves-effect', 'waves-light', 'waves-ripple']);
    $('body').addClass('flatadmintheme_initiated');

    // Change chart default font color
    Chart.defaults.global.defaultFontColor = "#000000";

    // Ini nanobar
    var options = {
        target: document.getElementsByTagName("BODY")[0]
    };

    var nanobar = new Nanobar(options);
    if (document.readyState == 'loading') {
        nanobar.go(30);
        nanobar.go(76);
        nanobar.go(100);
    }
})(jQuery);