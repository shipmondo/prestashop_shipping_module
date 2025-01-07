{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="header">
    <span class="name">{$servicePoint->name}</span>
    <span class="rate_name">{$carrier->name}</span>
</div>
<div class="location">
<div class="address_info">{$servicePoint->address},{if $servicePoint->address2} {$servicePoint->address2},{/if} {$servicePoint->zipcode} {$servicePoint->city}</div>
    {if $servicePoint->distance}
        <div class="distance">{($servicePoint->distance / 1000)|string_format:"%.2f"} km</div>
    {/if}
</div>