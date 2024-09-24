{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<h3 class="service_point_title">{l s='Pickup point' mod='shipmondo'}</h3>
<div class="shipmondo-original">
    <div class="shipmondo_service_point_selection selector_type-dropdown">
        <div class="selected_service_point service_point selector_type-dropdown">
            {include file='module:shipmondo/views/templates/front/_partials/selected_service_point.tpl'}
        </div>
        <div class="shipmondo-dropdown_wrapper">
            <div class="service_points_dropdown">
                <div class="service_points_list">
                    {foreach $service_points as $sp}
                        <div class="service_point{if $sp->id == $service_point->id} selected{/if}"
                             data-service_point_id="{$sp->id}"
                             data-name="{$sp->name}"
                             data-address="{$sp->address}"
                             data-city="{$sp->city}"
                             data-zip_code="{$sp->zipcode}"
                             data-carrier_code="{$sp->carrier_code}">
                            <div class="header"><span class="name">{$sp->name}</span></div>
                            <div class="location">
                                <div class="address_info">{$sp->address}, {$sp->zipcode} {$sp->city}</div>
                                {if $sp->distance}
                                    <div class="distance">{$sp->distance / 1000} km</div>
                                {/if}
                            </div>
                        </div>
                    {/foreach}
                </div>
                <div class="shipmondo-modal_footer">
                    <div class="powered_by_shipmondo">
                        <p>Powered by Shipmondo</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="powered_by_shipmondo">
            <p>Powered by Shipmondo</p>
        </div>
    </div>
</div>