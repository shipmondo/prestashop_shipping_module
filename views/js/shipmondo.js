/**
 *  @author    Shipmondo <support@shipmondo.com>
 *  @copyright 2024-present Shipmondo
 *  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
 */

jQuery(document).ready(function ($) {
    const frontend_type = window.frontend_type;
    const service_points_endpoint = window.service_points_endpoint;
    const selection_button = '.shipmondo_service_point_selection .selected_service_point';


    /* TODO
    - Ryd op i CSS (Gammel og ubrugt)
    - Håndter error
    - Håndter loading (Venter på janP)
    - skift til const hej(){} i stedet for function pga. scooping
    - generelt selector i consts så som fx. .shipmondo-original
     */

    // Get parent wrapper
    function getWrapper(element) {
        return element.parents('.shipmondo_service_point_selection');
    }

    function setShopHTML(servicePointElement, html) {
        const wrapper = getWrapper(servicePointElement)

        $(selection_button).html(html);

        wrapper.find('.service_point.selected').removeClass('selected')
        servicePointElement.addClass('selected');
    }

    // Service point selected
    function ServicePointSelected(shopElement) {
        const data = shopElement.data();
        data.action = "update";

        $.ajax({
            url: service_points_endpoint, type: 'POST', data: data, dataType: 'json', error: function (response) {
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
    }

    $(document).on('click', ((window.Shipmondo && window.Shipmondo.deliveryOptionInputContainerSelector) ? window.Shipmondo.deliveryOptionInputContainerSelector : '.delivery-option') + ' input', function (event) {
        const carrier_id = $(this).val().replace(/\D/g, '');

        console.log('delivery-option clicked maybe add loader?')

        $.ajax({
            url: service_points_endpoint, type: 'GET', data: {
                action: 'get', carrier_id: carrier_id
            }, success: function (response) {
                response = JSON.parse(response);

                if (response['status'] === 'success') {
                    const html = response['service_point_html'];
                    $('#shipmondo-service-points-container').html(html);
                }
            }
        });
    });

    // DROPDOWN
    if (frontend_type === 'dropdown') {
        function getDropdown(element) {
            return getWrapper(element).find('.shipmondo-dropdown_wrapper');
        }

        function openDropdown(dropdownElement) {
            dropdownElement.addClass('visible');
            getWrapper(dropdownElement).find('.selected_service_point').addClass('selector_open');
        }

        function closeDropdown(dropdownElement) {
            dropdownElement.removeClass('visible');
            getWrapper(dropdownElement).find('.selected_service_point').removeClass('selector_open');
        }

        function toggleDropdown(element) {
            const dropdown = getDropdown(element);

            console.log('dropdown', dropdown);
            console.log('element', element);
            if (dropdown.hasClass('visible')) {
                closeDropdown(dropdown);
            } else {
                openDropdown(dropdown);
            }
        }

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

        function getModal(element) {
            return getWrapper(element).find('.shipmondo-modal');
        }

        function openModal(modal) {
            modal.removeClass('shipmondo-hidden');

            if (googleMapsIsLoaded) {
                renderMap(modal);
            }

            setTimeout(function () {
                body.addClass('shipmondo_modal_open');
                modal.addClass('visible');
                modal.find('.shipmondo-modal_content').addClass('visible');
            }, 100)
        }

        function closeModal(modal) {
            modal.removeClass('visible');
            body.removeClass('shipmondo_modal_open');

            setTimeout(function () {
                $('.shipmondo-modal-checkmark').removeClass('visible');
                modal.addClass('shipmondo-hidden');
            }, 300);
        }

        function renderMap(modal) {
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
        }


        function selectServicePointFromMarker(service_point_id, modal) {
            const servicePointElement = modal.find('.service_points_list .service_point[data-service_point_id=' + service_point_id + ']');
            servicePointElement.trigger('click');
        }

        // Render Markers
        function shipmondoLoadMarker(servicePointEl) {
            const marker = new google.maps.Marker({
                position: {
                    lat: parseFloat(servicePointEl.data('latitude')),
                    lng: parseFloat(servicePointEl.data('longitude'))
                },
                map: map,
                icon: {
                    url: module_base_url + '/views/img/' + (servicePointEl.hasClass('selected') ? 'picker_green' : 'picker_default') + '.png',
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
        }

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
});