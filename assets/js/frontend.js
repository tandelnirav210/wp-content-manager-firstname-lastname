(function($) {
    'use strict';

    $(document).ready(function() {
        // Check if AJAX container exists
        var $container = $('#wpcmp-ajax-container');

        if ($container.length) {
            loadPromoBlocks($container);
        }
    });

    function loadPromoBlocks($container) {
        var nonce = $container.data('nonce') || wpcmp_ajax.nonce;

        $.ajax({
            url: wpcmp_ajax.ajax_url,
            type: 'GET',
            dataType: 'json',
            data: {
                action: 'get_promo_blocks',
                nonce: nonce
            },
            beforeSend: function() {
                $container.html('<div class="wpcmp-loading">' +
                    wpcmp_ajax.loading_text +
                    '</div>');
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $container.html(response.data.html);
                } else {
                    showError($container, response.data.message || 'Failed to load content');
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX Error:', error);
                showError($container, 'Failed to load promo blocks. Please try again.');
            }
        });
    }

    function showError($container, message) {
        $container.html(
            '<div class="wpcmp-error">' +
            '<p>' + message + '</p>' +
            '<button class="wpcmp-retry">Retry</button>' +
            '</div>'
        );

        $container.find('.wpcmp-retry').on('click', function() {
            loadPromoBlocks($container);
        });
    }

})(jQuery);