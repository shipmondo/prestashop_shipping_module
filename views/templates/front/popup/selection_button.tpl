{*
*  @author    Shipmondo
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="shipmondo-shipping-field-wrap">
    <input type="hidden" name="shipmondo_carrier_code_{$carrier_id}" value="{$carrier_code}">
    <div class="shipmondo-clearfix" id="shipmondo_shipping_button">
        <div class="shipmondo_stores">
            <button class="button button-medium btn btn-primary" id="shipmondo_find_shop_btn" name="shipmondo_find_shop" type="button"
                    data-selection-type="popup">
                {l s='Find nearest pickup point' mod='shipmondo'}
            </button>
        </div>
    </div>
    {include file='module:shipmondo/views/templates/front/_partials/selected_service_point.tpl'}
</div>