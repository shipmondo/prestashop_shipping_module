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

        const shipping_method_element = supercheckout_element.find(window.Shipmondo.deliveryOptionInputContainerSelector);
        //Element ready from start
        if (shipping_method_element) {
            console.log('element found', shipping_method_element);

            //triggerShippingOption();

            const observer = new MutationObserver(mutationList =>
                mutationList.filter(m => m.type === 'childList').forEach(m => {
                    m.addedNodes.forEach(function (textNode) {
                        console.log('textnode', textNode);
                        console.log('basic', $(textNode).find('.supercheckout_shipping_option'));
                        console.log('checked', $(textNode).find('.supercheckout_shipping_option:checked'));
                        console.log('ul?', $(textNode).is('ul'));
                        if ($(textNode).is('ul')) {
                            console.log('we are on UL')
                            const checked_radio_button = $(textNode).find('.supercheckout_shipping_option:checked');
                            console.log('checked_radio_button', checked_radio_button);
                            if (checked_radio_button.val()) {
                                console.log('trigger');

                                // init click when there are preselected shipping methods
                                checked_radio_button.trigger('click');
                            }
                        }


                        /*  if ($(textNode).is('.supercheckout_shipping_option')) {
                              console.log('found via observe');
                              console.log('checked', $(textNode).is(':checked'));
                              //triggerShippingOption();
                          }

                         */
                    });

                }));
            observer.observe(shipping_method_element[0], {childList: true, subtree: true});

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