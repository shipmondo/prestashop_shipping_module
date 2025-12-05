/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024 Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 *
 */

jQuery(document).ready(function ($) {
  const shippingMethodContainer = '#shipping-method';
  const deliveryOptionSelector = 'input.supercheckout_shipping_option';
  const supercheckoutElement = $('#supercheckout-fieldset');

  //Override default
  window.shipmondoModule.deliveryOptionSelector = deliveryOptionSelector;

  const triggerShippingOption = function (radio) {
    if (radio && radio.val()) {
      // init click when there are preselected shipping methods
      radio.trigger('click');
    }
  };

  if (supercheckoutElement) {
    const shipping_method_element = supercheckoutElement.find(shippingMethodContainer);
    //Element ready from start (Version 9)
    if (shipping_method_element) {
      const observer = new MutationObserver((mutationList) =>
        mutationList
          .filter((m) => m.type === 'childList')
          .forEach((m) => {
            m.addedNodes.forEach(function (textNode) {
              //Version 9 inserts ul
              if ($(textNode).is('ul')) {
                triggerShippingOption($(textNode).find(deliveryOptionSelector + ':checked'));
              }
            });
          }),
      );
      observer.observe(shipping_method_element[0], { childList: true, subtree: true });
    } else {
      //We need to wait on the element to be inserted (Version 8)
      const observer = new MutationObserver((mutationList) =>
        mutationList
          .filter((m) => m.type === 'childList')
          .forEach((m) => {
            m.addedNodes.forEach(function (textNode) {
              if ($(textNode).is(window.Shipmondo.deliveryOptionInputContainerSelector)) {
                triggerShippingOption($(deliveryOptionSelector + ':checked'));
              }
            });
          }),
      );
      observer.observe(supercheckoutElement[0], { childList: true, subtree: true });
    }
  }
});
