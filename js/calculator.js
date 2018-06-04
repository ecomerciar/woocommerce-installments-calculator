jQuery(function () {

    jQuery('#installments-calculator').accordion({
        collapsible: true,
        active: false,
        heightStyle: 'content'
    });

    jQuery('#mercadopago_installments_calculator_cards').on('change', function () {
        jQuery('#installments_message').text('');
        jQuery('#mercadopago_installments_calculator_banks option:not(:first)').remove();
        jQuery('#mercadopago_installments_calculator_installments option:not(:first)').remove();
        if (this.value === 'nothing') {
            return true;
        }
        var data = {
            'action': 'ic_check_card',
            'card_selected': this.value,
            'product_id': jQuery('.product-main .product-summary button[name="add-to-cart"]').attr('value'),
            'payment_method': 'mercadopago'
        };

        jQuery('#mercadopago_installments_calculator_banks').attr('disabled', '');
        jQuery('#mercadopago_installments_calculator_installments').attr('disabled', '');

        jQuery.ajax({
            type: 'post',
            data: data,
            url: ajax_object.ajax_url,
            success: function (data) {
                if (data.success) {
                    var banks = data.data.banks;
                    if (banks) {
                        jQuery.each(banks, function (index, val) {
                            jQuery('#mercadopago_installments_calculator_banks').append(jQuery("<option></option>").attr("value", val.id).text(val.name));
                        });
                        jQuery('#mercadopago_installments_calculator_banks').removeAttr('disabled');
                        jQuery('#mercadopago_installments_calculator_installments').removeAttr('disabled');
                    } else {
                        var installments = data.data.installments;
                        jQuery.each(installments, function (index, val) {
                            jQuery('#mercadopago_installments_calculator_installments').append(jQuery("<option></option>").attr("value", val.message).text(index + ' Cuotas'));
                        });
                        jQuery('#mercadopago_installments_calculator_installments').removeAttr('disabled');
                    }
                } else {
                    console.log(data.data.msg);
                }
            }
        });
    });

    jQuery('#mercadopago_installments_calculator_banks').on('change', function () {
        jQuery('#installments_message').text('');
        jQuery('#mercadopago_installments_calculator_installments option:not(:first)').remove();
        if (this.value === 'nothing') {
            return true;
        }
        var data = {
            'action': 'ic_check_bank',
            'card_selected': jQuery('#mercadopago_installments_calculator_cards').find(":selected").attr('value'),
            'bank_selected': this.value,
            'product_id': jQuery('.product-main .product-summary button[name="add-to-cart"]').attr('value'),
            'payment_method': 'mercadopago'
        };

        jQuery('#mercadopago_installments_calculator_installments').attr('disabled', '');

        jQuery.ajax({
            type: 'post',
            data: data,
            url: ajax_object.ajax_url,
            success: function (data) {
                if (data.success) {
                    jQuery('#mercadopago_installments_calculator_installments').removeAttr('disabled');
                    var installments = data.data.installments;
                    jQuery.each(installments, function (index, val) {
                        if (val.CFT === 0) {
                            jQuery('#mercadopago_installments_calculator_installments').append(jQuery("<option></option>").attr("value", val.message).text(index + ' Cuotas sin interés'));
                        } else {
                            jQuery('#mercadopago_installments_calculator_installments').append(jQuery("<option></option>").attr("value", val.message).text(index + ' Cuotas'));
                        }
                    });
                } else {
                    console.log(data.data.msg);
                }
            }
        });
    });

    jQuery('#mercadopago_installments_calculator_installments').on('change', function () {
        if (this.value === 'nothing') {
            jQuery('#installments_message').text('');
            return;
        }
        jQuery('#installments_message').html(this.value);
    });

});