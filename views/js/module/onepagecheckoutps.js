/*
 @author    Shipmondo
 @copyright 2019 Shipmondo
 @license   All rights reserved
 */

jQuery(document).ready(function ($) {
    console.log('init onepagecheckout');

    //TODO one issue - if you edit the address the JS address object will not get updated.
    window.SMGetDeliveryAddressID = function () {
        var address_id = null;
        if (window.Address && window.Address.id_address_delivery) {
            address_id = window.Address.id_address_delivery;
        } else {
            address_id = $('#delivery_address_container').find('.container_card.selected').parent().data("id-address");
        }
        return address_id;
    };

    //
    // window.SMContinueBtnSelector = "#buttons_footer_review #btn_place_order";
    //
    // //We can append the "block" button when container is added.
    // $("#onepagecheckoutps").bind("DOMNodeInserted", function (e) {
    //     var textNode = e.target;
    //     var container = $(textNode).find("#buttons_footer_review .col-12");
    //
    //     if (container.length == 1) {
    //         du er kommet her til og det virker ikke som det skal. Måden hvor den bliver tilføjet/skjult vist skal laves anderledes.
    //         if ((container).find('.select-service-point-to-continue').length == 0) {
    //             container.append('<button type="button" class="btn btn-primary select-service-point-to-continue">' + modal_header_title + '</button>');
    //             //Not good:
    //             if(!window.SMShowContinueBtn){
    //                 $(textNode).find("#buttons_footer_review .col-12").find('#select-service-point-to-continue').show();
    //             }
    //         }
    //     }
    // });
});