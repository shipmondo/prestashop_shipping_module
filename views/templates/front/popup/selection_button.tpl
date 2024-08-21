{*
*  @author    Shipmondo
*  @copyright 2023 Shipmondo
*  @license   All rights reserved
*}

<div class="shipmondo-shipping-field-wrap">
    <input type="hidden" name="shipmondo_carrier_code" value="{$carrier_code}">
    <div class="shipmondo-clearfix" id="shipmondo_shipping_button">
        <div class="shipmondo_stores">
            <button class="button button-medium btn btn-primary" id="shipmondo_find_shop_btn" name="shipmondo_find_shop" type="button"
                    data-selection-type="popup">
                {l s='Find nearest pickup point' mod='shipmondo'}
            </button>
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