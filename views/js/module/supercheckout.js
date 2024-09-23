/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024 Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 *
 */

jQuery(document).ready(function ($) {
    window.Shipmondo = {
        deliveryOptionInputContainerSelector: '#shipping-method',
        deliveryOptionRowSelector: '.highlight'
    };

    const supercheckout_selector = '#supercheckout-fieldset';
    const supercheckout_element = $(supercheckout_selector);

    const triggerShippingOption = function (radio) {
        if (radio && radio.val()) {
            // init click when there are preselected shipping methods
            radio.trigger('click');
        }
    };

    if (supercheckout_element) {
        const shipping_method_element = supercheckout_element.find(window.Shipmondo.deliveryOptionInputContainerSelector);
        //Element ready from start (Version 9)
        if (shipping_method_element) {
            const observer = new MutationObserver(mutationList =>
                mutationList.filter(m => m.type === 'childList').forEach(m => {
                    m.addedNodes.forEach(function (textNode) {
                        //Version 9 inserts ul
                        if ($(textNode).is('ul')) {
                            triggerShippingOption($(textNode).find('.supercheckout_shipping_option:checked'));
                        }
                    });

                }));
            observer.observe(shipping_method_element[0], {childList: true, subtree: true});
        } else {
            //We need to wait on the element to be inserted (Version 8)
            const observer = new MutationObserver(mutationList =>
                mutationList.filter(m => m.type === 'childList').forEach(m => {
                    m.addedNodes.forEach(function (textNode) {
                        if ($(textNode).is(window.Shipmondo.deliveryOptionInputContainerSelector)) {
                            triggerShippingOption($('.supercheckout_shipping_option:checked'));
                        }
                    });
                }));
            observer.observe(supercheckout_element[0], {childList: true, subtree: true});
        }
    }
});