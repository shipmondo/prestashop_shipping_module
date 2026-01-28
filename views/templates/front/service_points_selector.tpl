{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="selected_service_point service_point selector_type-{if $frontendType == 'popup'}modal{else}dropdown{/if}">
    <div class="header">
    <span class="name">{$selectedServicePoint->name}</span>
        <span class="rate_name">{$carrier->name}</span>
    </div>
    <div class="location">
    <div class="address_info">{$selectedServicePoint->address},{if $selectedServicePoint->address2} {$selectedServicePoint->address2},{/if} {$selectedServicePoint->zipcode} {$selectedServicePoint->city}</div>
        {if $selectedServicePoint->distance}
            <div class="distance">{($selectedServicePoint->distance / 1000)|string_format:"%.2f"} km</div>
        {/if}
    </div>
</div>
{if $frontendType == 'popup'}
    {include file='module:shipmondo/views/templates/front/popup/content.tpl'}
{else}
    {include file='module:shipmondo/views/templates/front/dropdown/content.tpl'}
{/if}
