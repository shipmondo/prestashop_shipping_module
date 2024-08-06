/**
 *  @author    Shipmondo
 *  @copyright 2024 Shipmondo
 *  @license   All rights reserved
 *
 */

jQuery(document).ready(function ($) {
    const supercheckout_selector = '#supercheckout-fieldset';

    window.Shipmondo = {
        deliveryOptionInputContainerSelector: '#shipping-method',
        deliveryOptionRowSelector: '.highlight'
    };

    const supercheckout_element = $(supercheckout_selector);
    if (supercheckout_element && supercheckout_element.length === 1) {
        const observer = new MutationObserver(mutationList =>
            mutationList.filter(m => m.type === 'childList').forEach(m => {
                m.addedNodes.forEach(function (textNode) {
                    if ($(textNode).is(window.Shipmondo.deliveryOptionInputContainerSelector)) {
                        const current_radio = $('.supercheckout_shipping_option:checked');
                        if (current_radio.val()) {
                            // init click when there are preselected shipping methods
                            current_radio.trigger('click');
                        }
                    }
                });

            }));
        observer.observe(supercheckout_element[0], {childList: true, subtree: true});
    }
});