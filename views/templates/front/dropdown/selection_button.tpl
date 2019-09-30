{*
*  @author    Shipmondo
*  @copyright 2019 Shipmondo
*  @license   All rights reserved
*}

<div class="shipmondo-shipping-field-wrap">
    <div class="shipmondo-clearfix" id="shipmondo_shipping_button">
        <div class="shipmondo_stores">
            <div class="shipmondo_dropdown_button">
                <button class="button button-medium btn btn-primary" id="shipmondo_find_shop_btn" name="shipmondo_find_shop" type="button"
                        data-selection-type="dropdown">
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
                            {include file='module:shipmondo/views/templates/front/popup/partials/close_button.tpl'}
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
    <div id="hidden_chosen_shop">
        <input type="hidden" name="shipmondo">
        <input type="hidden" name="shop_name">
        <input type="hidden" name="shop_address">
        <input type="hidden" name="shop_zip">
        <input type="hidden" name="shop_city">
        <input type="hidden" name="shop_carrier_code">
        <input type="hidden" name="shop_ID">
    </div>
    <div class="shipmondo-clearfix" id="selected_shop_context">
        <div class="shipmondo-shop-header">{l s='Currently chosen pickup point:' mod='shipmondo'}</div>
        <div class="shipmondo-shop-name"></div>
        <div class="shipmondo-shop-address"></div>
        <div class="shipmondo-shop-zip-and-city"></div>
        <div class="shipmondo-shop-id"></div>
    </div>
</div>