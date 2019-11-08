/*
 @author    Shipmondo
 @copyright 2019 Shipmondo
 @license   All rights reserved
 */

jQuery(document).ready(function ($) {
    console.log('init thecheckout');
    window.Shipmondo = {
        getDeliveryAddressID: function () {
            var address_id = null;
            // if (window.Address && window.Address.id_address_delivery) {
            //     address_id = window.Address.id_address_delivery;
            // } else {
            address_id = $("input[name^=delivery_option]:first").attr("name").replace(/[^\d]/g, "");
            // }
            console.log(address_id);
            return address_id;
        }
    };
});