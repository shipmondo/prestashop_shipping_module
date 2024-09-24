{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="header">
    <span class="name">{$service_point->name}</span>
    <span class="rate_name">{$carrier->name}</span>
</div>
<div class="location">
    <div class="address_info">{$service_point->address}, {$service_point->zipcode} {$service_point->city}</div>
    {if $service_point->distance}
        <div class="distance">{($service_point->distance / 1000)|string_format:"%.2f"} km</div>
    {/if}
</div>