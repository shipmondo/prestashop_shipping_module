{*
*  @author    Shipmondo <support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="shipmondo-service-points-container">
    <h3 class="service_point_title">{l s='Service point' d='Modules.Shipmondo.Front'}</h3>
    <div class="shipmondo-original">
        <div class="shipmondo_service_point_selection selector_type-{if $frontendType == 'popup'}modal{else}dropdown{/if}">
            <div class="shipmondo-loading">{l s='Loading...' d='Modules.Shipmondo.Front'}</div>
            <div class="shipmondo-service-points-content"></div>
            <div class="powered_by_shipmondo">
                <p>
                    {l s='Powered by Shipmondo' d='Modules.Shipmondo.Front'}
                </p>
            </div>
        </div>
    </div>
</div>
