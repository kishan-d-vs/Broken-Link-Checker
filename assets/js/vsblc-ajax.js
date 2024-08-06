jQuery(document).ready(function ($) {
    $('#vsblc-check-links-button').on('click', function (e) {
        e.preventDefault();
        console.log("HEllo");
        $.ajax({
            url: vsblcAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'vsblc_check_links'
            },
            success: function (response) {
                $('#vsblc-results').html(response);
            }
        });
    });
});
