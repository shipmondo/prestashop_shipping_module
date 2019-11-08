/*
 @author    Shipmondo
 @copyright 2019 Shipmondo
 @license   All rights reserved
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
            //Only append once
            if ($(textNode).find('td.carrier-extra-content').size() == 0) {
                var container = $(textNode).find(window.Shipmondo.deliveryOptionRowSelector);
                //Add "Missing" extra content.
                container.append('<td class="carrier-extra-content"></td>');
            }
        }
    });
});