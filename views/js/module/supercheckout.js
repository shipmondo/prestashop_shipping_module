/**
 *  @author    Shipmondo
 *  @copyright 2024 Shipmondo
 *  @license   All rights reserved
 *
 */

jQuery(document).ready(function ($) {
    window.Shipmondo = {
        deliveryOptionInputContainerSelector: '#shipping-method',
        deliveryOptionRowSelector: '.highlight'
    };

    const supercheckout_selector = '#supercheckout-fieldset';
    const supercheckout_element = $(supercheckout_selector);
    const triggerShippingOption = function () {
        console.log('triggerShippingOption');

        const current_radio = $('.supercheckout_shipping_option:checked');
        if (current_radio.val()) {
            console.log('trigger');

            // init click when there are preselected shipping methods
            current_radio.trigger('click');
        }
    };

    if (supercheckout_element) {
        console.log('supercheckout_element', supercheckout_element);
        //Element ready from start
        if (supercheckout_element.find(window.Shipmondo.deliveryOptionInputContainerSelector)) {
            console.log('element found');

            triggerShippingOption();
        } else {
            console.log('observe');

            //We need to wait on the element to be inserted
            const observer = new MutationObserver(mutationList =>
                mutationList.filter(m => m.type === 'childList').forEach(m => {
                    m.addedNodes.forEach(function (textNode) {
                        console.log('textnode', textNode);
                        if ($(textNode).is(window.Shipmondo.deliveryOptionInputContainerSelector)) {
                            console.log('found via observe');
                            triggerShippingOption();
                        }
                    });

                }));
            observer.observe(supercheckout_element[0], {childList: true, subtree: true});
        }
    }
});