{*
*  @author    Shipmondo
*  @copyright 2023 Shipmondo
*  @license   All rights reserved
*}

<div class="shipmondo-modal-content">
    {include file='module:shipmondo/views/templates/front/_partials/close_button.tpl'}
    <div class="shipmondo-modal-header">
        <h4>{l s='Choose pickup point' mod='shipmondo'}</h4>
        <p class="shipmondo-pickoup-point-counter" id="shipmondo-pickup-point-counter">
            {*
            <?php echo sprintf(_n('%s pickup point found', '%s pickup points found', $pickup_points_number,'pakkelabels-for-woocommerce'), $pickup_points_number); ?>
            *}
            {$service_points_count}
        </p>
    </div>
    <div class="shipmondo-modal-body">
        <div id="shipmondo-map-wrapper">
            <div id="shipmondo-map"></div>
            <input type="hidden" name="shipmondo_pickup_points_json" value='{$service_points_json}'>
            <script>
                jQuery(document).trigger('shipmondo_pickup_point_modal_loaded');
            </script>
        </div>
        <div id="shipmondo-list-wrapper">
            <ul class="shipmondo-shoplist-ul">
                {foreach $service_points as $service_point}
                <li class="shipmondo-shop-list" data-id="{$service_point->number}">
                    <div class="shipmondo-pickup-point-info">
                        <input type="hidden" class="input_shop_carrier_code" id="shop_carrier_code_{$service_point->carrier_code}" name="shop_carrier_code_{$service_point->carrier_code}"
                               value="{$service_point->carrier_code}">
                        <input type="hidden" class="input_shop_name" id="shop_name_{$service_point->number}"
                               name="shop_name_{$service_point->number}" value="{$service_point->company_name}">
                        <input type="hidden" class="input_shop_address" id="shop_address_{$service_point->number}"
                               name="shop_address_{$service_point->number}" value="{$service_point->address}">
                        <input type="hidden" class="input_shop_zip" id="shop_zip_{$service_point->number}"
                               name="shop_zip_{$service_point->number}" value="{$service_point->zipcode}">
                        <input type="hidden" class="input_shop_city" id="shop_city_{$service_point->number}"
                               name="shop_city_{$service_point->number}" value="{$service_point->city}">

                        <!--<div class="shipmondo-radio-button"></div>-->
                        <span class="custom-radio">
                            <input type="radio">
                            <span></span>
                        </span>

                        <div class="shipmondo-pickup-point-name">{$service_point->company_name}</div>
                        <div class="shipmondo-pickup-point-address">{$service_point->address}</div>
                        <div class="shipmondo-pickup-point-zipcode-city">
                            <span class="shipmondo-pickup-point-zipcode">{$service_point->zipcode}</span> <span class="shipmondo-pickup-point-city">{$service_point->city}</span>
                        </div>
                    </div>
                </li>
                {/foreach}
            </ul>
        </div>
    </div>
    <div class="shipmondo-modal-footer">
        {l s='Powered by Shipmondo' mod='shipmondo'}
    </div>
</div>
<div class="shipmondo-modal-checkmark">
    <svg class="shipmondo-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
        <circle class="shipmondo-checkmark_circle" cx="26" cy="26" r="25" fill="none"/>
        <path class="shipmondo-checkmark_check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/>
    </svg>
</div>