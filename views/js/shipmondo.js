var markerIcon = ''; // Data marker icon
//var defaultZoom = 5; // Zoom level of the map
//var defaultMaxZoom = 18; // Max zoom level of the map
var map; // Variable for map
var infowindow; // Variable for marker info window
var ms_marker_list = {};
var bounds = ''; // Set bounds

var usedAddress = '';
var usedZipCode = '';
var usedAgent = '';


var gotError = '';


//If this is only needed one place, remove it!
function getFrontendType() {
    console.log(frontendType);
    return frontendType;
}


//TODO maybe optimize if there is no changes to input
function getShopList(shipping_agent) {
    console.log('getShopList');
    //TODO could come from the calle (btn)
    var findShopBtn = jQuery('#pakkelabels_find_shop_btn');
    var shopList = jQuery('.pakkelabels-shoplist');


    var type = frontendType;
    console.log(type);


    //todo move to function

    //
    // switch (getFrontendType()) {
    //     case 'popup':
    //         console.log('popup');
    //         break;
    //     case 'radio':
    //         console.log('radio');
    //         break;
    //     case 'dropdown':
    //         console.log('dropdown');
    //         // var dropdownList = jQuery('.pakkelabels-shoplist.dropdown');
    //         // if(dropdownList.hasClass('open')){
    //         //     dropdownList.removeClass('open');
    //         //     return;
    //         // }
    //         //if we toggle lets close is
    //         // if(shopList.hasClass('open')){
    //         //     shopList.removeClass('open');
    //         //     return false
    //         // }else{
    //         //     shopList.addClass('open');
    //         // }
    //         break;
    //     default:
    //         console.log('default');
    //         break;
    // }
    //
    // console.log('after switch');


    //findShopBtn.prop("disabled", true); //TODO TESTING if this is the best way or loading overlay is better


    var id_delivery = prestashop.cart.id_address_delivery;
    if (!id_delivery) {
        id_delivery = jQuery('input[name="id_address_delivery"]:checked').val();
    }

    var address_data = prestashop.customer.addresses[id_delivery];
    var zipCode = address_data.postcode;
    var address = address_data.address1;


    if (shipping_agent == usedAgent && address === usedAddress && zipCode === usedZipCode) {
        console.log('No changes');
        if (type == 'popup') {
            //TODO move
            jQuery('#pakkelabel-modal').modal({
                show: true,
                backdrop: true
            });
        } else {

            //TODO is this only dropdown or also radio? not working
            // if(shopList.hasClass('open')){
            //     console.log('close');
            //     shopList.removeClass('open');
            //     return false
            // }else{
            //     console.log('open');
            //     shopList.addClass('open');
            // }

        }

        //TODO still loading ajax, it shoulndt
    } else {
        console.log('new information');

        //TODO instead of remove/append, update. Need new html structure for it to work. if not possible easy then update selector
        // jQuery('.pakkelabels-shoplist-dropdownul').remove();

        if (type == 'popup') {
            markerIcon = shipping_agent + '.png';
        }


        // TODO move to function (show/hide); Make specific for dropdown as well.
        shopList.find('.pakkelabels-dropdown-menu.dropdown-menu').addClass('loading');

        //TODO  2. if dropdown and already open, close.


        //TODO add propper loading. loading_radio is the old one.

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
        //TODO need to work out the issue where it adds a new UL alle the time. Only add one and then populate
        //     jQuery('.pakkelabels-shoplist-dropdownul').remove();
        //     gotError = '';
        // }

        // markerIcon = shipping_agent + '.png';

        // var loadingGif = '<img src="' + prestashop.urls.base_url + '/modules/shipmondo/views/img/loading.gif" class="loading_drop">';
        //
        // findShopBtn.find('span').html(loadingGif).removeClass('caret');

        // findShopBtn.removeClass('dropdown-toggle');


        usedAddress = address;
        usedZipCode = zipCode;
        usedAgent = shipping_agent;

        console.log('start ajax');

        //TODO starting loading now!
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

                //findShopBtn.find('span').addClass('caret').html('');
                findShopBtn.find('span').html('');
                //findShopBtn.addClass('dropdown-toggle').prop("disabled", false);
                //  findShopBtn.prop("disabled", false);
                // zipCodeField.prop("disabled", false);
                // zipCodeField.prop("disabled", false);

                if (response) {
                    var returned = JSON.parse(response);
                    if (returned.status == 'success') {

                        //TODO investigate if you should move inline js from service_ppoints.tpl to here

                        console.log('returned.frontend_type');
                        console.log(returned.frontend_type);
                        if (returned.frontend_type == 'dropdown') {
                            // setTimeout(function () {

                            //TODO instead of removing and adding, we need to append to a child
                            // shopList.find('.pakkelabels-dropdown-menu.dropdown-menu').remove();
                            shopList.find('.pakkelabels-list-wrapper').html(returned.service_points_html);
                            // shopList.append(returned.service_points_html);
                            // }, 1000)
                        } else if (returned.frontend_type == 'radio') {
                            // setTimeout(function () {
                            // jQuery(".loading_radio").hide();


                            // shopList.addClass('open').html(returned.service_points_html);
                            console.log(returned.service_points_html);
                            shopList.html(returned.service_points_html);
                            // }, 1000)
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
                        shopList.find('.pakkelabels-dropdown-menu.dropdown-menu').removeClass('loading');
                    } else {
                        shopList.find('.pakkelabels-dropdown-menu.dropdown-menu').removeClass('loading');
                        gotError = returned.error;
                        // jQuery(".loading_radio").hide();
                        $(".error_msg").html(returned.error);
                    }
                } else {
                    shopList.find('.pakkelabels-dropdown-menu.dropdown-menu').removeClass('loading');
                    // zipCodeField.prop("disabled", false);
                    // jQuery('#pakkelabels_find_shop_btn').prop("disabled", false);
                    $(".error_msg").html(returned.error);
                }
                // findShopBtn.prop("disabled", false); //TODO TESTING if this is the best way or loading overlay is better
            }, error: function (jqXHR, textStatus, errorThrown) {
                shopList.find('.pakkelabels-dropdown-menu.dropdown-menu').removeClass('loading');
                //findShopBtn.prop("disabled", false); //TODO TESTING if this is the best way or loading overlay is better
            }

        });
    }
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
//TODO look at woocommerce for better look
function loadMap(callback, markerfile) {
    console.log('loadMap');

    //var defaultLatlng = new google.maps.LatLng(55.9150835, 10.4713954); // Set default map properties
    var myOptions = {
        // zoom: defaultZoom,
        // center: defaultLatlng,
        // maxZoom: defaultMaxZoom,
        // mapTypeId: google.maps.MapTypeId.Road
        zoom: 6,
        center: {lat: 55.9150835, lng: 10.4713954},
        mapTypeControl: false,
        streetViewControl: false,
        fullscreenControl: false
        // zoom: defaultZoom,
        // center: defaultLatlng,
        // maxZoom: defaultMaxZoom,
        // mapTypeId: google.maps.MapTypeId.Road
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
            infowindow.setContent("<strong>" + markerData['company_name'] + "</strong><br>" + markerData['address'] + "<br> " + markerData['city'] + " <br> " + markerData['zipcode']);
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

    console.log(carrierId);
    console.log(glsCarrierId);
    console.log(daoCarrierId);
    console.log(postnordCarrierId);
    console.log(bringCarrierId);


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
    //TODO only add modal if type is modal
    if (frontendType == 'popup') {
        var modalHtml = '<div class="pakkelabel-modal fade-pakkelabel" id="pakkelabel-modal" tabindex="-1" role="dialog" aria-labelledby="packetshop window"> <div class="pakkelabel-modal-dialog" role="document"> <div class="pakkelabel-modal-content"> <div class="pakkelabel-modal-header"> <h4 class="pakkelabel-modal-title" id="pakkelabel-modal-header-h4">' + modalHeaderTitle + '</h4> <button id="pakkelabel-modal-header-button" type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button></div> <div class="pakkelabel-modal-body"> <div id="pakkelabel-map-wrapper"></div> <div id="pakkelabel-list-wrapper"></div> </div> <div class="pakkelabel-modal-footer"> <button id="choose-stop-btn" type="button" class="button btn btn-default button-medium" data-dismiss="modal">' + chooseServicePointText + '</button> <div class="powered-by-pakkelabels">Powered by</div> </div> </div> </div> </div>';
        jQuery('body').append(modalHtml);
    }


    // //TODO they are so similar that they shuold be combined - They are very close now!
    // var selectedPickupPointHtml = '';
    // if (frontendType == 'popup') {
    //     selectedPickupPointHtml = '<div>' +
    //         '<div class="error_msg"></div>' +
    //         '</div>' +
    //         '<div>' +
    //         '<button class="button button-medium btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' +
    //         findServicePointText +
    //         '</button>' +
    //         '</div>';
    // } else if (frontendType == 'radio') {
    //     selectedPickupPointHtml = '<div>' +
    //         '<div class="error_msg"></div>' +
    //         '</div>' +
    //         '<div class="pakkelabels-shoplist"></div>';
    // } else {
    //     selectedPickupPointHtml = '<div>' +
    //         '<div class="error_msg"></div>' +
    //         '</div>' +
    //         '<div class="pakkelabels-shoplist dropdown">' +
    //         '<button class="button button-medium btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' +
    //         findServicePointText +
    //         '</button>' +
    //         '<div class="pakkelabels-dropdown-menu dropdown-menu">' +
    //         '<div class="pakkelabels-dropdown-content-section">' +
    //         '<div class="pakkelabels-loader-wrapper">' +
    //         '<div class="pakkelabels-loader"></div></div>' +
    //         '<div class="pakkelabels-list-wrapper"></div>' +
    //         '</div>' +
    //         '<div class="pakkelabels-dropdown-footer">' +
    //         'Powered by Shipmondo' +
    //         '</div>' +
    //         '</div>' +
    //         '</div>' +
    //         '</div>';
    // }
    //
    //
    // var selectedPickupPointWrapHtml = '<div class="pakkelabels_shipping_field-wrap pakkelabels_shipping_field-wrap-type-' + frontendType + '">' +
    //     '<div class="pakkelabels_shipping_field">' +
    //     '<div class="pakkelabels-clearfix" id="pakkelabels_shipping_button">' +
    //     '<div class="pakkelabels_stores">' +
    //     selectedPickupPointHtml +
    //     '</div>' +
    //     '</div>' +
    //     '<div id="hidden_choosen_shop"></div>' +
    //     '<div class="pakkelabels-clearfix" id="selected_shop_wrapper">' +
    //     '<div id="pakkelabels-hidden-shop"></div>' +
    //     '<div class="pakkelabels-clearfix" id="selected_shop_header"></div>' +
    //     '<div class="pakkelabels-clearfix" id="selected_shop_context"></div>' +
    //     '</div>' +
    //     '</div>' +
    //     '</div>';


    //appends the modal to the body of the prestashop checkout page

    //Event fired when the find nearest shop is pressed
    $(document).on('click', '#pakkelabels_find_shop_btn', function () {
        console.log('click.pakkelabels_find_shop_btn');

        var chosenShippingAgent = getSelectedShippingAgent();

        console.log('chosenShippingAgent:');
        console.log(chosenShippingAgent);
        getShopList(chosenShippingAgent);

        //TODO implement autoclose and ok from WC
    });
    // Add Prevent continue button
    jQuery('#js-delivery').append('<button type="button" class="btn btn-primary pull-xs-right choose-pickuppoint" style="display:none;">' + modalHeaderTitle + '</button>');

    jQuery(document).on('click', '.delivery-option input[type="radio"]:not([value="' + glsCarrierId + ',"]):not([value="' + daoCarrierId + ',"]):not([value="' + bringCarrierId + ',"]):not([value="' + postnordCarrierId + ',"])', function () {
        jQuery('.pakkelabels_shipping_field-wrap').remove();
        jQuery('button[name="processCarrier"]').prop("disabled", false);
        jQuery('#js-delivery .continue').show();
        jQuery('.choose-pickuppoint').hide();
        // jQuery('#selected_shop_wrapper').removeClass("add_border");
    });

    jQuery(document).on('click', '.delivery-option input', function () {
        console.log('click.delivery-option');

        console.log($(this).val());
        console.log(getShippingAgentByVal($(this).val()));

        if (getShippingAgentByVal($(this).val()) != '') {

            // Remove zipcode wrapper
            jQuery('.pakkelabels_shipping_field-wrap').remove();

            // Find nearest delivery option
            var dev_option = jQuery('.delivery-option input:checked').closest('.delivery-option');
            var extra_content = jQuery(dev_option).find('.carrier-extra-content');

            if (jQuery(extra_content).length < 1) {
                extra_content = jQuery(dev_option).next('.carrier-extra-content');
            }


            console.log('extra_content');
            console.log(extra_content);


            //TODO they are so similar that they shuold be combined - They are very close now!
            var selectedPickupPointHtml = '';
            if (frontendType == 'popup') {
                selectedPickupPointHtml = '<div>' +
                    '<div class="error_msg"></div>' +
                    '</div>' +
                    '<div>' +
                    // '<button class="button button-medium btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' +
                    '<button class="button button-medium btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button">' +
                    findServicePointText +
                    '</button>' +
                    '</div>';
            } else if (frontendType == 'radio') {
                selectedPickupPointHtml = '<div>' +
                    '<div class="error_msg"></div>' +
                    '</div>' +
                    '<div class="pakkelabels-shoplist"></div>';
            } else {
                selectedPickupPointHtml = '<div>' +
                    '<div class="error_msg"></div>' +
                    '</div>' +
                    '<div class="pakkelabels-shoplist dropdown">' +
                    '<button class="button button-medium btn btn-primary dropdown-toggle" id="pakkelabels_find_shop_btn" type="button" data-toggle="dropdown">' +
                    findServicePointText +
                    '</button>' +
                    '<div class="pakkelabels-dropdown-menu dropdown-menu">' +
                    '<div class="pakkelabels-dropdown-content-section">' +
                    '<div class="pakkelabels-loader-wrapper">' +
                    '<div class="pakkelabels-loader"></div></div>' +
                    '<div class="pakkelabels-list-wrapper"></div>' +
                    '</div>' +
                    '<div class="pakkelabels-dropdown-footer">' +
                    'Powered by Shipmondo' +
                    '</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>';
            }


            var selectedPickupPointWrapHtml = '<div class="pakkelabels_shipping_field-wrap pakkelabels_shipping_field-wrap-type-' + frontendType + '">' +
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


            console.log('add selectedPickupPointWrapHtml');
            jQuery(extra_content).html(selectedPickupPointWrapHtml);
            console.log('added');


            jQuery('#js-delivery .continue').hide();

            if (frontendType == 'radio') {
                // jQuery(".loading_radio").show();
                //TODO could this be moved? Think its used alot as is
                getShopList(getShippingAgentByVal($(this).val()));
            } else {
                jQuery('.choose-pickuppoint').show();
                loadSelectedServicePoint()
            }
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


    //TODO enter on radiobutton also issue
    jQuery(document).on('keypress', function (event) {
        if (jQuery('.pakkelabels-shop-list').hasClass('selected') && event.keyCode == 13 && jQuery('#pakkelabel-modal:visible').length != 0) {
            jQuery('#choose-stop-btn').trigger("click").blur();
        }
    });

    //shows map
    // jQuery('.pakkelabel-open-map').on('click', function () {
    //     jQuery('.pakkelabel-hide-map').show();
    //     jQuery('.pakkelabel-open-map').hide();
    //     jQuery('#pakkelabel-map-wrapper').show();
    //     google.maps.event.trigger(map, 'resize');
    //     map.fitBounds(bounds);
    // });

    //hide map
    // jQuery('.pakkelabel-hide-map').on('click', function () {
    //     jQuery('.pakkelabel-hide-map').hide();
    //     jQuery('.pakkelabel-open-map').show();
    //     jQuery('#pakkelabel-map-wrapper').hide();
    // });

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
});

jQuery(document).on('click', '.choose-pickuppoint', function (e) {
    console.log('click.choose-pickuppoint');
    e.preventDefault();

    //Somehow above is not working correctly so for now use timeout - but this should be solved
    setTimeout(function () {
        jQuery("#pakkelabels_find_shop_btn").trigger("click");
        $("body,html").animate({
                scrollTop: $(".pakkelabels_stores").offset().top
            },
            800 //speed
        );
    }, 100);

});