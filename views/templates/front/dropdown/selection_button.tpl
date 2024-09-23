{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="shipmondo_service_point_selection">

    {include file='module:shipmondo/views/templates/front/_partials/selected_service_point.tpl'}

    <div class="service_points_dropdown">
        {foreach $service_points as $sp}
        <div class="service_point {if $sp->id == $service_point->id}selected{/if}">
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

    <div class="powered_by_shipmondo">
        <p>Powered by Shipmondo</p>
    </div>
</div>