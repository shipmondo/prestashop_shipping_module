/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024 Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
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
                        //Only append once
                        if ($(textNode).find('td.carrier-extra-content').size() == 0) {
                            const container = $(textNode).find(window.Shipmondo.deliveryOptionRowSelector);
                            //Add "Missing" extra content.
                            container.append('<td class="carrier-extra-content"></td>');

                            const current_radio = $('.supercheckout_shipping_option:checked');
                            if (current_radio.val()) {
                                //init click when there are preselected shipping methods
                                current_radio.trigger('click');
                            }
                        }
                    }
                });

            }));
        observer.observe(supercheckout_element[0], {childList: true, subtree: true});
    }
});