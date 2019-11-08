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
    var current_shop = null;
    var infowindow;
    var hidden_chosen_shop = '#hidden_chosen_shop';
    var selected_shop_context = '#selected_shop_context';
    var last_address = null;
    var last_carrier_code = null;

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

    function getServicePointsEndpoint(successCallback, errorCallback) {
        var current_carrier_code = getSelectedCarrierCode();

        console.log(last_address);
        $.ajax({
            url: service_points_endpoint,
            type: 'POST',
            data: {
                'method': 'get_list',
                'carrier_code': current_carrier_code,
                'last_carrier_code': last_carrier_code,
                'last_address': last_address
            },
            success: function (response) {
                if (response) {
                    last_carrier_code = current_carrier_code; //keep track of changes
                    successCallback(JSON.parse(response));
                } else {
                    errorCallback(response)
                }
            }, error: function (response) {
                errorCallback(response)
            }
        });
    }

    function getPickupPointsDropdown() {
        var dropdown = $('#shipmondo_pickup_point_selector_dropdown');
        var dropdown_button = $('.shipmondo_dropdown_button');
        var dropdown_content = dropdown.find('.shipmondo-removable-content');
        var dropdown_error = dropdown.find('.shipmondo-error');
        var existing_content = dropdown_content.html();

        dropdown.removeClass('shipmondo-hidden');

        dropdown_button.addClass('open');

        dropdown_error.removeClass('visible');

        dropdown.addClass('loading');

        dropdown_content.empty();

        getServicePointsEndpoint(function (response) {
            if (response.status === "error") {
                dropdown_error.html(response.error);
                dropdown_error.addClass('visible');
            } else {
                if (response.address_changed) {
                    last_address = response.new_address; //TODO move to other
                    dropdown_content.html(response.service_points_html);
                } else {
                    dropdown_content.html(existing_content);
                }
            }
            $('.shipmondo-modal-content').addClass('visible');
            dropdown.removeClass('loading');
        }, function () {
            dropdown_error.addClass('visible');
            dropdown.removeClass('loading');
        });
    }

    function getPickupPointsModal() {
        var existing_content = modal_content.html();

        modal.removeClass('shipmondo-hidden');
        setTimeout(function () {
            body.addClass('shipmondo-modal-open');
            modal.addClass('visible');
        }, 100);

        modal.addClass('loading');

        modal_content.empty();

        getServicePointsEndpoint(function (response) {
            if (response.status === "error") {
                if (response.error) {
                    modal_error.find('p').html(response.error);
                    modal_error.addClass('visible');
                }
            } else {
                if (response.address_changed) {
                    last_address = response.new_address; //TODO move to other

                    modal_content.html(response.service_points_html);
                    //Set selected
                    if (current_shop && current_shop.id) {
                        var current_li = $('.shipmondo-shoplist-ul > li[data-id=' + current_shop.id + ']');
                        $('.custom-radio input', current_li).prop('checked', true);
                    }
                } else {
                    modal_content.html(existing_content);
                }
            }
            $('.shipmondo-modal-content').addClass('visible');
            modal.removeClass('loading');
        }, function () {
            modal_error.addClass('visible');
            modal.removeClass('loading');
        });
    }


    function loadRadioButtons() {
        var radio_container = $('.shipmondo-shipping-field-wrap .shipmondo-radio-content');
        var radio_content = $(radio_container).find('.shipmondo-removable-content');
        var radio_error = radio_container.find('.shipmondo-error');
        var existing_content = radio_content.html();

        radio_error.removeClass('visible');

        radio_container.addClass('loading');

        radio_content.empty();

        getServicePointsEndpoint(function (response) {
            if (response.status === "error") {
                radio_error.html(response.error);
                radio_error.addClass('visible');
            } else {
                if (response.address_changed) {
                    radio_content.html(response.service_points_html);
                } else {
                    radio_content.html(existing_content);
                }

                if (current_shop && current_shop.id) {
                    //Preselect if all ready selected
                    var current_li = $('.shipmondo-shoplist-ul > li[data-id=' + current_shop.id + ']');
                    if (current_li.size() > 0) {
                        $('.custom-radio input', current_li).prop('checked', true);
                    } else {
                        shopSelected($('.shipmondo-shoplist-ul > li').first());
                    }
                } else {
                    shopSelected($('.shipmondo-shoplist-ul > li').first());
                }
            }
            radio_container.removeClass('loading');
        }, function () {
            radio_error.addClass('visible');
            radio_container.removeClass('loading');
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
        $('input[name="shop_carrier_code"]', hidden_chosen_shop).val(current_shop.carrier_code);

        $('.shipmondo-shop-name', selected_shop_context).html(current_shop.company_name);
        $('.shipmondo-shop-address', selected_shop_context).html(current_shop.address);
        $('.shipmondo-shop-zip-and-city', selected_shop_context).html(current_shop.zip_code + ', ' + current_shop.city);

        $('#selected_shop_context').addClass('active'); //TODO delete - legacy?


        showContinueBtn(true);
    }

    function setSelectionSession(shop) {
        $.ajax({
            url: service_points_endpoint,
            type: 'POST',
            data: {
                'method': "save_address",
                'company_name': shop.company_name,
                'service_point_id': shop.id,
                'address': shop.address,
                'city': shop.city,
                'zip_code': shop.zip_code,
                'carrier_code': last_carrier_code
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

        $.ajax({
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
        return getCarrierCodeByVal($(((window.Shipmondo && window.Shipmondo.deliveryOptionInputContainerSelector) ? window.Shipmondo.deliveryOptionInputContainerSelector : '.delivery-option') + ' input:checked').val());
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

    //Add find button
    $(document).on('click', ((window.Shipmondo && window.Shipmondo.deliveryOptionInputContainerSelector) ? window.Shipmondo.deliveryOptionInputContainerSelector : '.delivery-option') + ' input', function (event) {
        var carrier_code = getCarrierCodeByVal($(this).val());

        // Remove wrapper
        $('.shipmondo-shipping-field-wrap').remove();

        if (carrier_code != '') {
            // // Remove wrapper
            // $('.shipmondo-shipping-field-wrap').remove();

            var dev_option = $(this).closest((window.Shipmondo && window.Shipmondo.deliveryOptionRowSelector) ? window.Shipmondo.deliveryOptionRowSelector : '.delivery-option'); //row
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
            $('.delivery-option input:checked').trigger('click');
        });
    }

    //TODO only add prevent button where it works. So check if the html exists before adding. If we want to support it in all plugin we need to do below more generic:
    function showContinueBtn(show) {
        if (show) {
            $(('#js-delivery .continue')).show();
            $('.select-service-point-to-continue').hide();
        } else {
            $(('#js-delivery .continue')).hide();
            $('.select-service-point-to-continue').show();
        }
        // window.SMShowContinueBtn = show;
        //
        // if (show) {
        //     $((window.SMContinueBtnSelector ? window.SMContinueBtnSelector : '#js-delivery .continue')).show();
        //     $('.select-service-point-to-continue').hide();
        // } else {
        //     $((window.SMContinueBtnSelector ? window.SMContinueBtnSelector :'#js-delivery .continue')).hide();
        //     $('.select-service-point-to-continue').show();
        // }
    }

    //load service points if you go back to edit
    $('#checkout-delivery-step span.step-edit').on('click', function () {
        setCurrentShopBySession();
    });

    //if a shipping method chosen on pageload, trigger click event of that method
    if ($('.js-current-step').attr('id') == 'checkout-delivery-step' && $.inArray($('.delivery-option input:checked').val(), [gls_carrier_id + ",", postnord_carrier_id + ",", dao_carrier_id + ",", bring_carrier_id + ","]) >= 0) {
        setCurrentShopBySession();
    }

    // Add Prevent continue button -
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