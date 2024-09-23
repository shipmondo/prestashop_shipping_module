{*
*  @author    Shipmondo
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="shipmondo-shipping-field-wrap">
    <input type="hidden" name="shipmondo_carrier_code_{$carrier_id}" value="{$carrier_code}">
    <div class="shipmondo-clearfix" id="shipmondo_shipping_button">
        <div class="shipmondo_stores">
            <div class="shipmondo_dropdown_button">
                <button class="button button-medium btn btn-primary" id="shipmondo_find_shop_btn"
                    name="shipmondo_find_shop" type="button" data-selection-type="dropdown">
                    {l s='Find nearest pickup point' mod='shipmondo'}
                </button>
            </div>
            <div id="shipmondo_pickup_point_selector_dropdown_container">
                <div id="shipmondo_pickup_point_selector_dropdown" class="shipmondo-hidden">
                    <div class="shipmondo-dropdown-content-section">
                        <div class="shipmondo-loader-wrapper">
                            <div class="shipmondo-loader"></div>
                        </div>
                        <div class="shipmondo-removable-content"></div>
                        <div class="shipmondo-error">
                            {include file='module:shipmondo/views/templates/front/_partials/close_button.tpl'}
                            <p>Something went wrong, please try again!'</p>
                            <button class="shipmondo-modal-close-button button alt">Close</button>
                        </div>
                    </div>
                    <div class="shipmondo-dropdown-footer">
                        Powered by Shipmondo
                    </div>
                </div>
            </div>
        </div>
    </div>
    {include file='module:shipmondo/views/templates/front/_partials/selected_service_point.tpl'}
</div>