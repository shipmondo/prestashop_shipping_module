<div class="shipmondo-shipping-field-wrap">
    <div class="shipmondo-clearfix" id="shipmondo_shipping_button">
        <div class="shipmondo_stores">
            <!--<div>-->
                <!--<input disabled type="button" id="shipmondo_find_shop_btn" name="shipmondo_find_shop" class="button alt"-->
                       <!--value="{l s='Find nearest pickup point' mod='shipmondo'}" data-shipping-type="modal"-->
                       <!--data-selection-type="modal">-->
            <!--</div>-->
            <button class="button button-medium btn btn-primary" id="shipmondo_find_shop_btn" name="shipmondo_find_shop" type="button"
                    data-selection-type="popup">
                <!--data-shipping-type="frontendType" if possible insert selected agent here if not- keep js-->
                {l s='Find nearest pickup point' mod='shipmondo'}
            </button>
        </div>
    </div>

    {*
    <!--<div id="hidden_chosen_shop">-->
    <!--<input type="hidden" name="shipmondo" value="<?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::getCurrentSelection('id', $shipping_type); ?>">-->
    <!--<input type="hidden" name="shop_name" value="<?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::getCurrentSelection('name', $shipping_type); ?>">-->
    <!--<input type="hidden" name="shop_address" value="<?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::getCurrentSelection('address', $shipping_type); ?>">-->
    <!--<input type="hidden" name="shop_zip" value="<?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::getCurrentSelection('zip', $shipping_type); ?>">-->
    <!--<input type="hidden" name="shop_city" value="<?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::getCurrentSelection('city', $shipping_type); ?>">-->
    <!--<input type="hidden" name="shop_ID" value="<?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::getCurrentSelection('id_string', $shipping_type); ?>">-->
    <!--</div>-->
    <!--<div class="shipmondo-clearfix<?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::isCurrentSelection($shipping_type) ? ' active' : ''; ?>" id="selected_shop_context">-->
    <!--<div class="shipmondo-shop-header"><?php echo __('Currently choosen pickup point:', 'pakkelabels-for-woocommerce'); ?></div>-->
    <!--<div class="shipmondo-shop-name"><?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::getCurrentSelection('name', $shipping_type); ?></div>-->
    <!--<div class="shipmondo-shop-address"><?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::getCurrentSelection('address', $shipping_type); ?></div>-->
    <!--<div class="shipmondo-shop-zip-and-city"><?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::getCurrentSelection('zip_city', $shipping_type); ?></div>-->
    <!--<div class="shipmondo-shop-id"><?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::getCurrentSelection('id_string', $shipping_type); ?></div>-->
    <!--</div>-->
    *}

    <div id="hidden_chosen_shop">
        <input type="hidden" name="shipmondo">
        <input type="hidden" name="shop_name">
        <input type="hidden" name="shop_address">
        <input type="hidden" name="shop_zip">
        <input type="hidden" name="shop_city">
        <input type="hidden" name="shop_agent">
        <input type="hidden" name="shop_ID">
    </div>
    <div class="shipmondo-clearfix" id="selected_shop_context">
        <div class="shipmondo-shop-header">{l s='Currently chosen pickup point:' mod='shipmondo'}</div>
        <div class="shipmondo-shop-name"></div>
        <div class="shipmondo-shop-address"></div>
        <div class="shipmondo-shop-zip-and-city"></div>
        <div class="shipmondo-shop-id"></div>
    </div>


    {*
    <!--<div id="shipmondo_zicode_error_text" class="<?php echo \ShipmondoForWooCommerce\Plugin\Controllers\PickupPoint::isCurrentSelection($shipping_type) ? '' : 'active'; ?>">-->
    <!--{l s='Please enter a zipcode to select a pickup point' mod='shipmondo'}-->
    <!--</div>-->
    *}
</div>