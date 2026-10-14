// =======================================================
// Dragonstone - Main JS for site-wide UI interactions
// =======================================================
$(document).ready(function(){
    // Sticky header effect
    $(window).scroll(function(){
        if ($(this).scrollTop() > 50) {
            $('.site-header').addClass('sticky');
        } else {
            $('.site-header').removeClass('sticky');
        }
    });

    // Smooth scrolling for internal links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.hash);
        if (target.length) {
            $('html, body').animate({scrollTop: target.offset().top - 60}, 600);
        }
    });
});
