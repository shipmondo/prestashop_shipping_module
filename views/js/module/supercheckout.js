/**
 *  @author    Shipmondo
 *  @copyright 2023 Shipmondo
 *  @license   All rights reserved
 *
 */

jQuery(document).ready(function ($) {
    var supercheckout_selector = '#supercheckout-fieldset';

    window.Shipmondo = {
        deliveryOptionInputContainerSelector: '#shipping-method',
        deliveryOptionRowSelector: '.highlight'
    };

    $(supercheckout_selector).bind("DOMNodeInserted", function (e) {
        var textNode = e.target;
        if ($(textNode).is(window.Shipmondo.deliveryOptionInputContainerSelector)) {
            var current_radio = $('.supercheckout_shipping_option:checked');
            if (current_radio.val()) {
                // init click when there are preselected shipping methods
                current_radio.trigger('click');
            }
        }
    });
});