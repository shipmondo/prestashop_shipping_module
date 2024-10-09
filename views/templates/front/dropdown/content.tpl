{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="shipmondo-dropdown_wrapper">
    <div class="service_points_dropdown">
        <div class="service_points_list">
            {foreach $servicePoints as $servicePoint}
            <div class="service_point{if $servicePoint->id == $selectedServicePoint->id} selected{/if}"
                 data-service_point_id="{$servicePoint->id}"
                 data-name="{$servicePoint->name}"
                 data-address1="{$servicePoint->address}"
                 data-city="{$servicePoint->city}"
                 data-zip_code="{$servicePoint->zipcode}"
                 data-distance="{$servicePoint->distance}"
                 data-carrier_code="{$servicePoint->carrier_code}">
                <div class="header"><span class="name">{$servicePoint->name}</span></div>
                <div class="location">
                    <div class="address_info">{$servicePoint->address},{if $servicePoint->address2} {$servicePoint->address2},{/if} {$servicePoint->zipcode} {$servicePoint->city}</div>
                    {if $servicePoint->distance}
                    <div class="distance">{($servicePoint->distance / 1000)|string_format:"%.2f"} km</div>
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