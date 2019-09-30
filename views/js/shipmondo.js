jQuery(document).ready(function ($) {
    var body = $('body');
    var selection_button = '#shipmondo_find_shop_btn';
    var close_button = '.shipmondo-modal-close-button, .shipmondo-modal-close';
    var modal = null;
    var modal_content = null;
    var modal_error = null;
    var pickup_points_json = 'input[name="shipmondo_pickup_points_json"]';
    var map = null;
    var bounds = null;
    var current_search = null;
    var current_shop = null;
    var ajax_success = null;
    var infowindow;
    var hidden_chosen_shop = '#hidden_chosen_shop';
    var selected_shop_context = '#selected_shop_context';


    function hideModal() {
        modal.removeClass('visible').removeClass('loading');
        setTimeout(function () {
            $('.shipmondo-modal-content').addClass('visible');
            $('.shipmondo-modal-checkmark').removeClass('visible');
            modal.addClass('shipmondo-hidden');
        }, 300);
        modal_error.removeClass('visible');
        if (typeof infowindow !== 'undefined') {
            infowindow.close();
        }
        body.removeClass('shipmondo-modal-open');
    }

    function hideDropdown() {
        var dropdown = $('#shipmondo_pickup_point_selector_dropdown');
        var dropdown_button = $('.shipmondo_dropdown_button');

        dropdown.removeClass('loading').addClass('shipmondo-hidden');
        dropdown_button.removeClass('open');
    }

    function getAddress() {
        var id_delivery = prestashop.cart.id_address_delivery;
        if (!id_delivery) {
            id_delivery = jQuery('input[name="id_address_delivery"]:checked').val();
        }

        var address_data = prestashop.customer.addresses[id_delivery];
        console.log('address_data');
        console.log(address_data);

        return {
            carrier_code: getSelectedCarrierCode(),
            address: address_data.address1,
            zipcode: address_data.postcode,
            country: address_data.country
        };
    }

    function isLastSearch(search) {
        console.log('isLastSearch');
        var last_search = current_search;

        var _current_search = getAddress();

        console.log(_current_search);

        //todo add address
        if (last_search !== null && ajax_success && _current_search.carrier_code === last_search.carrier_code && _current_search.zipcode === last_search.zipcode && _current_search.country === last_search.country) {
            return true;
        }

        if (search === true) {
            current_search = _current_search;
        }

        return false;
    }

    function getPickupPointsDropdown() {
        console.log('getPickupPointsDropdown');

        var dropdown = $('#shipmondo_pickup_point_selector_dropdown');
        dropdown.removeClass('shipmondo-hidden');

        var dropdown_button = $('.shipmondo_dropdown_button');
        dropdown_button.addClass('open');

        var dropdown_error = dropdown.find('.shipmondo-error');
        dropdown_error.removeClass('visible');

        var dropdown_content = dropdown.find('.shipmondo-removable-content');


        console.log('dropdown');
        console.log(dropdown);

        //TODO under:
        if (isLastSearch(true)) {
            return;
        }

        dropdown.addClass('loading');

        dropdown_content.empty();

        ajax_success = false;


        //TODO maybe reuse from modal
        jQuery.ajax({
            url: servicePointsEndpoint,
            type: 'POST',
            data: {
                'method': 'get_list',
                'carrier_code': current_search.carrier_code,
                'zip_code': current_search.zipcode,
                'address': current_search.address
            },
            success: function (response) {
                if (response) {
                    var returned = JSON.parse(response);
                    console.log('returned');
                    console.log(returned);


                    if (returned.status === "error") {
                        dropdown_error.html(returned.error);
                        dropdown_error.addClass('visible');
                    } else {
                        console.log('returned.frontend_type');
                        console.log(returned.frontend_type);

                        console.log('dropdown_content');
                        console.log(dropdown_content);

                        dropdown_content.html(returned.service_points_html);

                        // dropdown_content.find('')

                        console.log('dropdown_content after');
                        console.log(dropdown_content);
                        ajax_success = true;
                    }
                    $('.shipmondo-modal-content').addClass('visible');
                    dropdown.removeClass('loading');
                } else {
                    dropdown_error.addClass('visible');
                    dropdown.removeClass('loading');
                }
            }, error: function () {
                dropdown_error.addClass('visible');
                dropdown.removeClass('loading');
            }
        });
    }

    function getPickupPointsModal() {
        console.log('getPickupPointsModal');

        console.log('modal');
        console.log(modal);

        modal.removeClass('shipmondo-hidden');
        setTimeout(function () {
            body.addClass('shipmondo-modal-open');
            modal.addClass('visible');
        }, 100);

        if (isLastSearch(true)) {
            return;
        }

        modal.addClass('loading');

        modal_content.empty();

        ajax_success = false;


        //TODO maybe reuse from modal
        $.ajax({
            url: servicePointsEndpoint,
            type: 'POST',
            data: {
                'method': 'get_list',
                'carrier_code': current_search.carrier_code,
                'zip_code': current_search.zipcode,
                'address': current_search.address
            },
            success: function (response) {
                if (response) {
                    var returned = JSON.parse(response);
                    console.log('returned');
                    console.log(returned);

                    if (returned.status === "error") {
                        if (returned.error) {
                            modal_error.find('p').html(returned.error);
                            modal_error.addClass('visible');
                        }
                    } else {
                        modal_content.html(returned.service_points_html);

                        //Set selected
                        console.log('current_shop');
                        console.log(current_shop);
                        if (current_shop && current_shop.id) {
                            //Preselect if all ready selected
                            console.log('select');
                            $('.shipmondo-shoplist-ul > li[data-id=' + current_shop.id + ']').addClass('selected');
                        }

                        ajax_success = true;
                    }
                    $('.shipmondo-modal-content').addClass('visible');
                    modal.removeClass('loading');
                } else {
                    modal_error.addClass('visible');
                    modal.removeClass('loading');
                }
            }, error: function () {
                modal_error.addClass('visible');
                modal.removeClass('loading');
            }
        });
    }


    function loadRadioButtons() {
        console.log('loadRadioButtons');

        // if (isLastSearch(true)) {
        //     return;
        // }
        //Populate data but don't stop
        isLastSearch(true);
        console.log('new search');

        var radio_container = $('.shipmondo-shipping-field-wrap .shipmondo-radio-content');
        var radio_content = $(radio_container).find('.shipmondo-removable-content');
        var radio_error = radio_container.find('.shipmondo-error');

        ajax_success = false;

        radio_error.removeClass('visible');

        radio_container.addClass('loading');

        radio_content.empty();


        //TODO maybe reuse from modal
        $.ajax({
            url: servicePointsEndpoint,
            type: 'POST',
            data: {
                'method': 'get_list',
                'carrier_code': current_search.carrier_code,
                'zip_code': current_search.zipcode,
                'address': current_search.address
            },
            success: function (response) {
                console.log('response');
                console.log(response);
                if (response) {
                    var returned = JSON.parse(response);
                    console.log('returned');
                    console.log(returned);

                    if (returned.status === "error") {
                        radio_error.html(returned.error);
                        radio_error.addClass('visible');
                    } else {
                        console.log('returned.frontend_type');
                        console.log(returned.frontend_type);

                        console.log('radio_content');
                        console.log(radio_content);

                        radio_content.html(returned.service_points_html);
                        if (current_shop && current_shop.id) {
                            //Preselect if all ready selected
                            console.log('select');
                            $('.shipmondo-shoplist-ul > li[data-id=' + current_shop.id + ']').addClass('selected');
                        }

                        ajax_success = true;
                    }
                    radio_container.removeClass('loading');
                } else {
                    radio_error.addClass('visible');
                    radio_container.removeClass('loading');
                }
            }, error: function () {
                radio_error.addClass('visible');
                radio_container.removeClass('loading');
            }
        });
    }

    function shipmondoLoadMarker(data) {
        var marker = new google.maps.Marker({
            position: {lat: parseFloat(data.latitude), lng: parseFloat(data.longitude)},
            map: map,
            icon: {
                url: moduleBaseUrl + '/views/img/' + data.carrier_code + '.png',
                //url: shipmondo[data.agent + '_icon_url'],
                size: new google.maps.Size(48, 48),
                scaledSize: new google.maps.Size(48, 48),
                anchor: new google.maps.Point(24, 24)
            }
        });

        google.maps.event.addListener(marker, 'click', (function (marker) {
            return function () {
                infowindow.setContent('<strong>' + data.company_name + '</strong><br/>' + data.address + "<br/> " + data.city + ' <br/> ' + data.zipcode + '<br/><div id="shipmondo-button-wrapper"><button class="button btn btn-primary" id="shipmondo-select-shop" data-number="' + data.number + '">' + chooseServicePointHeader + '</button></div>');
                infowindow.open(map, marker);
            };
        })(marker));

        bounds.extend(marker.position);
    }

    function shipmondoRenderMap() {
        map = new google.maps.Map(document.getElementById('shipmondo-map'), {
            zoom: 6,
            center: {lat: 55.9150835, lng: 10.4713954},
            mapTypeControl: false,
            streetViewControl: false,
            fullscreenControl: false
        });

        infowindow = new google.maps.InfoWindow();

        bounds = new google.maps.LatLngBounds();

        var pickup_points = JSON.parse($(pickup_points_json).val());

        $.each(pickup_points, function (index, element) {
            shipmondoLoadMarker(element);
        });

        setTimeout(function () {
            map.fitBounds(bounds);
        }, 100);
    }

    function shopSelected(shop) {
        console.log('shopSelected');
        console.log(shop);

        if (shop === null) {
            return;
        }

        //TODO clearing. This might not be needed
        console.log('current_shop');
        console.log(current_shop);

        //TODO This is not used for radio as we dont show it. So we want to select the right one as we do on mobile.
        if (current_shop !== shop) {
            current_shop = {
                'id': $(shop).attr('data-id'),
                'company_name': $('.input_shop_name', shop).val(),
                'address': $('.input_shop_address', shop).val(),
                'zip_code': $('.input_shop_zip', shop).val(),
                'city': $('.input_shop_city', shop).val(),
                'id_string': $('.input_shop_id', shop).val(),
                'carrier_code': $('.input_shop_carrier_code', shop).val()
            };

            console.log('add selected');

            $('.shipmondo-shop-list.selected').removeClass('selected');
            $(shop).addClass('selected');

            setSelectionSession(current_shop);
        }
        //
        // if(!current_shop.id && current_shop.address2){
        //     current_shop.id = current_shop.address2; //ID is saved
        // }

        //Not used for radio buttons
        $('input[name="shipmondo"]', hidden_chosen_shop).val(current_shop.id);
        $('input[name="shop_name"]', hidden_chosen_shop).val(current_shop.company_name);
        $('input[name="shop_address"]', hidden_chosen_shop).val(current_shop.address);
        $('input[name="shop_zip"]', hidden_chosen_shop).val(current_shop.zip_code);
        $('input[name="shop_city"]', hidden_chosen_shop).val(current_shop.city);
        $('input[name="shop_ID"]', hidden_chosen_shop).val(current_shop.id_string);
        $('input[name="shop_carrier_code"]', hidden_chosen_shop).val(current_shop.carrier_code);

        $('.shipmondo-shop-name', selected_shop_context).html(current_shop.company_name);
        $('.shipmondo-shop-address', selected_shop_context).html(current_shop.address);
        $('.shipmondo-shop-zip-and-city', selected_shop_context).html(current_shop.zip_code + ', ' + current_shop.city);
        $('.shipmondo-shop-id', selected_shop_context).html(current_shop.id_string);

        $('#selected_shop_context').addClass('active');


        //select
        // $('.shipmondo-shop-list').find("[id=+ current_shop.id+]").addClass('selected');

        showContinueBtn(true);
    }

    function setSelectionSession(shop) {
        console.log('setSelectionSession');
        console.log(shop);


        jQuery.ajax({
            url: servicePointsEndpoint,
            type: 'POST',
            data: {
                'method': "save_address",
                'company_name': shop.company_name,
                'service_point_id': shop.id,
                'address': shop.address,
                'city': shop.city,
                'zip_code': shop.zip_code,
                'carrier_code': current_search.carrier_code
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


    function getSelectionSession(callback) {
        var carrier_code = getSelectedCarrierCode();

        jQuery.ajax({
            url: servicePointsEndpoint,
            type: 'GET',
            data: {
                method: 'get_address',
                carrier_code: carrier_code
            },
            success: function (response) {
                response = JSON.parse(response);
                if (response['status'] == 'success') {
                    var servicePoint = response['service_point'];
                    if (callback) {
                        callback(servicePoint);
                    }
                } else {
                    if (callback) {
                        callback(null);
                    }
                }
            }
        });
    }


    $(document).on('click', selection_button, function (e) {
        var type = $(this).data('selection-type');
        console.log(type);

        if (type === 'popup') {
            getPickupPointsModal();
        } else if (type === 'radio') {

        } else if (type === 'dropdown' && !$(this).parents('.shipmondo_dropdown_button').hasClass('open')) {
            getPickupPointsDropdown();
            e.stopPropagation();
        }
    });

    //Prestashop copy
    function getSelectedCarrierCode() {
        console.log('getSelectedCarrierCode maybe combine with under');

        return getCarrierCodeByVal($('.delivery-option input:checked').val());
    }

    function getCarrierCodeByVal(val) {
        console.log('getCarrierCodeByVal');
        //Strip ',' etc.
        var carrierId = val.replace(/\D/g, '');

        console.log(carrierId);

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

    function showContinueBtn(show) {
        console.log('showContinueBtn');
        console.log(show);
        if (show) {
            $('#js-delivery .continue').show();
            $('.select-service-point-to-continue').hide();
        } else {
            $('#js-delivery .continue').hide();
            $('.select-service-point-to-continue').show();
        }
    }

    //Add find button
    $(document).on('click', '.delivery-option input', function () {
        console.log('click.delivery-option');

        console.log($(this).val());
        console.log(getCarrierCodeByVal($(this).val()));

        var carrier_code = getCarrierCodeByVal($(this).val());

        if (carrier_code != '') {

            // Remove zipcode wrapper
            $('.shipmondo-shipping-field-wrap').remove();

            // Find nearest delivery option TODO this?
            var dev_option = $('.delivery-option input:checked').closest('.delivery-option');
            var extra_content = $(dev_option).find('.carrier-extra-content');

            if ($(extra_content).length < 1) {
                extra_content = $(dev_option).next('.carrier-extra-content');
            }


            console.log('extra_content');
            console.log(extra_content);

            console.log('add selectedPickupPointWrapHtml');

            $(extra_content).html(selectionButton);

            console.log('$(extra_content)');
            console.log($(extra_content));

            console.log('added');
            console.log(selectionButton);

            //TODO remove this if we can set it in selection_button.tpl as WC
            $(extra_content).find('#shipmondo_find_shop_btn').data("shipping-type", carrier_code);

            console.log($(extra_content).find(selection_button));

            if (frontendType == 'radio') {
                loadRadioButtons();
            }


            console.log('carrier_code');
            console.log(carrier_code);
            console.log('current_shop');
            console.log(current_shop);

            console.log('carrier_code');
            console.log(carrier_code);

            console.log('carrier_code');
            console.log(carrier_code);


            //TODO I dont think this is enough - we should also use address etc.
            if (current_shop && (carrier_code == current_shop.carrier_code)) {
                console.log('setShop');
                shopSelected(current_shop);
            } else {
                showContinueBtn(false);
            }
        } else {
            showContinueBtn(true);
        }
    });


    //TODO move to INIT? Init modal?
    console.log('frontendType');
    console.log(frontendType);
    if (frontendType == 'popup') {
        console.log(modalHtml);
        $('body').append(modalHtml);
        modal = $('.shipmondo-modal');
        modal_content = modal.find('.shipmondo-removable-content');
        modal_error = modal.find('.shipmondo-error');


        $(modal).on('click', function (e) {
            if (typeof e.srcElement !== 'undefined' && e.srcElement.id === 'shipmondo-modal') {
                hideModal();
            }
        });

        $(modal).on('click', '.shipmondo-shop-list', function () {
            shopSelected(this);
            $('.shipmondo-modal-content').removeClass('visible');
            $('.shipmondo-modal-checkmark').addClass('visible');

            setTimeout(function () {
                hideModal();
            }, 1800);
        });

        $(modal).on('click', '#shipmondo-select-shop', function (e) {
            e.preventDefault();
            var shop = $('.shipmondo-shoplist-ul > li[data-id=' + $(this).data('number') + ']');
            shopSelected(shop);
            $('.shipmondo-modal-content').removeClass('visible');
            $('.shipmondo-modal-checkmark').addClass('visible');

            setTimeout(function () {
                hideModal();
            }, 1800);
        });

        $(document).on('shipmondo_pickup_point_modal_loaded', function () {
            shipmondoRenderMap();
        });

        $(document).on('click', close_button, function () {
            hideModal();
        });
    } else if (frontendType == 'dropdown') {
        $(document).on('click', '#shipmondo_pickup_point_selector_dropdown .shipmondo-shop-list', function () {
            shopSelected(this);
            hideDropdown();
        });

        $(document).on('click', function (e) {
            var dropdown = $('#shipmondo_pickup_point_selector_dropdown');
            // var button = $(selection_button);

            if ((!dropdown.is(e.target) && dropdown.has(e.target).length === 0) && !dropdown.hasClass('shipmondo-hidden')) {
                hideDropdown();
            }
        });
    } else if (frontendType == 'radio') {
        $(document).on('click', '.shipmondo-radio-content .shipmondo-shop-list', function () {
            console.log('click.shipmondo-shop-list');
            shopSelected(this);
            // $('.shipmondo-modal-content').removeClass('visible');
            // $('.shipmondo-modal-checkmark').addClass('visible');

            // setTimeout(function () {
            //     hideModal();
            // }, 1800);
        });

    }


    //load service points if you go back to edit
    // $('#checkout-delivery-step span.step-edit').on('click', function () {
    //     console.log('checkout-delivery-step span.step-edit.click');
    //     $('.delivery-option input:checked').trigger('click');
    // });

    //if a shipping method chosen on pageload, trigger click event of that method
    if ($('.js-current-step').attr('id') == 'checkout-delivery-step' && jQuery.inArray(jQuery('.delivery-option input:checked').val(), [glsCarrierId + ",", postnordCarrierId + ",", daoCarrierId + ",", bringCarrierId + ","]) >= 0) {
        getSelectionSession(function (shop) {
            console.log(shop);
            current_shop = shop;
            //Format data from saved fields to match what is used
            if (current_shop && !current_shop.id_string && current_shop.carrier_code && current_shop.address2) {
                current_shop.id = current_shop.address2; //ID is saved here
                current_shop.id_string = current_shop.carrier_code + '-' + current_shop.address2; //ID is saved here
            }
            $('.delivery-option input:checked').trigger('click');
        });
    }

    // Add Prevent continue button
    $('#js-delivery').append('<button type="button" class="btn btn-primary select-service-point-to-continue">' + modalHeaderTitle + '</button>');
    $(document).on('click', '.select-service-point-to-continue', function (e) {
        console.log('click.select-service-point-to-continue');
        e.preventDefault();

        //Somehow above is not working correctly so for now use timeout - but this should be solved
        setTimeout(function () {
            var find_shop_btn = $(selection_button);
            find_shop_btn.trigger("click");
            $("body,html").animate({
                    scrollTop: find_shop_btn.offset().top
                },
                800 //speed
            );
        }, 100);
    });
});