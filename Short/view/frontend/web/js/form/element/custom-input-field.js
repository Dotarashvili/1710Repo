define([
    'Magento_Ui/js/form/element/abstract',
    'jquery',
    'Magento_Ui/js/modal/modal',
    'text!DevAll_Short/template/form/element/short-address-modal.html',
    'Magento_Checkout/js/checkout-data',
    'Magento_Checkout/js/model/quote',
    'mage/url',
    'mage/translate'
], function (Abstract, $, modal, shortAddressTemplate, checkoutData, quote, urlBuilder, $t) {
    'use strict';

    return Abstract.extend({
        defaults: {
            template: 'DevAll_Short/form/element/custom-input-field'
        },

        modalOptions: {
            type: 'popup',
            responsive: true,
            innerScroll: true,
            title: 'Enter Short Address',
            modalClass: "short-address-popup",
        },
        modalElement: null,
        isApiSuccess: false,
        apiData: null,
        areEventsBound: false,

        initialize: function () {
            this._super();

            this.openModal = this.openModal.bind(this);
        },

        openModal: function () {
            var self = this;
            self.isApiSuccess = false;
            self.apiData = null;
            this.modalElement = $('<div/>').html(shortAddressTemplate);
            $('#short_address_input').val('');

            modal(this.modalOptions, this.modalElement);
            this.modalElement.modal('openModal');

            this.bindModalEvents();
        },

        bindModalEvents: function () {
            var self = this;

            var messageDiv = $('.message_div');
            var shortAddressInput = $('#short_address_input');
            var closeButton = $('.modal-close');
            var confirmButton = $('.modal-confirm');

            messageDiv.text($t('Please enter short national address.'));

            $('body').off('keyup', '#short_address_input').on('keyup', '#short_address_input', function (e) {
                e.stopPropagation();
                e.preventDefault();
                self.isApiSuccess = false;
                self.apiData = null;
                messageDiv.text($t('Please enter short national address.'));

                if ($(this).val().length >= 8) {
                    self.callApi();
                }
            });

            closeButton.off('click').on('click', function () {
                self.closeModal();
            });

            confirmButton.on('click', function () {
                if (self.isApiSuccess) {
                    var cityDropdown = $('[name="region_id"]');
                    var originalRegionId = cityDropdown.attr('id');
                    var postCode = $('[name="postcode"]');
                    var street = $('[name="street[0]"]');
                    var unitInput = $('#unit_apartment_suite_input');

                    var full = self.apiData.Addresses[0];

                    if (!cityDropdown.next().is('input')) {
                        var cityInput = $('<input>', {
                            'id': originalRegionId,
                            'class': 'input-text',
                            'type': 'text',
                            'data-bind': cityDropdown.attr('data-bind').replace('optionsCaption: caption,', '').replace('optionsValue: \'value\',', '').replace('optionsText: \'label\',', ''),
                            'name': 'input_region_id',
                            'placeholder': '',
                            'aria-required': 'true',
                            'aria-invalid': 'false'
                        });

                        cityDropdown.after(cityInput);
                    }
                    cityDropdown.hide();

                    $('[name="input_region_id"]').val(full['City']).trigger('input');
                    $('[name="city"]').val(full['District']);
                    postCode.val(full['PostCode']);
                    street.val(full['BuildingNumber'] + ',' + full['Street'] + ',' + full['AdditionalNumber']);

                    if (unitInput.val()) {
                        $('[name="street[1]"]').val(unitInput.val()).trigger('change');
                    }

                    $('[name="city"]').trigger('change');
                    $('[name="postcode"]').trigger('change');
                    $('[name="street[0]"]').trigger('change');

                    self.closeModal();
                    $('.cancel-short-address').show();
                    $('.cancel-short-address').css('display', 'inline-block');

                    var shippingAddress = quote.shippingAddress();

                    if (shippingAddress) {
                        shippingAddress.region = $('[name="input_region_id"]').val();
                        shippingAddress.region_code = $('[name="input_region_id"]').val();
                        quote.shippingAddress(shippingAddress);

                        $.ajax({
                            url: '/devall_short/ajax/saveaddress',
                            type: 'POST',
                            cache: false,
                            data: {
                                region: $('[name="input_region_id"]').val(),
                                region_code: $('[name="input_region_id"]').val()
                            },
                            success: function (response) {
                                if (response.success) {
                                    console.log('Address updated successfully on the server.');
                                } else {
                                    console.error('Failed to update address on the server.');
                                }
                            },
                            error: function () {
                                console.error('Error while updating address on the server.');
                            }
                        });
                    }
                }
            });
        },

        cancelShortAddress: function () {
            var cityInput = $('[name="input_region_id"]');
            var cityDropdown = cityInput.prev();

            cityInput.remove();
            cityDropdown.show();

            cityDropdown.val('').trigger('change');
            $('[name="city"]').val('').trigger('change');
            $('[name="postcode"]').val('').trigger('change');
            $('[name="street[0]"]').val('').trigger('change');
            $('[name="street[1]"]').val('').trigger('change');

            $('.cancel-short-address').hide();
        },

        callApi: function () {
            var self = this;
            self.apiData = null;
            var messageDiv = $('.message_div');
            var shortAddressInput = $('#short_address_input');
            var shortAddress = shortAddressInput.val();

            if (shortAddress) {
                var apiUrl = urlBuilder.build('short/ajax/address?shortaddress=' + shortAddress);
                $.ajax({
                    url: apiUrl,
                    type: 'GET',
                    dataType: 'json',
                    cache: false,
                    success: function (data) {
                        if (data && data.totalSearchResults > 0) {
                            self.apiData = data;
                            var full = data.Addresses[0];
                            var fullAddress = [];

                            for (var key in full) {
                                if (full.hasOwnProperty(key) && full[key]) {
                                    fullAddress.push(full[key]);
                                }
                            }

                            messageDiv.text(fullAddress.join(', '));
                            messageDiv.css('border-left', '5px solid green');
                            self.isApiSuccess = true;
                        } else {
                            messageDiv.text($t('No address found for the given short address.'));
                            self.isApiSuccess = false;
                        }
                    },
                    error: function (request, status, error) {
                        console.error('Error occurred:', error);
                    }
                });
            }
        },


        closeModal: function () {
            if (this.modalElement) {
                this.modalElement.modal('closeModal').remove();
                this.apiData = null;
            }
        }
    });
});