/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

jQuery(document).ready(function ($) {
    const frontendType = window.shipmondo_shipping_module.frontend_type;
    const servicePointsEndpoint = window.shipmondo_shipping_module.service_points_endpoint;
    const servicePointCarrierIds = window.shipmondo_shipping_module.service_point_carrier_ids;
    const selection_button = '.shipmondo_service_point_selection .selected_service_point';


    /* TODO
    - Håndter error
    - Håndter loading (Venter på janP)
    - generelt selector i consts så som fx. .shipmondo-original
     */


    //const shippingOptionSelector = '.delivery-option input'; //default
    //const shippingOptionSelector = 'input.delivery_option_radio'; // supercheckout


    // Get parent wrapper
    const getWrapper = function (element) {
        return element.parents('.shipmondo_service_point_selection');
    };

    const setShopHTML = function (servicePointElement, html) {
        const wrapper = getWrapper(servicePointElement)

        $(selection_button).html(html);

        wrapper.find('.service_point.selected').removeClass('selected')
        servicePointElement.addClass('selected');
    };

    // Service point selected
    const ServicePointSelected = function (shopElement) {
        const data = shopElement.data();
        data.action = "update";

        $.ajax({
            url: servicePointsEndpoint, type: 'POST', data: data, dataType: 'json', error: function (response) {
                console.error('error', response);
                //TODO SHOW ERROR? look at WC
                // Error
                $(".shipmondo_service_point_selection").html('<div class="selected_service_point service_point dropdown no_service_point">Ingen tilgængelige udleveringssteder</div>');
            }, success: function (response) {
                if (response.status === "success") {
                    setShopHTML(shopElement, response.selected_service_point_html)
                } else if (response.status === "error") {
                    //TODO SHOW ERROR? look at WC
                    $(".shipmondo_service_point_selection").html('<div class="selected_service_point service_point dropdown no_service_point">Ingen tilgængelige udleveringssteder</div>');
                }
            }
        });
    };

    //$(document).on('click', shippingOptionSelector, function (event) {
    $(document).on('click', ((window.Shipmondo && window.Shipmondo.deliveryOptionInputContainerSelector) ? window.Shipmondo.deliveryOptionInputContainerSelector : '.delivery-option') + ' input', function (event) {
        const carrierID = parseInt($(this).val().replace(/\D/g, ''));
        const containerEl = $('#shipmondo-service-points-container');
        const contentEl = containerEl.find('.shipmondo-service-points-content');
        console.log('delivery-option clicked maybe add loader?');
        console.log('carrierID', carrierID);


        $.each(servicePointCarrierIds, function (index, id) {
            console.log('id', id)
        });

//TODO Du er kommet her til . Det er ud til at virke OK men du skal bruge klasser i stedet for at tilføje meget ens html. h3 og powered by fx. burde være der altid
        console.log(servicePointCarrierIds.includes(carrierID));
        if (servicePointCarrierIds.includes(carrierID)) {
            //containerEl.html('<div class="selected_service_point loading" style="height: 82px;display: flex;align-items: center;justify-content: center;">Arbejder...</div>');
            contentEl.html('<div class="selected_service_point loading">Arbejder...</div>');
            containerEl.show();

            $.ajax({
                url: servicePointsEndpoint,
                type: 'GET',
                dataType: 'json',
                data: {
                    action: 'get', carrier_id: carrierID
                }, success: function (response) {
                    if (response.status === 'success') {
                        contentEl.html(response.service_point_html);
                    } else if (response.status === 'error') {
                        contentEl.html('<div class="selected_service_point loading no_service_point has-error">Error</div>');
                        console.error('Shipmondo:', response.error);
                    }
                }
            });
        } else {
            containerEl.hide();
        }
    });


    // DROPDOWN
    if (frontendType === 'dropdown') {
        const getDropdown = function (element) {
            return getWrapper(element).find('.shipmondo-dropdown_wrapper');
        };

        const openDropdown = function (dropdownElement) {
            dropdownElement.addClass('visible');
            getWrapper(dropdownElement).find('.selected_service_point').addClass('selector_open');
        };

        const closeDropdown = function (dropdownElement) {
            dropdownElement.removeClass('visible');
            getWrapper(dropdownElement).find('.selected_service_point').removeClass('selector_open');
        };

        const toggleDropdown = function (element) {
            const dropdown = getDropdown(element);

            console.log('dropdown', dropdown);
            console.log('element', element);
            if (dropdown.hasClass('visible')) {
                closeDropdown(dropdown);
            } else {
                openDropdown(dropdown);
            }
        };

        $(document).on('click', selection_button, function (e) {
            e.stopPropagation();
            toggleDropdown($(this));
        });

        // Hide dropdown when clicked outsite of it
        $(document).on('click', function (e) {
            const dropdown = $('.shipmondo-original .shipmondo-dropdown_wrapper.visible');

            if (dropdown.length > 0 && (!dropdown.is(e.target) && dropdown.has(e.target).length === 0)) {
                closeDropdown(dropdown);
            }
        })

        //TODO USE selection_button instead
        // Set selected shop
        $(document).on('click', '.shipmondo-original .selector_type-dropdown .service_points_list .service_point', function () {
            ServicePointSelected($(this));
            closeDropdown(getDropdown($(this)));
        });
    } else {
        // MODAL
        let map = null;
        let bounds = null;
        let googleMapsIsLoaded = false;
        const body = $('body');

        const getModal = function (element) {
            return getWrapper(element).find('.shipmondo-modal');
        };

        const openModal = function (modal) {
            modal.removeClass('shipmondo-hidden');

            if (googleMapsIsLoaded) {
                renderMap(modal);
            }

            setTimeout(function () {
                body.addClass('shipmondo_modal_open');
                modal.addClass('visible');
                modal.find('.shipmondo-modal_content').addClass('visible');
            }, 100)
        };

        const closeModal = function (modal) {
            modal.removeClass('visible');
            body.removeClass('shipmondo_modal_open');

            setTimeout(function () {
                $('.shipmondo-modal-checkmark').removeClass('visible');
                modal.addClass('shipmondo-hidden');
            }, 300);
        };

        const renderMap = function (modal) {
            const mapEl = modal.find('.service_points_map');
            map = new google.maps.Map(mapEl[0], {
                zoom: 6,
                center: {lat: 55.9150835, lng: 10.4713954},
                mapTypeControl: false,
                streetViewControl: false,
                fullscreenControl: false
            });

            bounds = new google.maps.LatLngBounds();

            //TODO get data array directly instead
            const servicePoints = modal.find('.service_points_list .service_point');

            $.each(servicePoints, function (index, element) {
                shipmondoLoadMarker($(element));
            });

            setTimeout(function () {
                map.fitBounds(bounds);
            }, 100)
        };


        const selectServicePointFromMarker = function (service_point_id, modal) {
            const servicePointElement = modal.find('.service_points_list .service_point[data-service_point_id=' + service_point_id + ']');
            servicePointElement.trigger('click');
        };

        // Render Markers
        const shipmondoLoadMarker = function (servicePointEl) {
            const marker = new google.maps.Marker({
                position: {
                    lat: parseFloat(servicePointEl.data('latitude')), lng: parseFloat(servicePointEl.data('longitude'))
                }, map: map, icon: {
                    url: window.shipmondo_shipping_module.module_base_url + '/views/img/' + (servicePointEl.hasClass('selected') ? 'picker_green' : 'picker_default') + '.png',
                    size: new google.maps.Size(48, 48),
                    scaledSize: new google.maps.Size(48, 48),
                    anchor: new google.maps.Point(24, 24)
                }
            })

            google.maps.event.addListener(marker, 'click', (function (marker) {
                return function () {
                    const modal = getModal(servicePointEl);
                    selectServicePointFromMarker(servicePointEl.data('service_point_id'), modal);
                }
            })(marker))

            bounds.extend(marker.position);
        };

        // Select shop
        $(document).on('click', '.shipmondo-original .selector_type-modal .service_points_list .service_point', function () {
            ServicePointSelected($(this));

            $('.shipmondo-modal_content').removeClass('visible');
            $('.shipmondo-modal-checkmark').addClass('visible');

            const modal = getModal($(this));

            setTimeout(function () {
                closeModal(modal);
            }, 1800);
        });


        // Show modal
        $(document).on('click', '.shipmondo-original .selected_service_point.selector_type-modal', function (e) {
            e.stopPropagation();
            openModal(getModal($(e.target)));
        });

        // Hide modal on close button
        $(document).on('click', '.shipmondo-original .shipmondo-modal_close', function (e) {
            e.preventDefault();
            closeModal(getModal($(e.target)));
        });

        // Hide modal when clicking outsite modal content
        $(document).on('click', '.shipmondo-original .shipmondo-modal', function (e) {
            if (typeof e.target !== 'undefined' && $(e.target).hasClass('shipmondo-modal')) {
                closeModal(getModal($(e.target)));
            }
        });

        window.googleMapsInit = function googleMapsInit() {
            googleMapsIsLoaded = true;
        }
    }

    console.log("$('.delivery-option input:checked')", $('.delivery-option input:checked'));


    //INIT CLICK  (only work on default)
    const current_radio = $('.delivery-option input:checked');
    if (current_radio.val()) {
        current_radio.trigger('click');
    }
});