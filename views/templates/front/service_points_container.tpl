{*
*  @author    Shipmondo
*
<support@shipmondo.com>
*  @copyright 2024-present Shipmondo
*  @license   https://opensource.org/license/bsd-3-clause BSD-3-Clause
*}

<div class="shipmondo-service-points-container">
    <h3 class="service_point_title">{l s='Pickup point' d='Modules.Shipmondo.Front'}</h3>
    <div class="shipmondo-original">
        <div class="shipmondo_service_point_selection selector_type-{if $frontendType == 'popup'}modal{else}dropdown{/if}">
            <div class="selected_service_point service_point selector_type-{if $frontendType == 'popup'}modal{else}dropdown{/if}">
                {* Replaced by the service point selector *}
            </div>

            <div class="shipmondo-loading">Arbejder...</div>
            <!--<div class="shipmondo-no-service-points">Ingen udleveringsteder</div>
            <div class="shipmondo-error-message">Error?</div>-->


            {if $frontendType == 'popup'}
                {include file='module:shipmondo/views/templates/front/popup/content.tpl'}
            {else}
                {include file='module:shipmondo/views/templates/front/dropdown/content.tpl'}
            {/if}


            <!--
                            <div class="selected_service_point service_point no_service_point">Ingen tilgængelige udleveringssteder</div>
                            <div class="selected_service_point service_point no_service_point has-error">Error</div>
                            <div class="selected_service_point service_point loading">Arbejder...</div>'

                            <div class="selected_service_point service_point selector_type-{if $frontendType == 'popup'}modal{else}dropdown{/if}">
                                hej
                            </div>
            -->


            <div class="powered_by_shipmondo">
                <p>Powered by Shipmondo</p>
            </div>
        </div>
    </div>
</div>