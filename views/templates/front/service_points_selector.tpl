{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="selected_service_point service_point selector_type-{if $frontendType == 'popup'}modal{else}dropdown{/if}">
    <div class="header">
    <span class="name">{$service_point->name}</span>
        <span class="rate_name">{$carrier->name}</span>
    </div>
    <div class="location">
    <div class="address_info">{$service_point->address},{if $service_point->address2} {$service_point->address2},{/if} {$service_point->zipcode} {$service_point->city}</div>
        {if $service_point->distance}
            <div class="distance">{($service_point->distance / 1000)|string_format:"%.2f"} km</div>
        {/if}
    </div>
</div>

{if $frontendType == 'popup'}
{include file='module:shipmondo/views/templates/front/popup/content.tpl'}
{else}
{include file='module:shipmondo/views/templates/front/dropdown/content.tpl'}
{/if}