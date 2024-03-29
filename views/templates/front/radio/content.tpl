{*
*  @author    Shipmondo
*  @copyright 2023 Shipmondo
*  @license   All rights reserved
*}

<div class="shipmondo-radio-content">
    <div class="shipmondo-list-wrapper">
        <ul class="shipmondo-shoplist-ul">
            {foreach $service_points as $service_point}
            <li class="shipmondo-shop-list" data-id="{$service_point->number}">
                <div class="shipmondo-pickup-point-info">
                    <input type="hidden" class="input_shop_carrier_code" id="shop_carrier_code_{$service_point->carrier_code}"
                           name="shop_carrier_code_{$service_point->carrier_code}"
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
    <div class="shipmondo-radio-footer">
        {l s='Powered by Shipmondo' mod='shipmondo'}
    </div>
</div>