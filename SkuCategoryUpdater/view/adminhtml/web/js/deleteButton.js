define([
    'jquery',
    'mage/translate',
    'Magento_Ui/js/modal/alert',
    'Magento_Ui/js/modal/confirm'
], function ($, $t, alert, confirmation) {
    'use strict';

    return function (config) {
        $(config.button).on('click', function () {
            var btn = $(this);

            confirmation({
                title: $t('Confirm Action'),
                content: $t('Would you really like to remove these products from the specified category?'),
                actions: {
                    confirm: function () {
                        btn.prop('disabled', true);
                        btn.text($t('Processing...'));
                        var separator = $('#generate_categories_input_fields_separator_select').val() === '1' ? ',' : '\n';

                        $.ajax({
                            url: config.ajaxUrl,
                            data: {
                                skus: $(config.skuInput).val().split(separator).map(function(sku) { return sku.trim(); }),
                                category: $(config.categoryInput).val()
                            },
                            type: 'POST',
                            showLoader: true,
                            success: function (response) {
                                btn.prop('disabled', false);
                                btn.text($t('Delete Products'));
                                alert({content: $t(response.message)});
                                var message = $t('Successfully removed ' + response.completedSkus + ' SKUs. ');

                                if (response.failedSkus.length > 0) {
                                    var failedMessages = response.failedSkus.map(function (item) {
                                        return "<br/>" + item.sku + ' -' + item.error;
                                    });

                                    message += $t('Failed to remove following SKUs: ') + failedMessages.join(', ');
                                }

                                $(config.statusUpdate).html(message + '<a href="#" id="close_status_delete" class="close-delete-update" style="margin-left:10px;">Close</a>').show();
                                $('#close_status_delete').on('click', function (e) {
                                    e.preventDefault();
                                    $(config.statusUpdate).hide();
                                });
                            },
                            error: function (response) {
                                btn.prop('disabled', false);
                                btn.text($t('Delete Products'));
                                alert({content: $t('An error occurred during the operation')});
                            }
                        });
                    },
                    cancel: function () {
                        btn.prop('disabled', false);
                    }
                }
            });
        });
    };
});
