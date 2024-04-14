jQuery(document).ready(function ($) {
    const $container = $('.wordgram-configurations-container');
    const $testConnection = $container.find('.testing');

    if ($testConnection.length) {
        $.get(window.ajaxurl, {
            action: 'wordgram-test-connection',
        }, null, 'json').done(function (data) {
            if (data.success) {
                if (data.data.code === 'not_authenticated' || data.data.code === 'not_found') {
                    $testConnection.hide();
                    $container.find('.not-connected').show();
                } else if (data.data.code === 'verified_successfully') {
                    $testConnection.hide();
                    $container.find('.connected').show().find('.instagram_username').text(data.data.instagram_username);
                }
            } else {
                $testConnection.find('p:not(.error)').hide();
                $testConnection.find('p.error').show();
            }
        }).fail(function () {
            $testConnection.find('p:not(.error)').hide();
            $testConnection.find('p.error').show();
        }).always(function () {
            $testConnection.find('.spinner').removeClass('is-active');
        });
    }

    $container.find('.not-connected button.connect').on('click', function (e) {
        e.preventDefault();
        const $form = $(this).closest('form');
        data = {};
        let isValid = true;
        $form.find('input').each(function () {
            data[$(this).attr('name')] = $(this).val();
            if ($(this).attr('required') && !$(this).val()) {
                isValid = false;
                return false;
            }
        });
        if (!isValid) {
            alert('Please fill in all the required fields.');
            return false;
        }
        data = JSON.stringify(data);
        $.ajax({
            url: $form.attr('action'),
            method: $form.attr('method'),
            contentType: 'application/json',
            data: data,
            success: function (response) {
                alert(response.message);
                if (response.status == 'success' && response.data && response.data.redirect_url) {
                    $.post(response.data.redirect_url, {
                        data: response.data
                    }, null, 'json').done(function (response_data) {
                        if (response_data && response_data.success && response_data.data.code === 'connected') {
                            window.location.reload();
                        }
                    });
                }
            },
            error: function (xhr, status, error) {
                alert("Something went wrong. Please try again later.");
            }
        });
    });

    $container.find('.connected button.disconnect').on('click', function (e) {
        const disconnectMessage = 'Disconnecting from Wordgram will schedule to remove products from your WC store' +
            " automatically which were added by Wordgram. Are you sure you'd like to disconnect your Wordgram account?"
        if (confirm(disconnectMessage)) {
            $(this).text('Disconnecting...').prop('disabled', true);
            $.get(window.ajaxurl, {
                action: 'wordgram-disconnect',
            }, null, 'json').done(function (data) {
                if (data && data.success && data.data.code === 'disconnected') {
                    window.location.reload();
                }
            });
        }
        return false;
    });

    $container.find('.connected button.sync-shop').on('click', function (e) {
        $(this).text('Syncing...').prop('disabled', true);
        $.get(window.ajaxurl, {
            action: 'wordgram-sync-shop',
        }, null, 'json').done(function (data) {
            if (data && data.success && data.data.code === 'synced') {
                alert(data.data.message);
                window.location.reload();
            }
        });
        return false;
    });
});