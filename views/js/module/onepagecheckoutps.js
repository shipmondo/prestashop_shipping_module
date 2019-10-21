/*
 @author    Shipmondo
 @copyright 2019 Shipmondo
 @license   All rights reserved
 */

jQuery(document).ready(function ($) {


    // window.SMGetLatestAddress = true;

    //TODO I stedet for en funktion her som egentlig bare returnere selector. Kan vi så ikke bar have en var?
    window.SMGetDeliveryAddressID = function () {
        console.log('SMGetDeliveryAddressID');

        var address_id = null;
        if (window.Address && window.Address.id_address_delivery) {
            address_id = window.Address.id_address_delivery;
        } else {
            address_id = $('#delivery_address_container').find('.container_card.selected').parent().data("id-address");
        }

        console.log(window.Address.id_address_delivery);
        console.log($('#delivery_address_container').find('.container_card.selected').parent().data("id-address"));

        return address_id;
    }
});