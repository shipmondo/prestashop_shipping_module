{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="shipmondo-modal service_points_modal shipmondo-hidden" tabindex="-1" role="dialog">
    <div class="shipmondo-modal_wrapper">
        <div class="shipmondo-modal_content">
            <button class="shipmondo-modal_close">
                <span aria-hidden="true">×</span>
            </button>
            <div class="shipmondo-modal_header">
                <h4>{l s='Choose pickup point' mod='shipmondo'}</h4>
            </div>
            <div class="service_points_map"></div>
            <div class="service_points_list">
                {foreach $service_points as $sp}
                <div class="service_point{if $sp->id == $service_point->id} selected{/if}"
                     data-service_point_id="{$sp->id}"
                     data-name="{$sp->name}"
                     data-address1="{$sp->address}"
                     data-city="{$sp->city}"
                     data-zip_code="{$sp->zipcode}"
                     data-distance="{$sp->distance}"
                     data-longitude="{$sp->longitude}"
                     data-latitude="{$sp->latitude}"
                     data-carrier_code="{$sp->carrier_code}">
                    <div class="header"><span class="name">{$sp->name}</span></div>
                    <div class="location">
                        <div class="address_info">{$sp->address}, {$sp->zipcode} {$sp->city}</div>
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
        <div class="shipmondo-modal-checkmark">
            <svg class="shipmondo-checkmark" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52">
                <circle class="shipmondo-checkmark_circle" cx="26" cy="26" r="25" fill="none"></circle>
                <path class="shipmondo-checkmark_check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"></path>
            </svg>
        </div>
    </div>
</div>
