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
var defaultAddress = '';

/** Roohi**/
function getShopList(shipping_agent, zipcode, address) {
    var myZipcode = zipcode;

    if (usedZipCode == zipcode && usedAgent == shipping_agent) {
        if (gotError !== '') {
            $(".error_msg").html(gotError);
            return false;
        }
        jQuery('#shipmondo_zipcode_field').removeAttr("disabled");
        jQuery('#shipmondo_find_shop_btn').removeAttr("disabled");
        if (frontendType == 'Popup') {
            jQuery('#shipmondo-modal').modal({
                show: true,
                backdrop: true,
            });
        }
        return true;
    } else {
        jQuery('.shipmondo-shoplist-dropdownul').remove();
        //usedZipCode = zipcode;
        //usedAgent = shipping_agent;
        gotError = '';
    }

    markerIcon = shipping_agent + '.png';
    laoding = '<img src="' + prestashop.urls.base_url + '/modules/shipmondo/views/img/loading.gif" class="loading_drop">';
    jQuery('#shipmondo_find_shop_btn span').removeClass('caret');
    jQuery('#shipmondo_find_shop_btn').removeClass('dropdown-toggle');
    jQuery('#shipmondo_find_shop_btn span').html(laoding);
    jQuery.ajax({
        url: servicePointsEndpoint,
        type: 'POST',
        data: {
            'method': 'get_list',
            'shipping_agent': shipping_agent,
            'zip_code': zipcode,
            'address': address
        },
        success: function(response) {
            jQuery('#shipmondo_find_shop_btn span').addClass('caret');
            jQuery('#shipmondo_find_shop_btn').addClass('dropdown-toggle');
            jQuery('#shipmondo_find_shop_btn span').html('');
            jQuery('#shipmondo_zipcode_field').prop("disabled", false);
            jQuery('#shipmondo_find_shop_btn').prop("disabled", false);
            if (response) {
                var returned = JSON.parse(response);
                if (returned.status == 'success') {
                    if (returned.frontend_type == 'dropdown') {
                        setTimeout(function() {
                            jQuery('.shipmondo-shoplist').append(returned.service_points_html);
                            jQuery('.shipmondo-shoplist').addClass('open');
                        }, 1000)
                    } else if (returned.frontend_type == 'radio') {
                        setTimeout(function() {
                            jQuery(".loading_radio").hide();
                            jQuery('.shipmondo-shoplist').html(returned.service_points_html);
                            jQuery('.shipmondo-shoplist').addClass('open');
                        }, 1000)
                    } else {
                        jQuery('#shipmondo-modal').modal({
                            show: true,
                            backdrop: true,
                        });
                        jQuery('#shipmondo-map-wrapper').html(returned.map);
                        jQuery('#shipmondo-list-wrapper').html(returned.service_points_html);
                        jQuery('#shipmondo-hidden-shop').html(returned.hidden_shipmondo);
                        markerFile = returned.service_points;
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
                } else {
                    gotError = returned.error;
                    jQuery(".loading_radio").hide();
                    $(".error_msg").html(returned.error);
                }
            } else {
                jQuery('#shipmondo_zipcode_field').prop("disabled", false);
                jQuery('#shipmondo_find_shop_btn').prop("disabled", false);
                $(".error_msg").html(returned.error);
            }
        }
    });
}
/** Roohi code ends **/
function saveCartdetails() {
    if (jQuery('#selected_shop_context').children().size() != 0) {
        var chosenShippingAgent = getSelectedShippingAgent()

        var sCompany_name = jQuery('#selected_shop_context > .shipmondo-company-name').text().trim();
        var sPacketshop_id = jQuery('#selected_shop_context > .shipmondo-Packetshop').text().trim();
        var sAdress = jQuery('#selected_shop_context > .shipmondo-Address').text().trim();
        var sCity = jQuery('#selected_shop_context > .shipmondo-ZipAndCity > .shipmondo-city').text().trim();
        var iZipcode = jQuery('#selected_shop_context > .shipmondo-ZipAndCity > .shipmondo-zipcode').text().trim();

        jQuery.ajax({
            url: servicePointsEndpoint,
            type: 'POST',
            data: {
                'method': "save_address",
                'company_name': sCompany_name,
                'service_point_id': sPacketshop_id,
                'address': sAdress,
                'city': sCity,
                'zip_code': iZipcode,
                'shipping_agent': chosenShippingAgent
            },
            dataType: 'json',
            error: function(response) {
                // Error
            },
            success: function(response) {
                if (response.status == "success") {
                    // Success
                } else if (response.status == "error") {
                    $(".error_msg").html(noPointSelectedErrorText);
                }

            }
        });
    }
}

//Calls googles gmap api, and gets the cords for the streetnames from the shopilst generated by shipmondo
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
                jQuery('[data-shopid="' + iShopid + '"] > div').append('<div class="no_cords_found">' + noCoordinatesErrorText + '</div>');
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
        icon: moduleBaseUrl + '/views/img/' + markerIcon
    });

    // Add information to the marker
    google.maps.event.addListener(marker, 'click', (function(marker) {
        return function() {
            infowindow.setContent("<strong>" + markerData['company_name'] + "</strong><br/>" + markerData['address'] + "<br/> " + markerData['city'] + " <br/> " + markerData['zipcode']);
            infowindow.open(map, marker);
            var shop_list = jQuery('.shipmondo-shoplist > ul >li');
            shop_list.removeClass('selected').filter('[data-shopid=' + markerData['number'] + ']').trigger('click').addClass('selected');
            jQuery('#shop_radio_' + markerData['number']).trigger('click');

            //adds the shop information to the #selected_shop div
            jQuery('#selected_shop_header').html(selectedServicePointHeader);
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
    jQuery('#js-delivery .continue').show();
    jQuery('.choose-pickuppoint').hide();
}

function li_addlistener_open_marker(eventElement) {
    var event = eventElement;

    jQuery.each(ms_marker_list, function(key, value) {

        if (key == event['context'].getAttribute('data-shopid')) {
            jQuery('#hidden_choosen_shop').attr('shopid', event['context'].getAttribute('data-shopid'));
            //adds the shop information to the #selected_shop div
            jQuery('#selected_shop_header').html(selectedServicePointHeader);
            jQuery('#selected_shop_wrapper').addClass("add_border");
            jQuery('#selected_shop_context').html(eventElement['context']['childNodes'][1].innerHTML);

            // Show continue button
            jQuery('#js-delivery .continue').show();
            jQuery('.choose-pickuppoint').hide();

            //adds the shop information to the marker corresponding with the shop
            infowindow.setContent(eventElement['context']['childNodes'][1].innerHTML);
            infowindow.open(map, value);
        }
    });
}

function loadSelectedServicePoint() {
    var chosenShippingAgent = getSelectedShippingAgent()

    jQuery.ajax({
        url: servicePointsEndpoint,
        type: 'GET',
        data: {
            method: 'get_address',
            shipping_agent: chosenShippingAgent,
        },
        success: function(response) {
            response = JSON.parse(response);
            if(response['status'] == 'success') {
                var servicePoint = response['service_point']
                var servicePointHtml = 
                '<div class="shipmondo-company-name">' + servicePoint['company'] + '</div>' +
                '<div class="shipmondo-Address">' + servicePoint['address'] + '</div>' +
                '<div class="shipmondo-ZipAndCity">' +
                    '<span class="shipmondo-zipcode">' + servicePoint['zip_code'] + '</span>,' +
                    '<span class="shipmondo-city">' + servicePoint['city'] + '</span>' +
                '</div>' +
                '<div class="shipmondo-Packetshop" style="display: none;">' + servicePoint['address2'] + '</div>'

                var shopId = servicePoint['address2'].replace(/\D/g, '')
                $('#hidden_choosen_shop').attr('shopid', shopId);

                $('#selected_shop_header').html(selectedServicePointHeader);
                $('#selected_shop_context').html(servicePointHtml);

                if(typeof checkdroppointselected !== 'undefined')
                    checkdroppointselected(this);
            }
        }
    });
}

function getSelectedShippingAgent() {
    var carrierId = $('.delivery-option input:checked').val().replace(/\D/g, '');
    var shippingAgent = '';
    
    switch(parseInt(carrierId)) {
        case glsCarrierId:
            shippingAgent = 'gls';
            break;
        case daoCarrierId:
            shippingAgent = 'dao';
            break;
        case postnordCarrierId:
            shippingAgent = 'pdk';
            break;
        case bringCarrierId:
            shippingAgent = 'bring';
            break;
    }
    
    return shippingAgent;
}

jQuery(window).on('load', function() {

    //html to be injected into the prestashop
    var sModalHTML = '<div class="shipmondo-modal fade-shipmondo" id="shipmondo-modal" tabindex="-1" role="dialog" aria-labelledby="packetshop window"> <div class="shipmondo-modal-dialog" role="document"> <div class="shipmondo-modal-content"> <div class="shipmondo-modal-header"> <h4 class="shipmondo-modal-title" id="shipmondo-modal-header-h4">' + modalHeaderTitle + '</h4> <button id="shipmondo-modal-header-button"type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> <div class="shipmondo-open-close-button-wrap"> <div class="shipmondo-open-close-button shipmondo-open-map">' + showMapText + '</div> <div class="shipmondo-open-close-button shipmondo-hide-map">' + hideMapText + '</div> </div></div> <div class="shipmondo-modal-body"> <div id="shipmondo-map-wrapper"></div> <div id="shipmondo-list-wrapper"></div> </div> <div class="shipmondo-modal-footer"> <button id="choose-stop-btn" type="button" class="button btn btn-default button-medium" data-dismiss="modal">' + chooseServicePointText + '</button> <div class="powered-by-shipmondo">Powered by</div> </div> </div> </div> </div>';
    /** Roohi**/
    if (frontendType == 'Popup') {
        var sZipcodeHTML = '<div class="shipmondo_shipping_field-wrap" id="shipmondo-zipcode-wrapper"> <div class="shipmondo_shipping_field"> <div class="shipmondo-clearfix" id="shipmondo_shipping_button"> <div class="shipmondo_stores"> <div><div class="error_msg"></div> <input type="hidden" id="shipmondo_zipcode_field" class="input" name="shipmondo_zipcode" placeholder="' + zipCodeFieldText + '"><input type="hidden" id="shipmondo_address_field" class="input" name="shipmondo_address" placeholder="' + addressFieldText + '"></div> <div> <button class="button button-medium btn btn-primary dropdown-toggle" id="shipmondo_find_shop_btn" type="button" data-toggle="dropdown">' + findServicePointText + '</button> </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="shipmondo-clearfix" id="selected_shop_wrapper"> <div id="shipmondo-hidden-shop"> </div> <div class="shipmondo-clearfix" id="selected_shop_header"></div> <div class="shipmondo-clearfix" id="selected_shop_context"> </div> </div> </div> </div>';
    } else if (frontendType == 'radio') {
        var sZipcodeHTML = '<div class="shipmondo_shipping_field-wrap" id="shipmondo-zipcode-wrapper"> <div class="shipmondo_shipping_field"> <div class="shipmondo-clearfix" id="shipmondo_shipping_button"> <div class="shipmondo_stores"> <div> <span><img src="' + prestashop.urls.base_url + '/modules/shipmondo/views/img/loadiing.gif" class="loading_radio" style="display:none;"></span><div class="error_msg"></div><input type="hidden" id="shipmondo_zipcode_field" class="input" name="shipmondo_zipcode" placeholder="' + zipCodeFieldText + '"><input type="hidden" id="shipmondo_address_field" class="input" name="shipmondo_address" placeholder="' + addressFieldText + '"></div> <div class="shipmondo-shoplist"> </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="shipmondo-clearfix" id="selected_shop_wrapper" style="display:none;"> <div id="shipmondo-hidden-shop"> </div> <div class="shipmondo-clearfix" id="selected_shop_header"></div> <div class="shipmondo-clearfix" id="selected_shop_context"> </div> </div> </div> </div>';
    } else {
        var sZipcodeHTML = '<div class="shipmondo_shipping_field-wrap" id="shipmondo-zipcode-wrapper"> <div class="shipmondo_shipping_field"> <div class="shipmondo-clearfix" id="shipmondo_shipping_button"> <div class="shipmondo_stores"> <div> <div class="error_msg"></div><input type="hidden" id="shipmondo_zipcode_field" class="input" name="shipmondo_zipcode" placeholder="' + zipCodeFieldText + '"><input type="hidden" id="shipmondo_address_field" class="input" name="shipmondo_address" placeholder="' + addressFieldText + '"> </div> <div class="shipmondo-shoplist dropdown"> <button class="button button-medium btn btn-primary dropdown-toggle" id="shipmondo_find_shop_btn" type="button" data-toggle="dropdown">' + findServicePointText + '<span class="caret"></span></button> </div> </div> </div> <div id="hidden_choosen_shop" type="hidden"></div><div class="shipmondo-clearfix" id="selected_shop_wrapper"> <div id="shipmondo-hidden-shop"> </div> <div class="shipmondo-clearfix" id="selected_shop_header"></div> <div class="shipmondo-clearfix" id="selected_shop_context"> </div> </div> </div> </div>';
    }

    //appends the modal to the body of the prestashop checkout page
    jQuery('body').append(sModalHTML);

    //Event fired when the find nearest shop is pressed
    $(document).on('click', '#shipmondo_find_shop_btn', function() {
        var chosenShippingAgent = getSelectedShippingAgent()

        defaultZipcode = jQuery('#shipmondo_zipcode_field').val();
        defaultAddress = jQuery('#shipmondo_address_field').val();

        getShopList(chosenShippingAgent, $('#shipmondo_zipcode_field').val(), $('#shipmondo_address_field').val());
    })

    jQuery(document).on('keypress', '#shipmondo_zipcode_field', function(event) {
        if (event.keyCode == 13) {
            event.preventDefault();
            if (!jQuery('#shipmondo_find_shop_btn').is(":disabled")) {
                var ishipmondo_choosen_delivery_option = jQuery('.delivery-option input:checked').val();
                ishipmondo_choosen_delivery_option = ishipmondo_choosen_delivery_option.replace(/\D/g, '');
                if (ishipmondo_choosen_delivery_option == glsCarrierId) {
                    sChoosenShippingAgent = 'gls';
                } else if (ishipmondo_choosen_delivery_option == daoCarrierId) {
                    sChoosenShippingAgent = 'dao';
                } else if (ishipmondo_choosen_delivery_option == postnordCarrierId) {
                    sChoosenShippingAgent = 'pdk';
                } else if (ishipmondo_choosen_delivery_option == bringCarrierId) {
                    sChoosenShippingAgent = 'bring';
                }

                defaultZipcode = jQuery('#shipmondo_zipcode_field').val();
                defaultAddress = jQuery('#shipmondo_address_field').val();


                getShopList(sChoosenShippingAgent, jQuery('#shipmondo_zipcode_field').val(), jQuery('#shipmondo_address_field').val());

                jQuery('#shipmondo_zipcode_field').blur();
            }
        }
    })

    // Add Prevent continue button
    jQuery('#js-delivery').append('<button type="button" class="btn btn-primary pull-xs-right choose-pickuppoint" style="display:none;">' + modalHeaderTitle + '</button>');

    jQuery(document).on('click', '.delivery-option input[type="radio"]:not([value="' + glsCarrierId + ',"]):not([value="' + daoCarrierId + ',"]):not([value="' + bringCarrierId + ',"]):not([value="' + postnordCarrierId + ',"])', function() {
        jQuery('#shipmondo-zipcode-wrapper').remove();
        jQuery('button[name="processCarrier"]').prop("disabled", false);
        jQuery('#js-delivery .continue').show();
        jQuery('.choose-pickuppoint').hide();
        jQuery('#selected_shop_wrapper').removeClass("add_border");


    });

    jQuery(document).on('click', '.delivery-option input', function() {
        var shippingAgent = '';
        switch($(this).val()) {
            case daoCarrierId + ',':
                shippingAgent = 'dao';
                break;
            case glsCarrierId + ',':
                shippingAgent = 'gls'
                break;
            case postnordCarrierId + ',':
                shippingAgent = 'pdk'
                break;
            case bringCarrierId + ',':
                shippingAgent = 'bring'
                break;
            default:
                return;
        }

        // Remove zipcode wrapper
        jQuery('#shipmondo-zipcode-wrapper').remove();

        // Find nearest delivery option
        var dev_option = jQuery('.delivery-option input:checked').closest('.delivery-option');
        var extra_content = jQuery(dev_option).find('.carrier-extra-content');

        if (jQuery(extra_content).length < 1) {
            extra_content = jQuery(dev_option).next('.carrier-extra-content');
        }

        jQuery(extra_content).html(sZipcodeHTML);
        jQuery('#js-delivery .continue').hide();

        // Add zipcode from customer address
        if (defaultZipcode) {
            jQuery('#shipmondo_zipcode_field').val(defaultZipcode);
        }
        if (defaultAddress) {
            jQuery('#shipmondo_address_field').val(defaultAddress);
        }
        if (defaultAddress == '' && defaultZipcode == '') {
            var id_delivery = prestashop.cart.id_address_delivery;
            if (!id_delivery) {
                var id_delivery = jQuery('input[name="id_address_delivery"]:checked').val();
            }
            var address_data = prestashop.customer.addresses[id_delivery];
            jQuery('#shipmondo_zipcode_field').val(address_data.postcode);
            jQuery('#shipmondo_address_field').val(address_data.address1);
        }
        if (frontendType == 'radio') {
            jQuery(".loading_radio").show();
            getShopList(shippingAgent, jQuery('#shipmondo_zipcode_field').val(), jQuery('#shipmondo_address_field').val());
        } else {
            jQuery('.choose-pickuppoint').show();
            loadSelectedServicePoint()
        }
    });

    //load service points if you go back to edit
    $('#checkout-delivery-step span.step-edit').on('click', function(){
        $('.delivery-option input:checked').trigger('click')
    })

    //if a shipping method chosen on pageload, trigger click event of that method
    if ($('.js-current-step').attr('id') == 'checkout-delivery-step' && $.inArray(jQuery('.delivery-option input:checked').val(), [glsCarrierId + ",", postnordCarrierId + ",", daoCarrierId + ",", bringCarrierId + ","]) >= 0) {
        $('.delivery-option input:checked').trigger('click')
    }

    jQuery(document).on('keypress', function(event) {
        if (jQuery('.shipmondo-shop-list').hasClass('selected') && event.keyCode == 13 && jQuery('#shipmondo-modal:visible').length != 0) {
            jQuery('#choose-stop-btn').trigger("click");
            jQuery('#choose-stop-btn').blur();
        }
    });

    //shows map
    jQuery('.shipmondo-open-map').on('click', function() {
        jQuery('.shipmondo-hide-map').show();
        jQuery('.shipmondo-open-map').hide();
        jQuery('#shipmondo-map-wrapper').show();
        google.maps.event.trigger(map, 'resize');
        map.fitBounds(bounds);
    })

    //hide map
    jQuery('.shipmondo-hide-map').on('click', function() {
        jQuery('.shipmondo-hide-map').hide();
        jQuery('.shipmondo-open-map').show();
        jQuery('#shipmondo-map-wrapper').hide();
    })

    jQuery('#shipmondo-modal').on('show.bs.modal', function(e) {
        jQuery('body').toggleClass('shipmondo-modal-shown');
        jQuery('.shipmondo-modal-body').scrollTop(0);
    });


    jQuery('#shipmondo-modal').on('hidden.bs.modal', function(e) {
        jQuery('body').toggleClass('shipmondo-modal-shown');
    });

    //Makes sure a shop is selected when the stuff is picked
    jQuery('button[name="processCarrier"]').on('click', function(e) {
        // Prevent submission
        //e.preventDefault();

        if (jQuery('input.delivery_option_radio[value="' + glsCarrierId + ',"]').is(':checked') ||
            jQuery('input.delivery_option_radio[value="' + daoCarrierId + ',"]').is(':checked') ||
            jQuery('input.delivery_option_radio[value="' + postnordCarrierId + ',"]').is(':checked') ||
            jQuery('input.delivery_option_radio[value="' + bringCarrierId + ',"]').is(':checked')) {
            if (jQuery('#selected_shop_context').children().size() == 0) {
                e.preventDefault()
                $("#selected_shop_context").html(noPointSelectedErrorText)
                // }else{
                //     // Submit form if everything is ok
                //     jQuery('#form').submit();
            }
        }
    });

    //Sets the choosen shipping address when modal closes
    jQuery('#shipmondo-modal').on('hidden.bs.modal', function(e) {
        saveCartdetails();
    })


    //adds 3 events to the zipcode text field, that will disable the "find shop button", until a zipcode thats 4 in lentgh % numeric is choosen!
    jQuery(document).on('keyup focusout input change', '#shipmondo_zipcode_field', function(e) {
        if (jQuery('#shipmondo_zipcode_field').val().length > 0) {
            jQuery('#shipmondo_find_shop_btn').prop("disabled", false);
        } else {
            jQuery('#shipmondo_find_shop_btn').prop("disabled", true);
        }
    });
});
jQuery(document).on('click', '.choose-pickuppoint', function() {
    jQuery("#shipmondo_find_shop_btn").trigger("click");
    $("body,html").animate({
            scrollTop: $(".shipmondo_stores").offset().top
        },
        800 //speed
    );
});