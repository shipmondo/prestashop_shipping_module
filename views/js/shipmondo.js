var markerIcon = ''; // Data marker icon
var defaultZoom = 5; // Zoom level of the map
var defaultMaxZoom = 18; // Max zoom level of the map
var map; // Variable for map
var infowindow; // Variable for marker info window
var ms_marker_list = {};
var bounds = ''; // Set bounds
// var usedZipCode = '';
// var usedAgent = '';
var gotError = '';


//TODO maybe optimize if there is no changes to input
function getShopList(shipping_agent) {
    console.log('getShopList');
    var findShopBtn = jQuery('#pakkelabels_find_shop_btn');
    // var zipCodeField = jQuery('#Pakkelabels_zipcode_field');

    var id_delivery = prestashop.cart.id_address_delivery;
    if (!id_delivery) {
        id_delivery = jQuery('input[name="id_address_delivery"]:checked').val();
    }

    var address_data = prestashop.customer.addresses[id_delivery];
    var zipCode = address_data.postcode;
    var address = address_data.address1;


    //
    // if (usedZipCode == zipCode && usedAgent == shipping_agent) {
    //     if (gotError !== '') {
    //         $(".error_msg").html(gotError);
    //         return false;
    //     }
    //     // zipCodeField.removeAttr("disabled");
    //     findShopBtn.removeAttr("disabled");
    //     if (frontendType == 'Popup') {
    //         jQuery('#pakkelabel-modal').modal({
    //             show: true,
    //             backdrop: true
    //         });
    //     }
    //     return true;
    // } else {
    //     jQuery('.pakkelabels-shoplist-dropdownul').remove();
    //     gotError = '';
    // }

    markerIcon = shipping_agent + '.png';

    var loadingGif = '<img src="' + prestashop.urls.base_url + '/modules/shipmondo/views/img/loading.gif" class="loading_drop">';

    findShopBtn.find('span').html(loadingGif).removeClass('caret');
    findShopBtn.removeClass('dropdown-toggle');

    jQuery.ajax({
        url: servicePointsEndpoint,
        type: 'POST',
        data: {
            'method': 'get_list',
            'shipping_agent': shipping_agent,
            'zip_code': zipCode,
            'address': address
        },
        success: function (response) {
            // jQuery('#pakkelabels_find_shop_btn span').addClass('caret');
            // jQuery('#pakkelabels_find_shop_btn').addClass('dropdown-toggle');
            // jQuery('#pakkelabels_find_shop_btn span').html('');
            // jQuery('#Pakkelabels_zipcode_field').prop("disabled", false);
            // jQuery('#pakkelabels_find_shop_btn').prop("disabled", false);

            findShopBtn.find('span').addClass('caret').html('');
            findShopBtn.addClass('dropdown-toggle').prop("disabled", false);
            // zipCodeField.prop("disabled", false);

            if (response) {
                var returned = JSON.parse(response);
                if (returned.status == 'success') {
                    var shopList = jQuery('.pakkelabels-shoplist');

                    //TODO investigate if you should move inline js from service_ppoints.tpl to here
                    if (returned.frontend_type == 'dropdown') {
                        setTimeout(function () {
                            shopList.addClass('open').append(returned.service_points_html);
                        }, 1000)
                    } else if (returned.frontend_type == 'radio') {
                        setTimeout(function () {
                            jQuery(".loading_radio").hide();
                            shopList.addClass('open').html(returned.service_points_html);
                        }, 1000)
                    } else {
                        jQuery('#pakkelabel-modal').modal({
                            show: true,
                            backdrop: true
                        });
                        jQuery('#pakkelabel-map-wrapper').html(returned.map);
                        jQuery('#pakkelabel-list-wrapper').html(returned.service_points_html);
                        jQuery('#pakkelabels-hidden-shop').html(returned.hidden_pakkelabels);
                        markerFile = returned.service_points;
                        undefined_cords_markerFile = [];

                        for (var key in markerFile) {
                            if (markerFile.hasOwnProperty(key) && (!markerFile[key].hasOwnProperty('latitude') || !markerFile[key].hasOwnProperty('longitude'))) {
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

                        setTimeout(function () {
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
                // zipCodeField.prop("disabled", false);
                jQuery('#pakkelabels_find_shop_btn').prop("disabled", false);
                $(".error_msg").html(returned.error);
            }
        }
    });
}

function saveCartdetails() {
    console.log('saveCartdetails');

    var selectedShopContext = jQuery('#selected_shop_context');
    if (selectedShopContext.children().size() != 0) {
        var shippingAgent = getSelectedShippingAgent();

        var companyName = selectedShopContext.find('.pakkelabels-company-name').text().trim();
        var servicePointID = selectedShopContext.find('.pakkelabels-Packetshop').text().trim();
        var address = selectedShopContext.find('.pakkelabels-Address').text().trim();
        var city = selectedShopContext.find('.pakkelabels-ZipAndCity > .pakkelabels-city').text().trim();
        var zipCode = selectedShopContext.find('.pakkelabels-ZipAndCity > .pakkelabels-zipcode').text().trim();

        // var sCompany_name = jQuery('#selected_shop_context > .pakkelabels-company-name').text().trim();
        // var sPacketshop_id = jQuery('#selected_shop_context > .pakkelabels-Packetshop').text().trim();
        // var sAdress = jQuery('#selected_shop_context > .pakkelabels-Address').text().trim();
        // var sCity = jQuery('#selected_shop_context > .pakkelabels-ZipAndCity > .pakkelabels-city').text().trim();
        // var iZipcode = jQuery('#selected_shop_context > .pakkelabels-ZipAndCity > .pakkelabels-zipcode').text().trim();

        jQuery.ajax({
            url: servicePointsEndpoint,
            type: 'POST',
            data: {
                'method': "save_address",
                'company_name': companyName,
                'service_point_id': servicePointID,
                'address': address,
                'city': city,
                'zip_code': zipCode,
                'shipping_agent': shippingAgent
            },
            dataType: 'json',
            error: function (response) {
                // Error
            },
            success: function (response) {
                if (response.status == "success") {
                    // Success
                } else if (response.status == "error") {
                    $(".error_msg").html(noPointSelectedErrorText);
                }
            }
        });
    }
}

//Calls googles gmap api, and gets the cords for the streetnames from the shopilst generated by pakkelabels
function load_markers_without_cords_from_streetname(aMarkerFile) {
    console.log('load_markers_without_cords_from_streetname');

    var geocoder = new google.maps.Geocoder();
    jQuery(aMarkerFile).each(function (key) {
        var address = this.address + ", " + this.city + ", " + this.zipcode;
        var iShopid = this.number;
        geocoder.geocode({
            'address': address
        }, function (results, status) {
            if (status === google.maps.GeocoderStatus.OK) {
                aMarkerFile[key].latitude = results[0].geometry.location.lat() + "";
                aMarkerFile[key].longitude = results[0].geometry.location.lng() + "";
                loadMarker(aMarkerFile[key]);

            } else {
                jQuery('[data-shopid="' + iShopid + '"] > div').append('<div class="no_cords_found">' + noCoordinatesErrorText + '</div>');
            }
        })
    });
    return aMarkerFile;
}

//loads the map and other map related stuff
function loadMap(callback, markerfile) {
    console.log('loadMap');

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
    console.log('loadmarkers');
    //loads any markers that already have cords
    if (Object.keys(markerFile).length >= 1) {
        jQuery(markerFile).each(function () {
            loadMarker(this);
        });
    }
}

//loades a single marker (used by loaderMarkers())
function loadMarker(markerData) {
    console.log('loadMarker');

    // Create new marker location
    var myLatlng = new google.maps.LatLng(markerData['latitude'], markerData['longitude']);

    // Create new marker
    var marker = new google.maps.Marker({
        map: map,
        position: myLatlng,
        icon: moduleBaseUrl + '/views/img/' + markerIcon
    });

    // Add information to the marker
    google.maps.event.addListener(marker, 'click', (function (marker) {
        return function () {
            infowindow.setContent("<strong>" + markerData['company_name'] + "</strong><br/>" + markerData['address'] + "<br/> " + markerData['city'] + " <br/> " + markerData['zipcode']);
            infowindow.open(map, marker);
            var shop_list = jQuery('.pakkelabels-shoplist > ul >li');
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
function checkdroppointselected() {
    console.log('checkdroppointselected');

    // Show continue button
    jQuery('#js-delivery .continue').show();
    jQuery('.choose-pickuppoint').hide();
}

function li_addlistener_open_marker(eventElement) {
    console.log('li_addlistener_open_marker');

    var event = eventElement;

    jQuery.each(ms_marker_list, function (key, value) {

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
    console.log('loadSelectedServicePoint');

    var shippingAgent = getSelectedShippingAgent();

    jQuery.ajax({
        url: servicePointsEndpoint,
        type: 'GET',
        data: {
            method: 'get_address',
            shipping_agent: shippingAgent
        },
        success: function (response) {
            response = JSON.parse(response);
            if (response['status'] == 'success') {
                var servicePoint = response['service_point'];
                var servicePointHtml =
                    '<div class="pakkelabels-company-name">' + servicePoint['company'] + '</div>' +
                    '<div class="pakkelabels-Address">' + servicePoint['address'] + '</div>' +
                    '<div class="pakkelabels-ZipAndCity">' +
                    '<span class="pakkelabels-zipcode">' + servicePoint['zip_code'] + '</span>,' +
                    '<span class="pakkelabels-city">' + servicePoint['city'] + '</span>' +
                    '</div>' +
                    '<div class="pakkelabels-Packetshop">' + servicePoint['address2'] + '</div>';

                var shopId = servicePoint['address2'].replace(/\D/g, '');
                $('#hidden_choosen_shop').attr('shopid', shopId);

                $('#selected_shop_header').html(selectedServicePointHeader);
                $('#selected_shop_context').html(servicePointHtml);

                if (typeof checkdroppointselected !== 'undefined')
                    checkdroppointselected(this);
            }
        }
    });
}

function getShippingAgentByVal(val) {
    console.log('getShippingAgentByVal');

    //Strip ',' etc.
    var carrierId = val.replace(/\D/g, '');
    switch (parseInt(carrierId)) {
        case glsCarrierId:
            return 'gls';
        case daoCarrierId:
            return 'dao';
        case postnordCarrierId:
            return 'pdk';
        case bringCarrierId:
            return 'bring';
        default:
            return ''
    }
}

function getSelectedShippingAgent() {
    console.log('getSelectedShippingAgent');

    return getShippingAgentByVal($('.delivery-option input:checked').val());
}

jQuery(window).on('load', function () {
    console.log('window.on.load');

    //html to be injected into the prestashop
    var modalHtml = '<div class="pakkelabel-modal fade-pakkelabel" id="pakkelabel-modal" tabindex="-1" role="dialog" aria-labelledby="packetshop window"> <div class="pakkelabel-modal-dialog" role="document"> <div class="pakkelabel-modal-content"> <div class="pakkelabel-modal-header"> <h4 class="pakkelabel-modal-title" id="pakkelabel-modal-header-h4">' + modalHeaderTitle + '</h4> <button id="pakkelabel-modal-header-button" type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button> <div class="pakkelabel-open-close-button-wrap"> <div class="pakkelabel-open-close-button pakkelabel-open-map">' + showMapText + '</div> <div class="pakkelabel-open-close-button pakkelabel-hide-map">' + hideMapText + '</div> </div></div> <div class="pakkelabel-modal-body"> <div id="pakkelabel-map-wrapper"></div> <div id="pakkelabel-list-wrapper"></div> </div> <div class="pakkelabel-modal-footer"> <button id="choose-stop-btn" type="button" class="button btn btn-default button-medium" data-dismiss="modal">' + chooseServicePointText + '</button> <div class="powered-by-pakkelabels">Powered by</div> </div> </div> </div> </div>';


    //TODO they are so similar that they shuold be combined
    var selectedPickupPointHtml = '';
    if (frontendType == 'popup') {
        selectedPickupPointHtml = '<div>' +
            '<div class="error_msg"></div>' +
            // '<input type="hidden" id="Pakkelabels_zipcode_field" class="input" name="pakkelabels_zipcode" placeholder="' + zipCodeFieldText + '">' +
            // '<input type="hidden" id="Pakkelabels_address_field" class="input" name="pakkelabels_address" placeholder="' + addressFieldText + '">' +
            '</div>' +
            '<div>' +
            '<button class="button button-medium btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' +
            findServicePointText +
            '</button>' +
            '</div>';
    } else if (frontendType == 'radio') {
        selectedPickupPointHtml = '<div>' +
            '<span><img src="' + prestashop.urls.base_url + '/modules/shipmondo/views/img/loading.gif" class="loading_radio" style="display:none;"></span>' +
            '<div class="error_msg"></div>' +
            // '<input type="hidden" id="Pakkelabels_zipcode_field" class="input" name="pakkelabels_zipcode" placeholder="' + zipCodeFieldText + '">' +
            // '<input type="hidden" id="Pakkelabels_address_field" class="input" name="pakkelabels_address" placeholder="' + addressFieldText + '">' +
            '</div>' +
            '<div class="pakkelabels-shoplist"></div>';
    } else {
        selectedPickupPointHtml = '<div>' +
            '<div class="error_msg"></div>' +
            // '<input type="hidden" id="Pakkelabels_zipcode_field" class="input" name="pakkelabels_zipcode" placeholder="' + zipCodeFieldText + '">' +
            // '<input type="hidden" id="Pakkelabels_address_field" class="input" name="pakkelabels_address" placeholder="' + addressFieldText + '">' +
            '</div>' +
            '<div class="pakkelabels-shoplist dropdown">' +
            '<button class="button button-medium btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' +
            findServicePointText + '<span class="caret"></span></button>' +
            '</div>';
    }


    var selectedPickupPointWrapHtml = '<div class="pakkelabels_shipping_field-wrap type-' + frontendType + '">' +
        '<div class="pakkelabels_shipping_field">' +
        '<div class="pakkelabels-clearfix" id="pakkelabels_shipping_button">' +
        '<div class="pakkelabels_stores">' +
        selectedPickupPointHtml +
        '</div>' +
        '</div>' +
        '<div id="hidden_choosen_shop"></div>' +
        '<div class="pakkelabels-clearfix" id="selected_shop_wrapper">' +
        '<div id="pakkelabels-hidden-shop"></div>' +
        '<div class="pakkelabels-clearfix" id="selected_shop_header"></div>' +
        '<div class="pakkelabels-clearfix" id="selected_shop_context"></div>' +
        '</div>' +
        '</div>' +
        '</div>';


    //appends the modal to the body of the prestashop checkout page
    jQuery('body').append(modalHtml);

    //Event fired when the find nearest shop is pressed
    $(document).on('click', '#pakkelabels_find_shop_btn', function () {
        console.log('click.pakkelabels_find_shop_btn');

        var chosenShippingAgent = getSelectedShippingAgent();
        // var defaultZipCode = jQuery('#Pakkelabels_zipcode_field').val();
        // var defaultAddress = jQuery('#Pakkelabels_address_field').val();

        getShopList(chosenShippingAgent);

        //TODO implement autoclose and ok from WC
    });


    // jQuery(document).on('keypress', '#Pakkelabels_zipcode_field', function (event) {
    //     if (event.keyCode == 13) {
    //         event.preventDefault();
    //         if (!jQuery('#pakkelabels_find_shop_btn').is(":disabled")) {
    //
    //             // var defaultZipCodeField = jQuery('#Pakkelabels_zipcode_field');
    //             // var defaultZipCode = defaultZipCodeField.val();
    //             // var defaultAddress = jQuery('#Pakkelabels_address_field').val();
    //
    //             getShopList(getSelectedShippingAgent());
    //
    //             defaultZipcodeField.blur();
    //         }
    //     }
    // });

    // Add Prevent continue button
    jQuery('#js-delivery').append('<button type="button" class="btn btn-primary pull-xs-right choose-pickuppoint" style="display:none;">' + modalHeaderTitle + '</button>');

    jQuery(document).on('click', '.delivery-option input[type="radio"]:not([value="' + glsCarrierId + ',"]):not([value="' + daoCarrierId + ',"]):not([value="' + bringCarrierId + ',"]):not([value="' + postnordCarrierId + ',"])', function () {
        jQuery('.pakkelabels_shipping_field-wrap').remove();
        jQuery('button[name="processCarrier"]').prop("disabled", false);
        jQuery('#js-delivery .continue').show();
        jQuery('.choose-pickuppoint').hide();
        jQuery('#selected_shop_wrapper').removeClass("add_border");
    });

    jQuery(document).on('click', '.delivery-option input', function () {
        console.log('click.delivery-option');


        // //TODO reuse case from above l-310
        // var shippingAgent = '';
        // switch ($(this).val()) {
        //     case daoCarrierId + ',':
        //         shippingAgent = 'dao';
        //         break;
        //     case glsCarrierId + ',':
        //         shippingAgent = 'gls';
        //         break;
        //     case postnordCarrierId + ',':
        //         shippingAgent = 'pdk';
        //         break;
        //     case bringCarrierId + ',':
        //         shippingAgent = 'bring';
        //         break;
        //     default:
        //         return;
        // }
        //
        //
        // shippingAgent = getShippingAgentByVal($(this).val());

        // Remove zipcode wrapper
        jQuery('.pakkelabels_shipping_field-wrap').remove();

        // Find nearest delivery option
        var dev_option = jQuery('.delivery-option input:checked').closest('.delivery-option');
        var extra_content = jQuery(dev_option).find('.carrier-extra-content');

        if (jQuery(extra_content).length < 1) {
            extra_content = jQuery(dev_option).next('.carrier-extra-content');
        }

        jQuery(extra_content).html(selectedPickupPointWrapHtml);
        // jQuery(extra_content).html(sZipcodeHTML);
        jQuery('#js-delivery .continue').hide();

        // Add zipcode from customer address
        // if (defaultZipcode) {
        //     jQuery('#Pakkelabels_zipcode_field').val(defaultZipcode);
        // }
        // if (defaultAddress) {
        //     jQuery('#Pakkelabels_address_field').val(defaultAddress);
        // }
        // if (defaultAddress == '' && defaultZipcode == '') {
        // var id_delivery = prestashop.cart.id_address_delivery;
        // if (!id_delivery) {
        //     id_delivery = jQuery('input[name="id_address_delivery"]:checked').val();
        // }
        // var address_data = prestashop.customer.addresses[id_delivery];
        // jQuery('#Pakkelabels_zipcode_field').val(address_data.postcode);
        // jQuery('#Pakkelabels_address_field').val(address_data.address1);
        // }
        if (frontendType == 'radio') {
            jQuery(".loading_radio").show();
            //TODO could this be moved? Think its used alot as is
            getShopList(getShippingAgentByVal($(this).val()));
        } else {
            jQuery('.choose-pickuppoint').show();
            loadSelectedServicePoint()
        }
    });

    //load service points if you go back to edit
    $('#checkout-delivery-step span.step-edit').on('click', function () {
        $('.delivery-option input:checked').trigger('click');
    });

    //if a shipping method chosen on pageload, trigger click event of that method
    if ($('.js-current-step').attr('id') == 'checkout-delivery-step' && jQuery.inArray(jQuery('.delivery-option input:checked').val(), [glsCarrierId + ",", postnordCarrierId + ",", daoCarrierId + ",", bringCarrierId + ","]) >= 0) {
        $('.delivery-option input:checked').trigger('click');
    }

    jQuery(document).on('keypress', function (event) {
        if (jQuery('.pakkelabels-shop-list').hasClass('selected') && event.keyCode == 13 && jQuery('#pakkelabel-modal:visible').length != 0) {
            jQuery('#choose-stop-btn').trigger("click").blur();
        }
    });

    //shows map
    jQuery('.pakkelabel-open-map').on('click', function () {
        jQuery('.pakkelabel-hide-map').show();
        jQuery('.pakkelabel-open-map').hide();
        jQuery('#pakkelabel-map-wrapper').show();
        google.maps.event.trigger(map, 'resize');
        map.fitBounds(bounds);
    });

    //hide map
    jQuery('.pakkelabel-hide-map').on('click', function () {
        jQuery('.pakkelabel-hide-map').hide();
        jQuery('.pakkelabel-open-map').show();
        jQuery('#pakkelabel-map-wrapper').hide();
    });

    var modal = jQuery('#pakkelabel-modal');
    modal.on('show.bs.modal', function (e) {
        jQuery('body').toggleClass('pakkelabels-modal-shown');
        jQuery('.pakkelabel-modal-body').scrollTop(0); //TODO is this not child of above?
    });


    modal.on('hidden.bs.modal', function (e) {
        jQuery('body').toggleClass('pakkelabels-modal-shown');
    });

    //Makes sure a shop is selected when the stuff is picked
    jQuery('button[name="processCarrier"]').on('click', function (e) {
        // Prevent submission
        //e.preventDefault();

        if (jQuery('input.delivery_option_radio[value="' + glsCarrierId + ',"]').is(':checked') ||
            jQuery('input.delivery_option_radio[value="' + daoCarrierId + ',"]').is(':checked') ||
            jQuery('input.delivery_option_radio[value="' + postnordCarrierId + ',"]').is(':checked') ||
            jQuery('input.delivery_option_radio[value="' + bringCarrierId + ',"]').is(':checked')) {
            if (jQuery('#selected_shop_context').children().size() == 0) {
                e.preventDefault();
                $("#selected_shop_context").html(noPointSelectedErrorText);
                // }else{
                //     // Submit form if everything is ok
                //     jQuery('#form').submit();
            }
        }
    });

    //Sets the choosen shipping address when modal closes
    modal.on('hidden.bs.modal', function (e) {
        saveCartdetails();
    });

    //adds 3 events to the zipcode text field, that will disable the "find shop button", until a zipcode thats 4 in lentgh % numeric is choosen!
    // jQuery(document).on('keyup focusout input change', '#Pakkelabels_zipcode_field', function (e) {
    //     if (jQuery('#Pakkelabels_zipcode_field').val().length > 0) {
    //         jQuery('#pakkelabels_find_shop_btn').prop("disabled", false);
    //     } else {
    //         jQuery('#pakkelabels_find_shop_btn').prop("disabled", true);
    //     }
    // });
});

jQuery(document).on('click', '.choose-pickuppoint', function () {
    jQuery("#pakkelabels_find_shop_btn").trigger("click");
    $("body,html").animate({
            scrollTop: $(".pakkelabels_stores").offset().top
        },
        800 //speed
    );
});