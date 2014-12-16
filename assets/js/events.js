jQuery(document).ready(function($) {
    // Options
    $('#display-options a').on('click', function(e) {
        e.preventDefault();
        $(this).parent().hide();
        $('#options').show();
    });

    // Help to select generated css
    $('#generated_css').on('click', function(e) {
        e.preventDefault();
        $(this).select();
    });

});