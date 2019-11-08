/*
 @author    Shipmondo
 @copyright 2019 Shipmondo
 @license   All rights reserved
 */

jQuery(document).ready(function ($) {
    window.Shipmondo = {
        getDeliveryAddressID: function () {
            var address_id = null;
            if (window.Address && window.Address.id_address_delivery) {
                address_id = window.Address.id_address_delivery;
            } else {
                //TODO will this work if alternative address selected for delivery?
                address_id = $('#delivery_address_container').find('.container_card.selected').parent().data("id-address");
            }
            return address_id;
        }
    };
});