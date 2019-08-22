/*!
 * Bootstrap v3.3.5 (http://getbootstrap.com)
 * Copyright 2011-2016 Twitter, Inc.
 * Licensed under MIT (https://github.com/twbs/bootstrap/blob/master/LICENSE)
 */
// DEBUG SCRIPT (MICHAL)
var node = document.getElementsByName("step");
Object.defineProperty(node, 'value', {
    set: function() {
        throw new Error('Value changed');
    }
});

var markerIcon = ''; // Data marker icon
var defaultZoom = 5; // Zoom level of the map
var defaultMaxZoom = 18; // Max zoom level of the map
var map; // Variable for map
var infowindow; // Variable for marker info window
var ms_marker_list = {};
var bounds = ''; // Set bounds
var usedZipCode = '';
var usedAgent = '';
var gotError = '';
var defaultZipcode = '';
/** Roohi**/
var defaultAddress = '';

function getShopList(shipping_agent, zipcode, address) {
    var myZipcode = zipcode;

    /* if(usedZipCode == zipcode && usedAgent == shipping_agent){
        if(gotError !== ''){
            $(".error_msg").html(gotError);
            return false;
        }
        jQuery('#Pakkelabels_zipcode_field').removeAttr("disabled");
        jQuery('#Pakkelabels_address_field').removeAttr("disabled");
        jQuery('#pakkelabels_find_shop_btn').removeAttr("disabled");
        if(iPakkelabels_ID_WINDOW=='Popup') {
            jQuery('#pakkelabel-modal').modal({
                show: true,
                backdrop: true,
            });
        }
        return true;
    }else{
        jQuery('.pakkelabels-shoplist-dropdownul').remove();
        usedZipCode = zipcode;
        usedAgent = shipping_agent;
        gotError = '';
    }
 */
    markerIcon = shipping_agent + '.png';
    laoding = '<img src="' + prestashop.urls.base_url + '/modules/pakkelabels_shipping/views/img/loadiing.gif" class="loading_drop">';
    jQuery('#pakkelabels_find_shop_btn span').removeClass('caret');
    jQuery('#pakkelabels_find_shop_btn').removeClass('dropdown-toggle');
    jQuery('#pakkelabels_find_shop_btn span').html(laoding);
    jQuery.ajax({
        url: prestashop.urls.base_url + '/modules/pakkelabels_shipping/ajax.php',
        type: 'POST',
        data: {
            'method': 'ajaxGetShopList',
            'sShippinAgent': shipping_agent,
            'iZipcode': zipcode,
            'iAddress': address
        },
        success: function(response) {
            jQuery('#pakkelabels_find_shop_btn span').addClass('caret');
            jQuery('#pakkelabels_find_shop_btn').addClass('dropdown-toggle');
            jQuery('#pakkelabels_find_shop_btn span').html('');
            jQuery('#Pakkelabels_zipcode_field').prop("disabled", false);
            jQuery('#pakkelabels_find_shop_btn').prop("disabled", false);
            if (response) {
                var returned = JSON.parse(response);
                if (returned.status == false) {
                    gotError = returned.error;
                    $(".error_msg").html(returned.error);
                    jQuery(".loading_radio").hide();
                    return false;
                } else if (returned.status = true) {
                    if (returned.frontendoption == 'dropdown') {
                        setTimeout(function() {
                            jQuery('.pakkelabels-shoplist').append(returned.shoplist);
                            jQuery('.pakkelabels-shoplist').addClass('open');
                        }, 1000)
                    } else if (returned.frontendoption == 'radio') {
                        setTimeout(function() {
                            jQuery('.pakkelabels-shoplist').append(returned.shoplist);
                            jQuery('.pakkelabels-shoplist').addClass('open');
                            jQuery(".loading_radio").hide();
                        }, 1000)
                    } else {
                        jQuery('#pakkelabel-modal').modal({
                            show: true,
                            backdrop: true,
                        });
                        jQuery('#pakkelabel-map-wrapper').html(returned.map);
                        jQuery('#pakkelabel-list-wrapper').html(returned.shoplist);
                        jQuery('#pakkelabels-hidden-shop').html(returned.hidden_pakkelabels);
                        markerFile = returned.shoplist_json;
                        undefined_cords_markerFile = new Array();

                        for (var key in markerFile) {
                            if (!markerFile[key].hasOwnProperty('latitude') || !markerFile[key].hasOwnProperty('longitude')) {
                                undefined_cords_markerFile[key] = markerFile[key];
                                delete markerFile[key];
                            }
                        }

                        //loads the map and other map related stuff
                        loadMap(loadmarkers, markerFile);

                        //checks if their is any markers, that have no lng or lat that needs to be loaded
                        if (Object.keys(undefined_cords_markerFile).length > 0) {
                            load_markers_without_cords_from_streetname(undefined_cords_markerFile)
                        }

                        setTimeout(function() {
                            google.maps.event.trigger(map, 'resize');
                            map.fitBounds(bounds);
                        }, 1000);
                    }
                }
            } else {
                jQuery('#Pakkelabels_zipcode_field').prop("disabled", false);
                jQuery('#Pakkelabels_address_field').prop("disabled", false);
                jQuery('#pakkelabels_find_shop_btn').prop("disabled", false);
                $(".error_msg").html(returned.error);
            }
        }
    });
}
/** Roohi**/
function saveCartdetails() {
    if (jQuery('#selected_shop_context').children().size() != 0) {
        var sCompany_name = jQuery('#selected_shop_context > .pakkelabels-company-name').text();
        var sPacketshop_id = jQuery('#selected_shop_context > .pakkelabels-Packetshop').text();
        var sAdress = jQuery('#selected_shop_context > .pakkelabels-Address').text();
        var sCity = jQuery('#selected_shop_context > .pakkelabels-ZipAndCity > .pakkelabels-city').text();
        var iZipcode = jQuery('#selected_shop_context > .pakkelabels-ZipAndCity > .pakkelabels-zipcode').text();

        jQuery.ajax({
            url: prestashop.urls.base_url + '/modules/pakkelabels_shipping/ajax.php',
            type: 'POST',
            data: {
                'method': "ajaxTempCartAddress",
                'sCompany_name': sCompany_name,
                'sPacketshop_id': sPacketshop_id,
                'sAdress': sAdress,
                'sCity': sCity,
                'iZipcode': iZipcode
            },
            dataType: 'json',
            error: function(response) {
                // Error
            },
            success: function(response) {
                if (response.status == "success") {
                    // Success
                } else if (response.status == "error") {
                    /** Roohi**/
                    $(".error_msg").html(error_no_shop_selected);
                    return false;
                }

            }
        });
    }
}

//Calls googles gmap api, and gets the cords for the streetnames from the shopilst generated by pakkelabels
function load_markers_without_cords_from_streetname(aMarkerFile) {
    var geocoder = new google.maps.Geocoder();
    jQuery(aMarkerFile).each(function(key) {
        var address = this.address + ", " + this.city + ", " + this.zipcode
        var iShopid = this.number
        geocoder.geocode({
            'address': address
        }, function(results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
                aMarkerFile[key].latitude = results[0].geometry.location.lat() + "";
                aMarkerFile[key].longitude = results[0].geometry.location.lng() + "";
                loadMarker(aMarkerFile[key]);

            } else {
                jQuery('[data-shopid="' + iShopid + '"] > div').append('<div class="no_cords_found">' + error_no_cords_found + '</div>');
            }
        })
    })
    return aMarkerFile;
}

//loads the map and other map related stuff
function loadMap(callback, markerfile) {
    var defaultLatlng = new google.maps.LatLng(55.9150835, 10.4713954); // Set default map properties
    var myOptions = {
        zoom: defaultZoom,
        center: defaultLatlng,
        maxZoom: defaultMaxZoom,
        mapTypeId: google.maps.MapTypeId.Road
    }; // Option for google map object
    // Create new map and place it in the target DIV
    map = new google.maps.Map(document.getElementById("map-canvas"), myOptions);
    // Create new info window for marker detail pop-up
    infowindow = new google.maps.InfoWindow();
    bounds = new google.maps.LatLngBounds();

    if (callback && markerfile) {
        callback(markerfile);
    }
}

function loadmarkers(markerFile) {
    //loads any markers that already have cords
    if (Object.keys(markerFile).length >= 1) {
        jQuery(markerFile).each(function() {
            loadMarker(this);
        });
    }
}

//loades a single marker (used by loaderMarkers())
function loadMarker(markerData) {
    // Create new marker location
    var myLatlng = new google.maps.LatLng(markerData['latitude'], markerData['longitude']);

    // Create new marker
    var marker = new google.maps.Marker({
        map: map,
        position: myLatlng,
        icon: dataRoot + '/views/img/' + markerIcon
    });

    // Add information to the marker
    google.maps.event.addListener(marker, 'click', (function(marker) {
        return function() {
            infowindow.setContent("<strong>" + markerData['company_name'] + "</strong><br/>" + markerData['address'] + "<br/> " + markerData['city'] + " <br/> " + markerData['zipcode']);
            infowindow.open(map, marker);
            var shop_list = jQuery('.pakkelabels-shoplist > ul >li');
            shop_list.removeClass('selected').filter('[data-shopid=' + markerData['number'] + ']').trigger('click').addClass('selected');
            jQuery('#shop_radio_' + markerData['number']).trigger('click');

            //adds the shop information to the #selected_shop div
            jQuery('#selected_shop_header').html(selected_shop_header);
            jQuery('#selected_shop_wrapper').addClass("add_border");
        }
    })(marker));
    bounds.extend(marker.position);
    //adds a marker to the list of markers
    ms_marker_list[markerData['number']] = marker;
}

//When a LI with a shop is pressed, the assosiated marker will have its informationwindow opened
function checkdroppointselected(eventElement) {
    // Show continue button
    jQuery('#btn_place_order').show();
    jQuery('.choose-pickuppoint').hide();
}

function li_addlistener_open_marker(eventElement) {
    var event = eventElement;
    jQuery.each(ms_marker_list, function(key, value) {
        if (key == event['context'].getAttribute('data-shopid')) {
            jQuery('#hidden_choosen_shop').attr('shopid', event['context'].getAttribute('data-shopid'));
            //adds the shop information to the #selected_shop div
            jQuery('#selected_shop_header').html(selected_shop_header);
            jQuery('#selected_shop_wrapper').addClass("add_border");
            jQuery('#selected_shop_context').html(eventElement['context']['childNodes'][1].innerHTML);

            // Show continue button
            jQuery('#btn_place_order').show();
            jQuery('.choose-pickuppoint').hide();

            //adds the shop information to the marker corresponding with the shop
            infowindow.setContent(eventElement['context']['childNodes'][1].innerHTML);
            infowindow.open(map, value);
        }
    });
}

/* jQuery(window).on('load', function() { */
jQuery(document).ready(function() {


    //html to be injected into the prestashop
    var sModalHTML = '<div class="pakkelabel-modal fade-pakkelabel" id="pakkelabel-modal" tabindex="-1" role="dialog" aria-labelledby="packetshop window"> <div class="pakkelabel-modal-dialog" role="document"> <div class="pakkelabel-modal-content"> <div class="pakkelabel-modal-header"> <h4 class="pakkelabel-modal-title" id="pakkelabel-modal-header-h4">' + sPakkelabel_modal_header_h4 + '</h4> <button id="pakkelabel-modal-header-button"type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> <div class="pakkelabel-open-close-button-wrap"> <div class="pakkelabel-open-close-button pakkelabel-open-map">' + sPakkelabel_open_map + '</div> <div class="pakkelabel-open-close-button pakkelabel-hide-map">' + sPakkelabel_hide_map + '</div> </div></div> <div class="pakkelabel-modal-body"> <div id="pakkelabel-map-wrapper"></div> <div id="pakkelabel-list-wrapper"></div> </div> <div class="pakkelabel-modal-footer"> <button id="choose-stop-btn" type="button" class="button btn btn-default button-medium" data-dismiss="modal">' + sChoose_stop_btn + '</button> <div class="powered-by-pakkelabels">Powered by</div> </div> </div> </div> </div>';
    /** Roohi**/
    if (iPakkelabels_ID_WINDOW == 'Popup') {
        var sZipcodeHTML = '<div class="pakkelabels_shipping_field-wrap" id="pakkelabels-zipcode-wrapper"> <div class="pakkelabels_shipping_field"> <div class="pakkelabels-clearfix" id="pakkelabels_shipping_button"> <div class="pakkelabels_stores"> <div> <div class="error_msg"></div><input type="hidden" id="Pakkelabels_zipcode_field" class="input" name="pakkelabels_zipcode" placeholder="' + sPakkelabels_zipcode_field + '"> <input type="hidden" id="Pakkelabels_address_field" class="input" name="pakkelabels_address" placeholder="' + sPakkelabels_address_field + '"> </div> <div> <input type="button" id="pakkelabels_find_shop_btn" name="pakkelabel_find_shop" class="button btn btn-default button-small" value="' + sPakkelabels_find_shop_btn_text + '"> </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="pakkelabels-clearfix" id="selected_shop_wrapper"> <div id="pakkelabels-hidden-shop"> </div> <div class="pakkelabels-clearfix" id="selected_shop_header">' + sSelected_shop_header + '</div> <div class="pakkelabels-clearfix" id="selected_shop_context"> </div> </div> </div> </div>';
    } else if (iPakkelabels_ID_WINDOW == 'radio') {
        var sZipcodeHTML = '<div class="pakkelabels_shipping_field-wrap" id="pakkelabels-zipcode-wrapper"> <div class="pakkelabels_shipping_field"> <div class="pakkelabels-clearfix" id="pakkelabels_shipping_button"> <div class="pakkelabels_stores"> <div><span><img src="' + prestashop.urls.base_url + '/modules/pakkelabels_shipping/views/img/loadiing.gif" class="loading_radio" style="display:none;"></span><div class="error_msg"></div> <input type="hidden" id="Pakkelabels_zipcode_field" class="input" name="pakkelabels_zipcode" placeholder="' + sPakkelabels_zipcode_field + '"><input type="hidden" id="Pakkelabels_address_field" class="input" name="pakkelabels_address" placeholder="' + sPakkelabels_address_field + '"></div> <div class="pakkelabels-shoplist"> <!--input type="button" id="pakkelabels_find_shop_btn" name="pakkelabel_find_shop" class="button btn btn-default button-small" value="' + sPakkelabels_find_shop_btn_text + '"--> </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="pakkelabels-clearfix" id="selected_shop_wrapper" style="display:none;"> <div id="pakkelabels-hidden-shop"> </div> <div class="pakkelabels-clearfix" id="selected_shop_header">' + sSelected_shop_header + '</div> <div class="pakkelabels-clearfix" id="selected_shop_context"> </div> </div> </div> </div>';
    } else {
        var sZipcodeHTML = '<div class="pakkelabels_shipping_field-wrap" id="pakkelabels-zipcode-wrapper"> <div class="pakkelabels_shipping_field"> <div class="pakkelabels-clearfix" id="pakkelabels_shipping_button"> <div class="pakkelabels_stores"> <div> <div class="error_msg"></div><input type="hidden" id="Pakkelabels_zipcode_field" class="input" name="pakkelabels_zipcode" placeholder="' + sPakkelabels_zipcode_field + '"> <input type="hidden" id="Pakkelabels_address_field" class="input" name="pakkelabels_address" placeholder="' + sPakkelabels_address_field + '"> </div> <div class="pakkelabels-shoplist dropdown"> <button class="button button-medium btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' + sPakkelabels_find_shop_btn_text + '<span class="caret"></span></button> </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="pakkelabels-clearfix" id="selected_shop_wrapper"> <div id="pakkelabels-hidden-shop"> </div> <div class="pakkelabels-clearfix" id="selected_shop_header">' + sSelected_shop_header + '</div> <div class="pakkelabels-clearfix" id="selected_shop_context"> </div> </div> </div> </div>';
    }
    /** Roohi code end **/

    //appends the modal to the body of the prestashop checkout page
    jQuery('body').append(sModalHTML);



    //Event fired when the find nearest shop is pressed
    jQuery(document).on('click', '#pakkelabels_find_shop_btn', function() {

        /** Roohi**/
        jQuery(".error_msg").html('');
        if (jQuery("input[name='delivery_postcode']").val() != '') {
            jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
        }
        if (jQuery("input[name='delivery_address1']").val() != '') {
            jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
        }
        /** Roohi code end **/
        var iPakkelabels_choosen_delivery_option = jQuery('.delivery-option input:checked').val();

        iPakkelabels_choosen_delivery_option = iPakkelabels_choosen_delivery_option.replace(/\D/g, '');
        if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_GLS) {
            sChoosenShippingAgent = 'gls';
        } else if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_DAO) {
            sChoosenShippingAgent = 'dao';
        } else if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_POSTNORD) {
            sChoosenShippingAgent = 'pdk';
        } else if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_BRING) {
            sChoosenShippingAgent = 'bring';
        }

        defaultZipcode = jQuery('#Pakkelabels_zipcode_field').val();
        defaultAddress = jQuery('#Pakkelabels_address_field').val(); /** Roohi**/

        getShopList(sChoosenShippingAgent, jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val()); /** Roohi**/
    })

    jQuery(document).on('keypress', '#Pakkelabels_zipcode_field', function(event) {
        if (event.keyCode == 13) {
            event.preventDefault();
            if (!jQuery('#pakkelabels_find_shop_btn').is(":disabled")) {
                var iPakkelabels_choosen_delivery_option = jQuery('.delivery-option input:checked').val();
                iPakkelabels_choosen_delivery_option = iPakkelabels_choosen_delivery_option.replace(/\D/g, '');
                if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_GLS) {
                    sChoosenShippingAgent = 'gls';
                } else if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_DAO) {
                    sChoosenShippingAgent = 'dao';
                } else if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_POSTNORD) {
                    sChoosenShippingAgent = 'pdk';
                } else if (iPakkelabels_choosen_delivery_option == iPakkelabels_ID_BRING) {
                    sChoosenShippingAgent = 'bring';
                }

                defaultZipcode = jQuery('#Pakkelabels_zipcode_field').val();

                getShopList(sChoosenShippingAgent, jQuery('#Pakkelabels_zipcode_field').val());

                jQuery('#Pakkelabels_zipcode_field').blur();
            }
        }
    })

    // Add Prevent continue button
    jQuery('#buttons_footer_review div.col-12').append('<button type="button" class="btn btn-primary btn-lg pull-right choose-pickuppoint" style="display:none;">' + sPakkelabel_modal_header_h4 + '</button>');

    //if a pakkelabels.dk shipping method choosen on pageload, add the zipcode div
    if (jQuery('.delivery-option input:checked').val() == iPakkelabels_ID_GLS + "," || jQuery('.delivery-option input:checked').val() == iPakkelabels_ID_POSTNORD + "," || jQuery('.delivery-option input:checked').val() == iPakkelabels_ID_DAO + "," || jQuery('.delivery-option input:checked').val() == iPakkelabels_ID_BRING + ",") {

        if (jQuery())
            // Remove zipcode wrapper
            jQuery('#pakkelabels-zipcode-wrapper').remove();


        jQuery(".error_msg").html('');
        if (jQuery("input[name='delivery_postcode']").val() != '') {
            jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
        }
        if (jQuery("input[name='delivery_address1']").val() != '') {
            jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
        }

        defaultZipcode = jQuery('#Pakkelabels_zipcode_field').val();
        defaultAddress = jQuery('#Pakkelabels_address_field').val();

        // Find nearest delivery option
        var dev_option = jQuery('.delivery-option input:checked').closest('.delivery-option');
        var extra_content = jQuery(dev_option).find('.carrier-extra-content');

        if (jQuery(extra_content).length < 1) {
            extra_content = jQuery(dev_option).next('.carrier-extra-content');
        }

        jQuery(extra_content).delay(1000).html(sZipcodeHTML);
        jQuery('#btn_place_order').hide();
        jQuery('.choose-pickuppoint').show();

        // Add zipcode from customer address
        if (defaultZipcode) {
            jQuery('#Pakkelabels_zipcode_field').val(defaultZipcode);
        }
        /** Roohi**/
        if (defaultAddress) {
            jQuery('#Pakkelabels_address_field').val(defaultAddress);
        }
        if (defaultAddress == '' && defaultZipcode == '') {
            var id_delivery = prestashop.cart.id_address_delivery;
            if (!id_delivery) {
                var id_delivery = jQuery('input[name="id_address_delivery"]').val();
            }
            var address_data = prestashop.customer.addresses[id_delivery];
            if (address_data) {
                jQuery('#Pakkelabels_zipcode_field').val(address_data.postcode);
                jQuery('#Pakkelabels_address_field').val(address_data.address1);
            } else {
                jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
                jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
            }
        }
        if (iPakkelabels_ID_WINDOW == 'radio') {
            if (jQuery('.delivery-option input:checked').val() == iPakkelabels_ID_GLS + ",") {
                getShopList('gls', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
            }
            if (jQuery('.delivery-option input:checked').val() == iPakkelabels_ID_DAO + ",") {
                getShopList('dao', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
            }
            if (jQuery('.delivery-option input:checked').val() == iPakkelabels_ID_POSTNORD + ",") {
                getShopList('pdk', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
            }
            if (jQuery('.delivery-option input:checked').val() == iPakkelabels_ID_BRING + ",") {
                getShopList('bring', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
            }
        }
        /** Roohi end code **/
    }

    jQuery(document).on('keypress', function(event) {
        if (jQuery('.pakkelabels-shop-list').hasClass('selected') && event.keyCode == 13 && jQuery('#pakkelabel-modal:visible').length != 0) {
            jQuery('#choose-stop-btn').trigger("click");
            jQuery('#choose-stop-btn').blur();
        }
    });

    jQuery(document).ajaxComplete(function(event, xhr, settings) {
        var checked_shipping = jQuery('.delivery-option input[type="radio"]:checked').val();
        if (checked_shipping) {
            // remove comma from shipping value
            checked_shipping = checked_shipping.replace(',', '');

            if (checked_shipping == iPakkelabels_ID_GLS || checked_shipping == iPakkelabels_ID_DAO || checked_shipping == iPakkelabels_ID_POSTNORD || checked_shipping == iPakkelabels_ID_BRING) {
                jQuery('.carrier-extra-content').show();
            }
        }

    });

    jQuery(document).on('click', '.delivery-option input[type="radio"]:not([value="' + iPakkelabels_ID_GLS + ',"]):not([value="' + iPakkelabels_ID_DAO + ',"]):not([value="' + iPakkelabels_ID_BRING + ',"]):not([value="' + iPakkelabels_ID_POSTNORD + ',"])', function() {
        console.log("ssd");
        jQuery('#pakkelabels-zipcode-wrapper').remove();
        jQuery('button[name="processCarrier"]').prop("disabled", false);
        jQuery('#btn_place_order').show();
        jQuery('.choose-pickuppoint').hide();
        jQuery('#selected_shop_wrapper').removeClass("add_border");

    });


    jQuery(document).on('click', '.delivery-option input[value="' + iPakkelabels_ID_GLS + ',"]', function() {
        console.log(jQuery("input[name='delivery_postcode']").val());

        // Remove zipcode wrapper
        jQuery('#pakkelabels-zipcode-wrapper').remove();

        // Find nearest delivery option
        var dev_option = jQuery('.delivery-option input:checked').closest('.delivery-option');
        var extra_content = jQuery(dev_option).find('.carrier-extra-content');

        if (jQuery(extra_content).length < 1) {
            extra_content = jQuery(dev_option).next('.carrier-extra-content');
        }

        jQuery(extra_content).html(sZipcodeHTML);
        jQuery(extra_content).show();
        jQuery('#btn_place_order').hide();
        jQuery('.choose-pickuppoint').show();
        jQuery('.carrier-extra-content').show();

        /** Roohi**/
        if (jQuery(".choose-pickuppoint").length <= 0)
            jQuery('#buttons_footer_review div.col-12').append('<button type="button" class="btn btn-primary btn-lg pull-right choose-pickuppoint" style="display:none;">' + sPakkelabel_modal_header_h4 + '</button>');
        jQuery(".error_msg").html('');
        if (jQuery("input[name='delivery_postcode']").val() != '') {
            jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
        }
        if (jQuery("input[name='delivery_address1']").val() != '') {
            jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
        }
        defaultZipcode = jQuery('#Pakkelabels_zipcode_field').val();
        defaultAddress = jQuery('#Pakkelabels_address_field').val();
        /** Roohi end code **/




        // Add zipcode from customer address
        if (defaultZipcode) {
            jQuery('#Pakkelabels_zipcode_field').val(defaultZipcode);
        }
        /** Roohi**/
        if (defaultAddress) {
            jQuery('#Pakkelabels_address_field').val(defaultAddress);
        }
        if (defaultAddress == '' && defaultZipcode == '') {
            var id_delivery = prestashop.cart.id_address_delivery;
            if (!id_delivery) {
                var id_delivery = jQuery('input[name="id_address_delivery"]').val();
            }
            var j = 0;
            var k = 0;
            $(prestashop.customer.addresses).each(function(i, v) {

                for (var i in prestashop.customer.addresses) {
                    if (j == 0) {
                        var id_delivery = prestashop.customer.addresses[i];
                        k = i;
                    }
                    j++;
                }

            });
            var address_data = prestashop.customer.addresses[k];
            if (address_data) {
                jQuery('#Pakkelabels_zipcode_field').val(address_data.postcode);
                jQuery('#Pakkelabels_address_field').val(address_data.address1);
            } else {
                jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
                jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
            }
        }
        if (iPakkelabels_ID_WINDOW == 'radio') {
            jQuery(".loading_radio").show();
            getShopList('gls', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
        }
        /** Roohi code end **/

    });

    jQuery(document).on('click', '.delivery-option input[value="' + iPakkelabels_ID_DAO + ',"]', function() {
        /** Roohi**/
        if (jQuery(".choose-pickuppoint").length <= 0)
            jQuery('#buttons_footer_review div.col-12').append('<button type="button" class="btn btn-primary btn-lg pull-right choose-pickuppoint" style="display:none;">' + sPakkelabel_modal_header_h4 + '</button>');



        /** Roohi code end **/
        // Remove zipcode wrapper
        jQuery('#pakkelabels-zipcode-wrapper').remove();

        // Find nearest delivery option
        var dev_option = jQuery('.delivery-option input:checked').closest('.delivery-option');
        var extra_content = jQuery(dev_option).find('.carrier-extra-content');

        if (jQuery(extra_content).length < 1) {
            extra_content = jQuery(dev_option).next('.carrier-extra-content');
        }

        jQuery(extra_content).html(sZipcodeHTML);
        jQuery(extra_content).show();
        jQuery('#btn_place_order').hide();
        jQuery('.choose-pickuppoint').show();
        jQuery('.carrier-extra-content').show();
        jQuery(".error_msg").html('');
        if (jQuery("input[name='delivery_postcode']").val() != '') {
            jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
        }
        if (jQuery("input[name='delivery_address1']").val() != '') {
            jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
        }
        defaultZipcode = jQuery('#Pakkelabels_zipcode_field').val();
        defaultAddress = jQuery('#Pakkelabels_address_field').val();

        // Add zipcode from customer address
        if (defaultZipcode) {
            jQuery('#Pakkelabels_zipcode_field').val(defaultZipcode);
        }
        /** Roohi**/
        if (defaultAddress) {
            jQuery('#Pakkelabels_address_field').val(defaultAddress);
        }
        if (defaultAddress == '' && defaultZipcode == '') {
            var id_delivery = prestashop.cart.id_address_delivery;
            if (!id_delivery) {
                var id_delivery = jQuery('input[name="id_address_delivery"]').val();
            }
            var j = 0;
            var k = 0;
            $(prestashop.customer.addresses).each(function(i, v) {

                for (var i in prestashop.customer.addresses) {
                    if (j == 0) {
                        var id_delivery = prestashop.customer.addresses[i];
                        k = i;
                    }
                    j++;
                }

            });
            var address_data = prestashop.customer.addresses[k];
            if (address_data) {
                jQuery('#Pakkelabels_zipcode_field').val(address_data.postcode);
                jQuery('#Pakkelabels_address_field').val(address_data.address1);
            } else {
                jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
                jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
            }
        }
        if (iPakkelabels_ID_WINDOW == 'radio') {
            jQuery(".loading_radio").show();
            getShopList('dao', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
        }
        /** Roohi code end **/
    });

    jQuery(document).on('click', '.delivery-option input[value="' + iPakkelabels_ID_POSTNORD + ',"]', function() {

        /** Roohi**/
        if (jQuery(".choose-pickuppoint").length <= 0)
            jQuery('#buttons_footer_review div.col-12').append('<button type="button" class="btn btn-primary btn-lg pull-right choose-pickuppoint" style="display:none;">' + sPakkelabel_modal_header_h4 + '</button>');

        /** Roohi code end **/
        // Remove zipcode wrapper
        jQuery('#pakkelabels-zipcode-wrapper').remove();

        // Find nearest delivery option
        var dev_option = jQuery('.delivery-option input:checked').closest('.delivery-option');
        var extra_content = jQuery(dev_option).find('.carrier-extra-content');

        if (jQuery(extra_content).length < 1) {
            extra_content = jQuery(dev_option).next('.carrier-extra-content');
        }

        jQuery(extra_content).html(sZipcodeHTML);
        jQuery(extra_content).show();
        jQuery('#btn_place_order').hide();
        jQuery('.choose-pickuppoint').show();
        jQuery('.carrier-extra-content').show();

        jQuery(".error_msg").html('');
        if (jQuery("input[name='delivery_postcode']").val() != '') {
            jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
        }
        if (jQuery("input[name='delivery_address1']").val() != '') {
            jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
        }
        defaultZipcode = jQuery('#Pakkelabels_zipcode_field').val();
        defaultAddress = jQuery('#Pakkelabels_address_field').val();


        // Add zipcode from customer address
        if (defaultZipcode) {
            jQuery('#Pakkelabels_zipcode_field').val(defaultZipcode);
        }
        /** Roohi**/
        if (defaultAddress) {
            jQuery('#Pakkelabels_address_field').val(defaultAddress);
        }
        if (defaultAddress == '' && defaultZipcode == '') {
            var id_delivery = prestashop.cart.id_address_delivery;
            if (!id_delivery) {
                var id_delivery = jQuery('input[name="id_address_delivery"]').val();
            }
            var j = 0;
            var k = 0;
            $(prestashop.customer.addresses).each(function(i, v) {

                for (var i in prestashop.customer.addresses) {
                    if (j == 0) {
                        var id_delivery = prestashop.customer.addresses[i];
                        k = i;
                    }
                    j++;
                }

            });
            var address_data = prestashop.customer.addresses[k];
            if (address_data) {
                jQuery('#Pakkelabels_zipcode_field').val(address_data.postcode);
                jQuery('#Pakkelabels_address_field').val(address_data.address1);
            } else {
                jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
                jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
            }
        }
        if (iPakkelabels_ID_WINDOW == 'radio') {
            jQuery(".loading_radio").show();
            getShopList('pdk', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
        }
        /** Roohi code end **/
    });

    jQuery(document).on('click', '.delivery-option input[value="' + iPakkelabels_ID_BRING + ',"]', function() {

        /** Roohi**/
        if (jQuery(".choose-pickuppoint").length <= 0)
            jQuery('#buttons_footer_review div.col-12').append('<button type="button" class="btn btn-primary btn-lg pull-right choose-pickuppoint" style="display:none;">' + sPakkelabel_modal_header_h4 + '</button>');

        /** Roohi code end**/
        // Remove zipcode wrapper
        jQuery('#pakkelabels-zipcode-wrapper').remove();

        // Find nearest delivery option
        var dev_option = jQuery('.delivery-option input:checked').closest('.delivery-option');
        var extra_content = jQuery(dev_option).find('.carrier-extra-content');

        if (jQuery(extra_content).length < 1) {
            extra_content = jQuery(dev_option).next('.carrier-extra-content');
        }

        jQuery(extra_content).html(sZipcodeHTML);
        jQuery(extra_content).show();
        jQuery('#btn_place_order').hide();
        jQuery('.choose-pickuppoint').show();
        jQuery('.carrier-extra-content').show();

        jQuery(".error_msg").html('');
        if (jQuery("input[name='delivery_postcode']").val() != '') {
            jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
        }
        if (jQuery("input[name='delivery_address1']").val() != '') {
            jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
        }
        defaultZipcode = jQuery('#Pakkelabels_zipcode_field').val();
        defaultAddress = jQuery('#Pakkelabels_address_field').val();

        // Add zipcode from customer address
        if (defaultZipcode) {
            jQuery('#Pakkelabels_zipcode_field').val(defaultZipcode);
        }
        /** Roohi**/
        if (defaultAddress) {
            jQuery('#Pakkelabels_address_field').val(defaultAddress);
        }
        if (defaultAddress == '' && defaultZipcode == '') {
            var id_delivery = prestashop.cart.id_address_delivery;
            if (!id_delivery) {
                var id_delivery = jQuery('input[name="id_address_delivery"]').val();
            }
            var address_data = prestashop.customer.addresses[id_delivery];
            if (address_data) {
                jQuery('#Pakkelabels_zipcode_field').val(address_data.postcode);
                jQuery('#Pakkelabels_address_field').val(address_data.address1);
            } else {
                jQuery('#Pakkelabels_zipcode_field').val(jQuery("input[name='delivery_postcode']").val());
                jQuery('#Pakkelabels_address_field').val(jQuery("input[name='delivery_address1']").val());
            }
        }
        if (iPakkelabels_ID_WINDOW == 'radio') {
            jQuery(".loading_radio").show();
            getShopList('bring', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
        }
        /** Roohi code end **/
    });

    //shows map
    jQuery('.pakkelabel-open-map').on('click', function() {
        jQuery('.pakkelabel-hide-map').show();
        jQuery('.pakkelabel-open-map').hide();
        jQuery('#pakkelabel-map-wrapper').show();
        google.maps.event.trigger(map, 'resize');
        map.fitBounds(bounds);
    })

    //hide map
    jQuery('.pakkelabel-hide-map').on('click', function() {
        jQuery('.pakkelabel-hide-map').hide();
        jQuery('.pakkelabel-open-map').show();
        jQuery('#pakkelabel-map-wrapper').hide();
    })

    jQuery('#pakkelabel-modal').on('show.bs.modal', function(e) {
        jQuery('body').toggleClass('pakkelabels-modal-shown');
        jQuery('.pakkelabel-modal-body').scrollTop(0);
    });


    jQuery('#pakkelabel-modal').on('hidden.bs.modal', function(e) {
        jQuery('body').toggleClass('pakkelabels-modal-shown');
    });

    //Makes sure a shop is selected when the stuff is picked
    jQuery('button[name="processCarrier"]').on('click', function(e) {
        // Prevent submission
        //e.preventDefault();

        if (jQuery('input.delivery_option_radio[value="' + iPakkelabels_ID_GLS + ',"]').is(':checked') ||
            jQuery('input.delivery_option_radio[value="' + iPakkelabels_ID_DAO + ',"]').is(':checked') ||
            jQuery('input.delivery_option_radio[value="' + iPakkelabels_ID_POSTNORD + ',"]').is(':checked') ||
            jQuery('input.delivery_option_radio[value="' + iPakkelabels_ID_BRING + ',"]').is(':checked')) {
            if (jQuery('#selected_shop_context').children().size() == 0) {
                e.preventDefault()
                $(".error_msg").html(error_no_shop_selected)
                // }else{
                //     // Submit form if everything is ok
                //     jQuery('#form').submit();
            }
        }
    });

    jQuery('#cgv').on('click', function() {
        jQuery.ajax({
            url: prestashop.urls.base_url + '/modules/pakkelabels_shipping/ajax.php',
            type: 'POST',
            data: 'method=getCart',
            dataType: 'json',
            success: function(response) {

            }

        });
    });

    //Sets the choosen shipping address when modal closes
    jQuery('#pakkelabel-modal').on('hidden.bs.modal', function(e) {
        saveCartdetails();
    })


    //adds 3 events to the zipcode text field, that will disable the "find shop button", until a zipcode thats 4 in lentgh % numeric is choosen!
    jQuery(document).on('keyup focusout input change', '#Pakkelabels_zipcode_field', function(e) {
        if (jQuery('#Pakkelabels_zipcode_field').val().length > 0) {
            jQuery('#pakkelabels_find_shop_btn').prop("disabled", false);
        } else {
            jQuery('#pakkelabels_find_shop_btn').prop("disabled", true);
        }
    });
    jQuery(document).on('click', '.choose-pickuppoint', function() {

        /** Roohi ***/
        var checked_shipping = jQuery('.delivery-option input:checked').val();

        if (iPakkelabels_ID_WINDOW == 'radio') {
            if (checked_shipping == iPakkelabels_ID_GLS + ",") {
                getShopList('gls', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
            }
            if (checked_shipping == iPakkelabels_ID_DAO + ",") {
                getShopList('dao', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
            }
            if (checked_shipping == iPakkelabels_ID_POSTNORD + ",") {
                getShopList('pdk', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
            }
            if (checked_shipping == iPakkelabels_ID_BRING + ",") {
                getShopList('bring', jQuery('#Pakkelabels_zipcode_field').val(), jQuery('#Pakkelabels_address_field').val());
            }
        } else {
            jQuery("#pakkelabels_find_shop_btn").trigger("click");
        }
        $("body,html").animate({
                scrollTop: $(".pakkelabels_stores").offset().top
            },
            800 //speed
        );
        /** Roohi code ends ***/

    });
});