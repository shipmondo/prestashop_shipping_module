{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="shipmondo-dropdown_wrapper">
    <div class="service_points_dropdown">
        <div class="service_points_list">
            {foreach $service_points as $sp}
            <div class="service_point{if $sp->id == $service_point->id} selected{/if}"
                 data-service_point_id="{$sp->id}"
                 data-name="{$sp->name}"
                 data-address1="{$sp->address}"
                 data-city="{$sp->city}"
                 data-zip_code="{$sp->zipcode}"
                 data-distance="{$sp->distance}"
                 data-carrier_code="{$sp->carrier_code}">
                <div class="header"><span class="name">{$sp->name}</span></div>
                <div class="location">
                    <div class="address_info">{$sp->address},{if $sp->address2} {$sp->address2},{/if} {$sp->zipcode} {$sp->city}</div>
                    {if $sp->distance}
                    <div class="distance">{($sp->distance / 1000)|string_format:"%.2f"} km</div>
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