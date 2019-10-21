/*
 @author    Shipmondo
 @copyright 2019 Shipmondo
 @license   All rights reserved
 */

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
        if (!id_delivery && window.SMGetDeliveryAddressID && window.SMGetDeliveryAddressID()) {
            id_delivery = window.SMGetDeliveryAddressID();
        } else {
            id_delivery = jQuery('input[name="id_address_delivery"]:checked').val();
        }

        var address_data = prestashop.customer.addresses[id_delivery];

        if (!address_data) {
            alert('Shipmondo - Error');
            return
        }

        return {
            carrier_code: getSelectedCarrierCode(),
            address: address_data.address1,
            zipcode: address_data.postcode,
            country: address_data.country
        };
    }

    function isLastSearch(search) {
        var last_search = current_search;
        var _current_search = getAddress();

        if (last_search !== null && ajax_success && _current_search.carrier_code === last_search.carrier_code && _current_search.zipcode === last_search.zipcode && _current_search.address === last_search.address && _current_search.country === last_search.country) {
            return true;
        }

        if (search === true) {
            current_search = _current_search;
        }

        return false;
    }

    function getPickupPointsDropdown() {
        var dropdown = $('#shipmondo_pickup_point_selector_dropdown');
        dropdown.removeClass('shipmondo-hidden');

        var dropdown_button = $('.shipmondo_dropdown_button');
        dropdown_button.addClass('open');

        var dropdown_error = dropdown.find('.shipmondo-error');
        dropdown_error.removeClass('visible');

        var dropdown_content = dropdown.find('.shipmondo-removable-content');

        if (isLastSearch(true)) {
            return;
        }

        dropdown.addClass('loading');

        dropdown_content.empty();

        ajax_success = false;

        //TODO maybe reuse from modal
        jQuery.ajax({
            url: service_points_endpoint,
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

                    if (returned.status === "error") {
                        dropdown_error.html(returned.error);
                        dropdown_error.addClass('visible');
                    } else {
                        dropdown_content.html(returned.service_points_html);

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

        $.ajax({
            url: service_points_endpoint,
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
                    if (returned.status === "error") {
                        if (returned.error) {
                            modal_error.find('p').html(returned.error);
                            modal_error.addClass('visible');
                        }
                    } else {
                        modal_content.html(returned.service_points_html);
                        //Set selected
                        if (current_shop && current_shop.id) {
                            // $('.shipmondo-shoplist-ul > li[data-id=' + current_shop.id + ']').addClass('selected');
                            var current_li = $('.shipmondo-shoplist-ul > li[data-id=' + current_shop.id + ']');
                            $('.custom-radio input', current_li).prop('checked', true);
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
        //Populate data but don't stop
        isLastSearch(true);

        var radio_container = $('.shipmondo-shipping-field-wrap .shipmondo-radio-content');
        var radio_content = $(radio_container).find('.shipmondo-removable-content');
        var radio_error = radio_container.find('.shipmondo-error');

        ajax_success = false;

        radio_error.removeClass('visible');

        radio_container.addClass('loading');

        radio_content.empty();

        //TODO maybe reuse from modal
        $.ajax({
            url: service_points_endpoint,
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
                    if (returned.status === "error") {
                        radio_error.html(returned.error);
                        radio_error.addClass('visible');
                    } else {
                        radio_content.html(returned.service_points_html);
                        if (current_shop && current_shop.id) {
                            //Preselect if all ready selected
                            var current_li = $('.shipmondo-shoplist-ul > li[data-id=' + current_shop.id + ']');
                            if (current_li.size() > 0) {
                                // current_li.addClass('selected');
                                $('.custom-radio input', current_li).prop('checked', true);
                            } else {
                                shopSelected($('.shipmondo-shoplist-ul > li').first());
                            }
                        } else {
                            shopSelected($('.shipmondo-shoplist-ul > li').first());
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
                url: module_base_url + '/views/img/' + data.carrier_code + '.png',
                size: new google.maps.Size(48, 48),
                scaledSize: new google.maps.Size(48, 48),
                anchor: new google.maps.Point(24, 24)
            }
        });

        google.maps.event.addListener(marker, 'click', (function (marker) {
            return function () {
                infowindow.setContent('<strong>' + data.company_name + '</strong><br/>' + data.address + "<br/> " + data.city + ' <br/> ' + data.zipcode + '<br/><div id="shipmondo-button-wrapper"><button class="button btn btn-primary" id="shipmondo-select-shop" data-number="' + data.number + '">' + choose_service_point_header + '</button></div>');
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
        if (shop === null) {
            return;
        }

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

            // $('.shipmondo-shop-list.selected').removeClass('selected');
            $('.shipmondo-shop-list .custom-radio input:checked').prop('checked', false);

            // $(shop).addClass('selected');
            $('.custom-radio input', shop).prop('checked', true);

            setSelectionSession(current_shop);
        }

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


        showContinueBtn(true);
    }

    function setSelectionSession(shop) {
        jQuery.ajax({
            url: service_points_endpoint,
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
                    // $(".error_msg").html(noPointSelectedErrorText);
                }
            }
        });
    }


    function getSelectionSession(callback) {
        var carrier_code = getSelectedCarrierCode();

        jQuery.ajax({
            url: service_points_endpoint,
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
        return getCarrierCodeByVal($('.delivery-option input:checked').val());
    }

    function getCarrierCodeByVal(val) {
        //Strip ',' etc.
        var carrierId = val.replace(/\D/g, '');

        switch (parseInt(carrierId)) {
            case gls_carrier_id:
                return 'gls';
            case dao_carrier_id:
                return 'dao';
            case postnord_carrier_id:
                return 'pdk';
            case bring_carrier_id:
                return 'bring';
            default:
                return ''
        }
    }

    function showContinueBtn(show) {
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
        var carrier_code = getCarrierCodeByVal($(this).val());

        if (carrier_code != '') {
            // Remove zipcode wrapper
            $('.shipmondo-shipping-field-wrap').remove();

            var dev_option = $('.delivery-option input:checked').closest('.delivery-option');
            var extra_content = $(dev_option).find('.carrier-extra-content');

            if ($(extra_content).length < 1) {
                extra_content = $(dev_option).next('.carrier-extra-content');
            }

            $(extra_content).html(selection_button_html);

            //TODO remove this if we can set it in selection_button.tpl as WC
            $(extra_content).find('#shipmondo_find_shop_btn').data("shipping-type", carrier_code);

            if (frontend_type == 'radio') {
                loadRadioButtons();
            }

            //TODO I dont think this is enough - we should also use address etc.
            if (current_shop && (carrier_code == current_shop.carrier_code)) {
                shopSelected(current_shop);
            } else {
                showContinueBtn(false);
            }
        } else {
            showContinueBtn(true);
        }
    });

    //TODO move to INIT? Init modal?
    if (frontend_type == 'popup') {
        body.append(modal_html);
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
    } else if (frontend_type == 'dropdown') {
        $(document).on('click', '#shipmondo_pickup_point_selector_dropdown .shipmondo-shop-list', function () {
            shopSelected(this);
            hideDropdown();
        });

        $(document).on('click', function (e) {
            var dropdown = $('#shipmondo_pickup_point_selector_dropdown');

            if ((!dropdown.is(e.target) && dropdown.has(e.target).length === 0) && !dropdown.hasClass('shipmondo-hidden')) {
                hideDropdown();
            }
        });
    } else if (frontend_type == 'radio') {
        $(document).on('click', '.shipmondo-radio-content .shipmondo-shop-list', function () {
            shopSelected(this);
        });
    }


    function setCurrentShopBySession() {
        getSelectionSession(function (shop) {
            current_shop = shop;
            //Format data from saved fields to match what is used
            if (current_shop && !current_shop.id_string && current_shop.carrier_code && current_shop.address2) {
                current_shop.id = current_shop.address2; //ID is saved here
                current_shop.id_string = current_shop.carrier_code + '-' + current_shop.address2; //ID is saved here
            }
            $('.delivery-option input:checked').trigger('click');
        });
    }

    //load service points if you go back to edit
    $('#checkout-delivery-step span.step-edit').on('click', function () {
        setCurrentShopBySession();
    });

    //if a shipping method chosen on pageload, trigger click event of that method
    if ($('.js-current-step').attr('id') == 'checkout-delivery-step' && jQuery.inArray(jQuery('.delivery-option input:checked').val(), [gls_carrier_id + ",", postnord_carrier_id + ",", dao_carrier_id + ",", bring_carrier_id + ","]) >= 0) {
        setCurrentShopBySession();
    }

    // Add Prevent continue button
    $('#js-delivery').append('<button type="button" class="btn btn-primary select-service-point-to-continue">' + modal_header_title + '</button>');
    $(document).on('click', '.select-service-point-to-continue', function (e) {
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