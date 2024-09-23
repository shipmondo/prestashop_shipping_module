{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div id="hidden_chosen_shop">
{*div id="hidden_chosen_shop">
    <input type="hidden" name="shipmondo">
    <input type="hidden" name="shop_name">
    <input type="hidden" name="shop_address">
    <input type="hidden" name="shop_zip">
    <input type="hidden" name="shop_city">
    <input type="hidden" name="shop_carrier_code">
    <input type="hidden" name="shop_ID">
</div>
<div class="shipmondo-clearfix {if $service_point}active{/if}" id="selected_shop_context">
    <div class="shipmondo-shop-header">{l s='Currently chosen pickup point:' mod='shipmondo'}</div>
    <div class="shipmondo-shop-name">{if $service_point}{$service_point->getName()}{/if}</div>
    <div class="shipmondo-shop-address">{if $service_point}{$service_point->getAddress1()}{/if}</div>
    <div class="shipmondo-shop-zip-and-city">{if $service_point}{$service_point->getZipCode()}
        {$service_point->getCity()}{/if}
    </div>
    <div class="shipmondo-shop-id">{if $service_point}{$service_point->getServicePointId()}{/if}</div>
</div>*}


    <h3 class="service_point_title">{l s='Pickup point' mod='shipmondo'}</h3>
    <div class="selected_service_point service_point">
        <div class="header">
            <span class="name">{$service_point->name}</span>
            <!--<span class="rate_name">GLS Pakkeshop</span>-->
            <span class="rate_name">{$carrier->name}</span>
        </div>
        <div class="location">
            <!--<div class="address_info">Skibhusvej 70A, 5000 Odense C</div>-->
            <div class="address_info">{$service_point->address}, {$service_point->zipcode} {$service_point->city}</div>
            {if $service_point->distance}
                <div class="distance">{$service_point->distance / 1000} km</div>
            {/if}
        </div>
    </div>
